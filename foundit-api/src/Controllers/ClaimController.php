<?php
// ----------------------------------------------------------------------
// ClaimController — claims workflow on items.  Owner: M2 (backend).
// Implemented:
//   index   - item owner lists the claims on their item
//   store   - a logged-in user files a claim (with business-rule guards)
//   update  - item owner approves/rejects (transaction: approving resolves
//             the item and auto-rejects the other pending claims)
//   destroy - the claimant withdraws their own claim
// ----------------------------------------------------------------------

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Database;

class ClaimController
{
    // GET /api/items/{id}/claims   (JWT, item owner only)
    public function index(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $itemId = (int) $args['id'];
        $pdo    = Database::pdo();

        $stmt = $pdo->prepare('SELECT user_id FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }
        if ((int) $item['user_id'] !== $userId) {
            return $this->json($response, ['error' => 'Only the item owner can view its claims.'], 403);
        }

        $stmt = $pdo->prepare(
            'SELECT c.*, u.name AS claimant_name, u.email AS claimant_email
             FROM claims c JOIN users u ON u.id = c.user_id
             WHERE c.item_id = ?
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([$itemId]);

        return $this->json($response, ['claims' => $stmt->fetchAll()], 200);
    }

    // POST /api/items/{id}/claims   (JWT)
    public function store(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $itemId = (int) $args['id'];
        $data   = (array) $request->getParsedBody();
        $pdo    = Database::pdo();

        // Item must exist
        $stmt = $pdo->prepare('SELECT user_id FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }

        // Business rule: you cannot claim your own posted item
        if ((int) $item['user_id'] === $userId) {
            return $this->json($response, ['error' => 'You cannot file a claim on your own item.'], 422);
        }

        // Validation: proof message required
        $message = trim($data['message'] ?? '');
        if (mb_strlen($message) < 10) {
            return $this->json($response, [
                'errors' => ['message' => 'Please describe your proof of ownership (at least 10 characters).'],
            ], 422);
        }

        // Business rule: one active (pending) claim per user per item
        $stmt = $pdo->prepare("SELECT id FROM claims WHERE item_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$itemId, $userId]);
        if ($stmt->fetch()) {
            return $this->json($response, ['error' => 'You already have a pending claim on this item.'], 409);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO claims (item_id, user_id, message, status) VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([$itemId, $userId, $message]);

        return $this->fetchOne($response, (int) $pdo->lastInsertId(), 201);
    }

    // PUT /api/claims/{id}   (JWT, item owner approves/rejects)
    public function update(Request $request, Response $response, array $args): Response
    {
        $userId  = (int) $request->getAttribute('user_id');
        $claimId = (int) $args['id'];
        $data    = (array) $request->getParsedBody();
        $pdo     = Database::pdo();

        // Load the claim together with the owner of its item
        $stmt = $pdo->prepare(
            'SELECT c.id, c.item_id, i.user_id AS item_owner
             FROM claims c JOIN items i ON i.id = c.item_id
             WHERE c.id = ?'
        );
        $stmt->execute([$claimId]);
        $claim = $stmt->fetch();

        if (!$claim) {
            return $this->json($response, ['error' => 'Claim not found.'], 404);
        }
        if ((int) $claim['item_owner'] !== $userId) {
            return $this->json($response, ['error' => 'Only the item owner can review claims.'], 403);
        }

        $status = $data['status'] ?? '';
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return $this->json($response, [
                'errors' => ['status' => "Status must be 'approved' or 'rejected'."],
            ], 422);
        }

        // Use a transaction so the claim + item + sibling claims stay consistent
        try {
            $pdo->beginTransaction();

            $pdo->prepare('UPDATE claims SET status = ? WHERE id = ?')->execute([$status, $claimId]);

            if ($status === 'approved') {
                // The item is being returned -> mark it resolved...
                $pdo->prepare("UPDATE items SET status = 'resolved' WHERE id = ?")
                    ->execute([$claim['item_id']]);
                // ...and auto-reject the other still-pending claims on the same item
                $pdo->prepare(
                    "UPDATE claims SET status = 'rejected'
                     WHERE item_id = ? AND id <> ? AND status = 'pending'"
                )->execute([$claim['item_id'], $claimId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;   // handled by Slim's error middleware -> 500
        }

        return $this->fetchOne($response, $claimId, 200);
    }

    // DELETE /api/claims/{id}   (JWT, the claimant withdraws)
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId  = (int) $request->getAttribute('user_id');
        $claimId = (int) $args['id'];
        $pdo     = Database::pdo();

        $stmt = $pdo->prepare('SELECT user_id FROM claims WHERE id = ?');
        $stmt->execute([$claimId]);
        $claim = $stmt->fetch();

        if (!$claim) {
            return $this->json($response, ['error' => 'Claim not found.'], 404);
        }
        if ((int) $claim['user_id'] !== $userId) {
            return $this->json($response, ['error' => 'You can only withdraw your own claim.'], 403);
        }

        $pdo->prepare('DELETE FROM claims WHERE id = ?')->execute([$claimId]);
        return $response->withStatus(204);
    }

    // GET /api/me/claims   (JWT) — claims filed by the logged-in user
    public function mine(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $stmt = Database::pdo()->prepare(
            'SELECT c.*, i.title AS item_title, i.type AS item_type
             FROM claims c JOIN items i ON i.id = c.item_id
             WHERE c.user_id = ? ORDER BY c.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $this->json($response, ['claims' => $stmt->fetchAll()], 200);
    }

    // ---------------------------------------------------------------
    private function fetchOne(Response $response, int $id, int $status): Response
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.*, u.name AS claimant_name
             FROM claims c JOIN users u ON u.id = c.user_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $this->json($response, ['claim' => $stmt->fetch()], $status);
    }

    private function json(Response $r, array $data, int $status = 200): Response
    {
        $r->getBody()->write(json_encode($data));
        return $r->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
