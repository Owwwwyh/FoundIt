<?php
// ----------------------------------------------------------------------
// Admin authorization middleware (Feature: admin role + moderation).
// Runs *after* JwtMiddleware, so the request already carries a verified
// `role` attribute. It lets the request through only for admins; everyone
// else gets a 403 Forbidden. This is the role-based authorization layer
// that sits on top of the per-resource ownership checks in the controllers.
// ----------------------------------------------------------------------

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

class AdminMiddleware
{
    public function __invoke(Request $request, Handler $handler)
    {
        if ($request->getAttribute('role') !== 'admin') {
            $res = new Response();
            $res->getBody()->write(json_encode(['error' => 'Admin access required.']));
            return $res->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
