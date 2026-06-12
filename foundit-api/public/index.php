<?php
// ----------------------------------------------------------------------
// FoundIt API — application entry point (bootstrap)
// Owner: M1.  This file is already wired; you normally don't change it.
// ----------------------------------------------------------------------

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

// 1. Load environment variables from .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// 2. Create the Slim app
$app = AppFactory::create();

// 3. Parse incoming JSON bodies into arrays
$app->addBodyParsingMiddleware();

// 4. CORS — let the Vue frontend call this API from another origin
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $_ENV['CORS_ORIGIN'] ?? '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// 5. Answer browser pre-flight (OPTIONS) requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// 6. Error handling — only expose error details when APP_DEBUG=true (keep
//    it false in production so stack traces are never leaked to clients)
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
$app->addErrorMiddleware($debug, true, true);

// 7. Register all API routes (see routes/api.php)
(require __DIR__ . '/../routes/api.php')($app);

$app->run();
