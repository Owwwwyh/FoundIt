<?php
// ----------------------------------------------------------------------
// AuthController — register + login.  Owner: M1 (OW YEE HAO).
// Implemented: input validation, duplicate-email check, bcrypt password
// hashing, and JWT issuing.
// ----------------------------------------------------------------------

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Database;
use App\Services\MailService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController
{
    // POST /api/register
    public function register(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $name     = trim($data['name'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        // --- Validation (returns 422 with per-field messages) ---
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is not valid.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($errors) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $pdo = Database::pdo();

        // --- Reject duplicate email (409 Conflict) ---
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return $this->json($response, ['error' => 'Email is already registered.'], 409);
        }

        // --- Hash the password and insert the new user ---
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);
        $id = (int) $pdo->lastInsertId();

        // Never return the password hash. New accounts are always normal users.
        return $this->json($response, [
            'user' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'user'],
        ], 201);
    }

    // POST /api/login
    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $email    = trim($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => 'Email and password are required.'], 422);
        }

        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Same generic message whether the email or the password is wrong
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->json($response, ['error' => 'Invalid email or password.'], 401);
        }

        $role = $user['role'] ?? 'user';

        // --- Build and sign the JWT ---
        $now = time();
        $payload = [
            'sub'  => $user['id'],               // the user id (read by JwtMiddleware)
            'name' => $user['name'],
            'role' => $role,                     // 'user' | 'admin' (role-based authorization)
            'iat'  => $now,                      // issued at
            'exp'  => $now + (int) $_ENV['JWT_EXPIRY'],  // expires
        ];
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        return $this->json($response, [
            'token' => $token,
            'user'  => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $role],
        ], 200);
    }

    // POST /api/forgot-password — email a reset link (no DB migration: the link
    // carries a short-lived signed token). Always replies the same way so we
    // never reveal which emails are registered.
    public function forgotPassword(Request $request, Response $response): Response
    {
        $data  = (array) $request->getParsedBody();
        $email = trim($data['email'] ?? '');

        $generic = ['message' => "If that email is registered, we've sent a password reset link."];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, $generic, 200);
        }

        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $now   = time();
            $token = JWT::encode([
                'sub'     => $user['id'],
                'purpose' => 'reset',
                'pwck'    => substr($user['password_hash'], -12), // binds the token to the current password (single-use)
                'iat'     => $now,
                'exp'     => $now + 1800,                         // valid for 30 minutes
            ], $_ENV['JWT_SECRET'], 'HS256');

            $link = rtrim($_ENV['CORS_ORIGIN'] ?? '', '/') . '/reset-password?token=' . $token;

            try {
                (new MailService())->sendPasswordReset([
                    'name'       => $user['name'],
                    'email'      => $user['email'],
                    'reset_link' => $link,
                ]);
            } catch (\Throwable $e) {
                error_log('FoundIt password-reset email error: ' . $e->getMessage());
            }
        }

        return $this->json($response, $generic, 200);
    }

    // POST /api/reset-password — verify the token and set a new password.
    public function resetPassword(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $token    = (string) ($data['token'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if (strlen($password) < 6) {
            return $this->json($response, ['errors' => ['password' => 'Password must be at least 6 characters.']], 422);
        }
        if ($token === '') {
            return $this->json($response, ['error' => 'Reset link is missing or invalid.'], 400);
        }

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => 'This reset link is invalid or has expired. Please request a new one.'], 400);
        }

        if (($decoded->purpose ?? '') !== 'reset') {
            return $this->json($response, ['error' => 'This reset link is invalid.'], 400);
        }

        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = ?');
        $stmt->execute([(int) ($decoded->sub ?? 0)]);
        $user = $stmt->fetch();

        if (!$user) {
            return $this->json($response, ['error' => 'This reset link is invalid.'], 400);
        }
        // Single-use: the token was bound to the password it was issued for.
        if (($decoded->pwck ?? '') !== substr($user['password_hash'], -12)) {
            return $this->json($response, ['error' => 'This reset link has already been used. Please request a new one.'], 400);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);

        return $this->json($response, ['message' => 'Your password has been updated. You can now log in.'], 200);
    }

    // Helper: send a JSON response with a status code
    private function json(Response $r, array $data, int $status = 200): Response
    {
        $r->getBody()->write(json_encode($data));
        return $r->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
