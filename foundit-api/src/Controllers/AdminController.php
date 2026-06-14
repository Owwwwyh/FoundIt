<?php
// ----------------------------------------------------------------------
// AdminController — moderation dashboard for admin users.
// Every route here is behind JwtMiddleware + AdminMiddleware, so by the
// time a method runs we already know the caller is an authenticated admin.
//
//   stats        - aggregate numbers for the dashboard (totals, resolved rate)
//   items        - list every item (any owner) with poster + claim counts
//   destroyItem  - remove ANY item (deeper than the owner-only delete)
//   updateItem   - force an item's status (e.g. reopen / mark resolved)
//   users        - list accounts (with how many items each posted)
// ----------------------------------------------------------------------

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Database;

class AdminController
{
    private const STATUSES = ['open', 'claimed', 'resolved'];

    // GET /api/admin/stats
    public function stats(Request $request, Response $response): Response
    {
        $pdo = Database::pdo();

        $totalUsers  = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

        // One pass over items for all the per-status / per-type counters.
        $itemRows = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(type = 'lost')      AS lost,
                SUM(type = 'found')     AS found,
                SUM(status = 'open')    AS open,
                SUM(status = 'claimed') AS claimed,
                SUM(status = 'resolved') AS resolved
             FROM items"
        )->fetch();

        $claimRows = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'pending')  AS pending,
                SUM(status = 'approved') AS approved,
                SUM(status = 'rejected') AS rejected
             FROM claims"
        )->fetch();

        $totalItems   = (int) ($itemRows['total'] ?? 0);
        $resolved     = (int) ($itemRows['resolved'] ?? 0);
        $resolvedRate = $totalItems > 0 ? round(($resolved / $totalItems) * 100, 1) : 0.0;

        // A small per-category breakdown for the dashboard chart/list.
        $byCategory = $pdo->query(
            'SELECT category, COUNT(*) AS count FROM items
             GROUP BY category ORDER BY count DESC'
        )->fetchAll();

        return $this->json($response, [
            'stats' => [
                'users'         => $totalUsers,
                'admins'        => $totalAdmins,
                'items'         => $totalItems,
                'lost'          => (int) ($itemRows['lost'] ?? 0),
                'found'         => (int) ($itemRows['found'] ?? 0),
                'open'          => (int) ($itemRows['open'] ?? 0),
                'claimed'       => (int) ($itemRows['claimed'] ?? 0),
                'resolved'      => $resolved,
                'resolved_rate' => $resolvedRate,
                'claims'        => (int) ($claimRows['total'] ?? 0),
                'claims_pending'  => (int) ($claimRows['pending'] ?? 0),
                'claims_approved' => (int) ($claimRows['approved'] ?? 0),
                'claims_rejected' => (int) ($claimRows['rejected'] ?? 0),
                'by_category'   => array_map(static function ($r) {
                    return ['category' => $r['category'], 'count' => (int) $r['count']];
                }, $byCategory),
            ],
        ], 200);
    }

    // GET /api/admin/items   — every item, with optional ?type= &status= &search=
    public function items(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();

        $sql = 'SELECT i.*, u.name AS poster_name, u.email AS poster_email,
                       (SELECT COUNT(*) FROM claims c WHERE c.item_id = i.id) AS claim_count
                FROM items i JOIN users u ON u.id = i.user_id';
        $where  = [];
        $params = [];

        if (!empty($q['type']) && in_array($q['type'], ['lost', 'found'], true)) {
            $where[]  = 'i.type = ?';
            $params[] = $q['type'];
        }
        if (!empty($q['status']) && in_array($q['status'], self::STATUSES, true)) {
            $where[]  = 'i.status = ?';
            $params[] = $q['status'];
        }
        if (!empty($q['search'])) {
            $where[]  = '(i.title LIKE ? OR i.location LIKE ? OR u.name LIKE ?)';
            $term     = '%' . $q['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY i.created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $this->json($response, ['items' => $stmt->fetchAll()], 200);
    }

    // DELETE /api/admin/items/{id}   — admins can remove any item.
    public function destroyItem(Request $request, Response $response, array $args): Response
    {
        $id  = (int) $args['id'];
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT image_path FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }

        $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);

        // Best-effort: also remove the uploaded image file (same rule as ItemController).
        if (!empty($row['image_path'])) {
            $full = $this->uploadsDir() . '/' . basename($row['image_path']);
            if (is_file($full)) {
                @unlink($full);
            }
        }

        return $response->withStatus(204);
    }

    // PUT /api/admin/items/{id}   — moderate an item's status (open/claimed/resolved).
    public function updateItem(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = (array) $request->getParsedBody();
        $pdo  = Database::pdo();

        $status = $data['status'] ?? '';
        if (!in_array($status, self::STATUSES, true)) {
            return $this->json($response, ['errors' => ['status' => 'Status must be open, claimed or resolved.']], 422);
        }

        $stmt = $pdo->prepare('SELECT id FROM items WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }

        $pdo->prepare('UPDATE items SET status = ? WHERE id = ?')->execute([$status, $id]);

        $stmt = $pdo->prepare(
            'SELECT i.*, u.name AS poster_name, u.email AS poster_email,
                    (SELECT COUNT(*) FROM claims c WHERE c.item_id = i.id) AS claim_count
             FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ?'
        );
        $stmt->execute([$id]);
        return $this->json($response, ['item' => $stmt->fetch()], 200);
    }

    // GET /api/admin/users
    public function users(Request $request, Response $response): Response
    {
        $stmt = Database::pdo()->query(
            'SELECT u.id, u.name, u.email, u.role, u.created_at,
                    (SELECT COUNT(*) FROM items i WHERE i.user_id = u.id) AS item_count
             FROM users u ORDER BY u.created_at ASC'
        );
        return $this->json($response, ['users' => $stmt->fetchAll()], 200);
    }

    // ---------------------------------------------------------------
    private function uploadsDir(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? (__DIR__ . '/../../public');
        return rtrim($root, "/\\") . '/uploads';
    }

    private function json(Response $r, array $data, int $status = 200): Response
    {
        $r->getBody()->write(json_encode($data));
        return $r->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
