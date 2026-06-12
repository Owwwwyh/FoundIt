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
use Firebase\JWT\JWT;

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

        // Never return the password hash
        return $this->json($response, [
            'user' => ['id' => $id, 'name' => $name, 'email' => $email],
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
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Same generic message whether the email or the password is wrong
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->json($response, ['error' => 'Invalid email or password.'], 401);
        }

        // --- Build and sign the JWT ---
        $now = time();
        $payload = [
            'sub'  => $user['id'],               // the user id (read by JwtMiddleware)
            'name' => $user['name'],
            'iat'  => $now,                      // issued at
            'exp'  => $now + (int) $_ENV['JWT_EXPIRY'],  // expires
        ];
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        return $this->json($response, [
            'token' => $token,
            'user'  => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']],
        ], 200);
    }

    // Helper: send a JSON response with a status code
    private function json(Response $r, array $data, int $status = 200): Response
    {
        $r->getBody()->write(json_encode($data));
        return $r->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
