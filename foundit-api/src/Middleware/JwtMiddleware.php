<?php
// ----------------------------------------------------------------------
// JWT authentication middleware.
// Owner: M1.  Sits in front of every protected route.
// Reads "Authorization: Bearer <token>", verifies the token, and either
// lets the request through (attaching the user id) or returns 401.
// ----------------------------------------------------------------------

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    public function __invoke(Request $request, Handler $handler)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s+(.+)/', $header, $m)) {
            return $this->unauthorized('Missing or malformed token');
        }

        try {
            $decoded = JWT::decode($m[1], new Key($_ENV['JWT_SECRET'], 'HS256'));
            // Make the logged-in user available to controllers + later middleware:
            //   $request->getAttribute('user_id')  ·  $request->getAttribute('role')
            // Tokens issued before roles existed simply default to 'user'.
            $request = $request->withAttribute('user_id', $decoded->sub);
            $request = $request->withAttribute('role', $decoded->role ?? 'user');
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid or expired token');
        }

        return $handler->handle($request);
    }

    private function unauthorized(string $msg): Response
    {
        $res = new Response();
        $res->getBody()->write(json_encode(['error' => $msg]));
        return $res->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
