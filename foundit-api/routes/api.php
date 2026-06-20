<?php
// ----------------------------------------------------------------------
// FoundIt API — route definitions ("the API contract" in code)
// Public routes are open; the protected group requires a valid JWT.
// ----------------------------------------------------------------------

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\ItemController;
use App\Controllers\ClaimController;
use App\Controllers\AdminController;
use App\Middleware\JwtMiddleware;
use App\Middleware\AdminMiddleware;

return function (App $app) {

    $app->group('/api', function ($group) {

        // ---------- PUBLIC ----------
        $group->post('/register',         [AuthController::class, 'register']);
        $group->post('/login',            [AuthController::class, 'login']);
        $group->post('/forgot-password',  [AuthController::class, 'forgotPassword']);
        $group->post('/reset-password',   [AuthController::class, 'resetPassword']);
        $group->get('/items',              [ItemController::class, 'index']);
        $group->get('/lost-leaderboard',   [ItemController::class, 'leaderboard']);
        $group->get('/items/{id}',         [ItemController::class, 'show']);
        $group->get('/items/{id}/matches', [ItemController::class, 'matches']);

        // ---------- PROTECTED (JWT required) ----------
        $group->group('', function ($g) {
            $g->get('/me/items',           [ItemController::class, 'mine']);
            $g->get('/me/claims',          [ClaimController::class, 'mine']);

            $g->post('/items',             [ItemController::class, 'store']);
            $g->put('/items/{id}',         [ItemController::class, 'update']);
            $g->delete('/items/{id}',      [ItemController::class, 'destroy']);
            $g->post('/items/{id}/image',  [ItemController::class, 'uploadImage']);
            $g->post('/items/{id}/ai-hints', [ItemController::class, 'regenerateHints']);

            $g->get('/items/{id}/claims',  [ClaimController::class, 'index']);
            $g->post('/items/{id}/claims', [ClaimController::class, 'store']);
            $g->put('/claims/{id}',        [ClaimController::class, 'update']);
            $g->delete('/claims/{id}',     [ClaimController::class, 'destroy']);
        })->add(new JwtMiddleware());

        // ---------- ADMIN (JWT + admin role required) ----------
        // AdminMiddleware runs after JwtMiddleware (added last = outermost),
        // so the role attribute is already set when it checks for 'admin'.
        $group->group('/admin', function ($a) {
            $a->get('/stats',          [AdminController::class, 'stats']);
            $a->get('/items',          [AdminController::class, 'items']);
            $a->get('/lost-items',     [AdminController::class, 'lostItems']);
            $a->put('/items/{id}',     [AdminController::class, 'updateItem']);
            $a->delete('/items/{id}',  [AdminController::class, 'destroyItem']);
            $a->get('/users',          [AdminController::class, 'users']);
        })->add(new AdminMiddleware())->add(new JwtMiddleware());

    });
};
