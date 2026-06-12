<?php
// ----------------------------------------------------------------------
// ItemController — CRUD for lost/found items.  Owner: M2 (backend).
// Implemented: filtered listing, single fetch, create, owner-guarded
// update & delete. All queries use PDO prepared statements.
// ----------------------------------------------------------------------

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Database;

class ItemController
{
    private const TYPES    = ['lost', 'found'];
    private const STATUSES = ['open', 'claimed', 'resolved'];
    private const MAX_IMAGE_BYTES = 2097152; // 2 MB
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    // GET /api/items   (public) — supports ?type= &category= &search= &status=
    public function index(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();

        $sql    = 'SELECT i.*, u.name AS poster_name
                   FROM items i JOIN users u ON u.id = i.user_id';
        $where  = [];
        $params = [];

        if (!empty($q['type']) && in_array($q['type'], self::TYPES, true)) {
            $where[]  = 'i.type = ?';
            $params[] = $q['type'];
        }
        if (!empty($q['category'])) {
            $where[]  = 'i.category = ?';
            $params[] = $q['category'];
        }
        if (!empty($q['status']) && in_array($q['status'], self::STATUSES, true)) {
            $where[]  = 'i.status = ?';
            $params[] = $q['status'];
        }
        if (!empty($q['search'])) {
            $where[]  = '(i.title LIKE ? OR i.description LIKE ?)';
            $term     = '%' . $q['search'] . '%';
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

    // GET /api/items/{id}   (public)
    public function show(Request $request, Response $response, array $args): Response
    {
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, u.name AS poster_name
             FROM items i JOIN users u ON u.id = i.user_id
             WHERE i.id = ?'
        );
        $stmt->execute([(int) $args['id']]);
        $item = $stmt->fetch();

        if (!$item) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }
        return $this->json($response, ['item' => $item], 200);
    }

