<?php
// ----------------------------------------------------------------------
// FoundIt API — route definitions ("the API contract" in code)
// Public routes are open; the protected group requires a valid JWT.
// ----------------------------------------------------------------------

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\ItemController;
use App\Controllers\ClaimController;
use App\Middleware\JwtMiddleware;

return function (App $app) {

    $app->group('/api', function ($group) {

        // ---------- PUBLIC ----------
        $group->post('/register',  [AuthController::class, 'register']);
        $group->post('/login',     [AuthController::class, 'login']);
        $group->get('/items',      [ItemController::class, 'index']);
        $group->get('/items/{id}', [ItemController::class, 'show']);

        // ---------- PROTECTED (JWT required) ----------
        $group->group('', function ($g) {
            $g->get('/me/items',           [ItemController::class, 'mine']);
            $g->get('/me/claims',          [ClaimController::class, 'mine']);

            $g->post('/items',             [ItemController::class, 'store']);
            $g->put('/items/{id}',         [ItemController::class, 'update']);
            $g->delete('/items/{id}',      [ItemController::class, 'destroy']);
            $g->post('/items/{id}/image',  [ItemController::class, 'uploadImage']);

            $g->get('/items/{id}/claims',  [ClaimController::class, 'index']);
            $g->post('/items/{id}/claims', [ClaimController::class, 'store']);
            $g->put('/claims/{id}',        [ClaimController::class, 'update']);
            $g->delete('/claims/{id}',     [ClaimController::class, 'destroy']);
        })->add(new JwtMiddleware());

    });
};