    // POST /api/items   (JWT)
    public function store(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('user_id');   // set by JwtMiddleware
        $data   = (array) $request->getParsedBody();

        $errors = $this->validate($data);
        if ($errors) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $pdo  = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO items (user_id, title, description, category, type, location, status, date_reported)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            trim($data['title']),
            trim($data['description'] ?? ''),
            trim($data['category']),
            $data['type'],
            trim($data['location']),
            !empty($data['status']) ? $data['status'] : 'open',
            !empty($data['date_reported']) ? $data['date_reported'] : date('Y-m-d'),
        ]);

        return $this->fetchOne($response, (int) $pdo->lastInsertId(), 201);
    }

    // PUT /api/items/{id}   (JWT, owner only)
    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $id     = (int) $args['id'];
        $pdo    = Database::pdo();

        // Load the existing row to check existence + ownership
        $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }
        if ((int) $existing['user_id'] !== $userId) {
            return $this->json($response, ['error' => 'You can only edit your own items.'], 403);
        }

        $data   = (array) $request->getParsedBody();
        $errors = $this->validate($data);
        if ($errors) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $stmt = $pdo->prepare(
            'UPDATE items
             SET title = ?, description = ?, category = ?, type = ?, location = ?, status = ?, date_reported = ?
             WHERE id = ?'
        );
        $stmt->execute([
            trim($data['title']),
            trim($data['description'] ?? ''),
            trim($data['category']),
            $data['type'],
            trim($data['location']),
            // keep the existing value when the field is omitted
            !empty($data['status']) ? $data['status'] : $existing['status'],
            !empty($data['date_reported']) ? $data['date_reported'] : $existing['date_reported'],
            $id,
        ]);

        return $this->fetchOne($response, $id, 200);
    }

    // DELETE /api/items/{id}   (JWT, owner only)
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $id     = (int) $args['id'];
        $pdo    = Database::pdo();

        $stmt = $pdo->prepare('SELECT user_id, image_path FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }
        if ((int) $row['user_id'] !== $userId) {
            return $this->json($response, ['error' => 'You can only delete your own items.'], 403);
        }

        $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);

        // best-effort: remove the item's uploaded image file too
        if (!empty($row['image_path'])) {
            $this->deleteImageFile($row['image_path']);
        }
        return $response->withStatus(204);   // No Content
    }

    // POST /api/items/{id}/image   (JWT, owner only) — upload/replace the item photo
    public function uploadImage(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $id     = (int) $args['id'];
        $pdo    = Database::pdo();

        $stmt = $pdo->prepare('SELECT user_id, image_path FROM items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            return $this->json($response, ['error' => 'Item not found.'], 404);
        }
        if ((int) $item['user_id'] !== $userId) {
            return $this->json($response, ['error' => 'You can only add a photo to your own item.'], 403);
        }

        // The file is sent as multipart/form-data under the field name "image"
        $file = $request->getUploadedFiles()['image'] ?? null;
        if (!$file) {
            return $this->json($response, ['errors' => ['image' => 'Please choose an image file.']], 422);
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $tooBig = in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            return $this->json($response, ['errors' => ['image' =>
                $tooBig ? 'The image is too large (max 2 MB).' : 'The image upload failed. Please try again.']], 422);
        }
        if ((int) $file->getSize() > self::MAX_IMAGE_BYTES) {
            return $this->json($response, ['errors' => ['image' => 'Image must be 2 MB or smaller.']], 422);
        }

        // Read the bytes and verify it is really an image (not just a renamed file)
        try {
            $contents = (string) $file->getStream();
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => 'Could not read the uploaded image.'], 500);
        }
        // Defense-in-depth: verify the real byte length too (getSize() can be null)
        if (strlen($contents) > self::MAX_IMAGE_BYTES) {
            return $this->json($response, ['errors' => ['image' => 'Image must be 2 MB or smaller.']], 422);
        }
        $info = @getimagesizefromstring($contents);
        if ($info === false || !isset(self::IMAGE_TYPES[$info['mime'] ?? ''])) {
            return $this->json($response, ['errors' => ['image' => 'Allowed image types: JPG, PNG, WEBP, GIF.']], 422);
        }
        $ext = self::IMAGE_TYPES[$info['mime']];

        // Save the file under the public /uploads folder with a safe random name
        try {
            $dir = $this->uploadsDir();
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException('Cannot create uploads directory.');
            }
            $filename = 'item' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (@file_put_contents($dir . '/' . $filename, $contents) === false) {
                throw new \RuntimeException('Cannot write image file.');
            }
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => 'Could not save the image. Please try again.'], 500);
        }

        // Replace any previous image, then store the new path
        if (!empty($item['image_path'])) {
            $this->deleteImageFile($item['image_path']);
        }
        $pdo->prepare('UPDATE items SET image_path = ? WHERE id = ?')->execute(['/uploads/' . $filename, $id]);

        return $this->fetchOne($response, $id, 200);
    }

    // GET /api/me/items   (JWT) — items posted by the logged-in user
    public function mine(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, (SELECT COUNT(*) FROM claims c WHERE c.item_id = i.id) AS claim_count
             FROM items i WHERE i.user_id = ? ORDER BY i.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $this->json($response, ['items' => $stmt->fetchAll()], 200);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /** Validate item input; returns an array of field => message (empty if valid). */
    private function validate(array $data): array
    {
        $errors   = [];
        $title    = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? '');
        $type     = trim($data['type'] ?? '');
        $location = trim($data['location'] ?? '');

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        } elseif (mb_strlen($title) > 150) {
            $errors['title'] = 'Title must be 150 characters or fewer.';
        }
        if ($category === '') {
            $errors['category'] = 'Category is required.';
        }
        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = "Type must be 'lost' or 'found'.";
        }
        if ($location === '') {
            $errors['location'] = 'Location is required.';
        }
        if (!empty($data['status']) && !in_array($data['status'], self::STATUSES, true)) {
            $errors['status'] = 'Status must be open, claimed or resolved.';
        }
        if (!empty($data['date_reported'])) {
            $d = \DateTime::createFromFormat('Y-m-d', $data['date_reported']);
            if (!$d || $d->format('Y-m-d') !== $data['date_reported']) {
                $errors['date_reported'] = 'Date must be in YYYY-MM-DD format.';
            }
        }
        return $errors;
    }

    /** Fetch a single item (with poster name) and return it as JSON. */
    private function fetchOne(Response $response, int $id, int $status): Response
    {
        $stmt = Database::pdo()->prepare(
            'SELECT i.*, u.name AS poster_name
             FROM items i JOIN users u ON u.id = i.user_id
             WHERE i.id = ?'
        );
        $stmt->execute([$id]);
        return $this->json($response, ['item' => $stmt->fetch()], $status);
    }

    /** Absolute path to the public uploads directory. */
    private function uploadsDir(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? (__DIR__ . '/../../public');
        return rtrim($root, "/\\") . '/uploads';
    }

    /** Delete a previously stored image (best-effort, stays inside the uploads dir). */
    private function deleteImageFile(string $imagePath): void
    {
        $full = $this->uploadsDir() . '/' . basename($imagePath);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function json(Response $r, array $data, int $status = 200): Response
    {
        $r->getBody()->write(json_encode($data));
        return $r->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
