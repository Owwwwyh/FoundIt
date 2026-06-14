<?php
// ----------------------------------------------------------------------
// AiLocationService — the "AI" module, implemented as a LOCAL probabilistic
// scorer (no external service required). Given an item, it ranks the places
// the reporting user has recently been to and suggests where the item is
// most likely to be found, with a human-readable reason for each guess.
//
// Each candidate place is scored on four signals:
//   1. temporal proximity  — how close, in time, the user's visits to that
//      place were to when this item was reported
//   2. visit frequency     — how often the user has posted from that place
//   3. category affinity   — keyword overlap between this item and the items
//      the user previously posted at that place
//   4. distance clustering — (optional) Haversine distance between this item's
//      map point and the place, when both have coordinates
//
// The weighted blend (0–100) plus reasons are stored back onto the item row
// as JSON in `items.ai_location_hints`.
//
// If an OpenAI key is configured (OPENAI_API_KEY) the scorer additionally asks
// the model for a short natural-language summary — but this is pure enrichment:
// any failure (no key, no network, bad response) falls back gracefully to a
// locally generated summary, so the feature always works offline.
// ----------------------------------------------------------------------

namespace App\Services;

use App\Database;

class AiLocationService
{
    // Blend weights for the four signals (sum = 1.0).
    private const W_TEMPORAL  = 0.30;
    private const W_FREQUENCY = 0.25;
    private const W_CATEGORY  = 0.30;
    private const W_DISTANCE  = 0.15;

    private const MAX_HINTS     = 4;
    private const TEMPORAL_DAYS = 30;   // visits older than this add little temporal weight
    private const CLUSTER_KM    = 1.5;  // distance over which the proximity bonus fades out

    /**
     * Generate hints for an item, persist them, and return the hint payload.
     * Always returns an array (possibly with an empty `hints` list).
     */
    public function generateForItem(int $itemId): array
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) {
            return ['hints' => [], 'summary' => '', 'source' => 'none'];
        }

        $payload = $this->score($item);

        // Persist back onto the item document (JSON column).
        $pdo->prepare('UPDATE items SET ai_location_hints = ? WHERE id = ?')
            ->execute([json_encode($payload), $itemId]);

        return $payload;
    }

    /**
     * Pure scoring step (no DB writes other than the read of the user's history).
     * Exposed separately so it can be unit-reasoned about / reused.
     */
    public function score(array $item): array
    {
        $pdo = Database::pdo();

        // The user's other items = their "recent places" history.
        $stmt = $pdo->prepare(
            'SELECT title, description, category, location, latitude, longitude, date_reported
             FROM items WHERE user_id = ? AND id <> ? ORDER BY date_reported DESC'
        );
        $stmt->execute([(int) $item['user_id'], (int) $item['id']]);
        $history = $stmt->fetchAll();

        $usedFallback = false;
        if (count($history) === 0) {
            // Cold start: borrow the busiest campus-wide places so we can still
            // offer something useful for a brand-new user.
            $history = $this->campusFallbackHistory((int) $item['id']);
            $usedFallback = true;
        }

        // Group the history rows by a normalized place name.
        $places = [];
        foreach ($history as $row) {
            $key = $this->normalizePlace($row['location']);
            if ($key === '') {
                continue;
            }
            if (!isset($places[$key])) {
                $places[$key] = [
                    'label'  => trim($row['location']),
                    'visits' => 0,
                    'dates'  => [],
                    'text'   => '',
                    'lat'    => null,
                    'lng'    => null,
                ];
            }
            $places[$key]['visits']++;
            $places[$key]['dates'][] = $row['date_reported'];
            $places[$key]['text']   .= ' ' . $row['category'] . ' ' . $row['title'] . ' ' . ($row['description'] ?? '');
            if ($places[$key]['lat'] === null && $row['latitude'] !== null) {
                $places[$key]['lat'] = (float) $row['latitude'];
                $places[$key]['lng'] = (float) $row['longitude'];
            }
        }

        $maxVisits  = max(1, ...array_map(static fn ($p) => $p['visits'], $places ?: [['visits' => 1]]));
        $itemWords  = $this->keywords($item['category'] . ' ' . $item['title'] . ' ' . ($item['description'] ?? ''));
        $hasItemGeo = $item['latitude'] !== null && $item['longitude'] !== null;

        $hints = [];
        foreach ($places as $place) {
            $reasons = [];

            // 1. Temporal proximity — closest visit to the report date.
            $minDays = null;
            foreach ($place['dates'] as $d) {
                $days = $this->daysApart($item['date_reported'], $d);
                if ($days !== null && ($minDays === null || $days < $minDays)) {
                    $minDays = $days;
                }
            }
            $temporal = $minDays === null ? 0.0 : max(0.0, 1.0 - ($minDays / self::TEMPORAL_DAYS));
            if ($temporal > 0.5) {
                $reasons[] = $minDays <= 1 ? 'You were here around the same day' : "Visited about {$minDays} day(s) apart";
            }

            // 2. Visit frequency.
            $frequency = $place['visits'] / $maxVisits;
            if ($place['visits'] >= 2) {
                $reasons[] = 'One of your frequent spots (' . $place['visits'] . ' reports)';
            }

            // 3. Category / keyword affinity.
            $placeWords = $this->keywords($place['text']);
            $shared     = array_values(array_intersect($itemWords, $placeWords));
            $category   = count($itemWords) === 0 ? 0.0 : min(1.0, count($shared) / 3.0);
            if ($shared) {
                $reasons[] = 'Similar items here (' . implode(', ', array_slice($shared, 0, 2)) . ')';
            }

            // 4. Distance clustering (optional — needs coordinates on both sides).
            $distance = 0.0;
            $hasDistance = false;
            if ($hasItemGeo && $place['lat'] !== null) {
                $km = $this->haversineKm(
                    (float) $item['latitude'], (float) $item['longitude'],
                    $place['lat'], $place['lng']
                );
                $distance = max(0.0, 1.0 - ($km / self::CLUSTER_KM));
                $hasDistance = true;
                if ($distance > 0.5) {
                    $reasons[] = 'Close to where you marked it (~' . $this->fmtKm($km) . ')';
                }
            }

            // Weighted blend. When coordinates are missing the distance weight is
            // redistributed across the other three signals so scores stay comparable.
            if ($hasDistance) {
                $score = self::W_TEMPORAL * $temporal
                       + self::W_FREQUENCY * $frequency
                       + self::W_CATEGORY * $category
                       + self::W_DISTANCE * $distance;
            } else {
                $scale = 1 / (self::W_TEMPORAL + self::W_FREQUENCY + self::W_CATEGORY);
                $score = ($scale) * (self::W_TEMPORAL * $temporal
                       + self::W_FREQUENCY * $frequency
                       + self::W_CATEGORY * $category);
            }

            if ($reasons === []) {
                $reasons[] = 'A place you have used before';
            }

            $hints[] = [
                'location'  => $place['label'],
                'score'     => (int) round($score * 100),
                'latitude'  => $place['lat'],
                'longitude' => $place['lng'],
                'reasons'   => array_slice($reasons, 0, 3),
            ];
        }

        // Best score first; keep the strongest few.
        usort($hints, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $hints = array_slice($hints, 0, self::MAX_HINTS);

        $summary = $this->localSummary($item, $hints, $usedFallback);
        $source  = 'local';

        // Optional OpenAI enrichment — never blocks, never throws past here.
        $enriched = $this->enrichWithOpenAI($item, $hints);
        if ($enriched !== null && $enriched !== '') {
            $summary = $enriched;
            $source  = 'openai';
        }

        return [
            'hints'        => $hints,
            'summary'      => $summary,
            'source'       => $source,
            'used_history' => !$usedFallback,
            'generated_at' => date('c'),
        ];
    }

    // ---------------------------------------------------------------
    // Optional OpenAI enrichment
    // ---------------------------------------------------------------
    private function enrichWithOpenAI(array $item, array $hints): ?string
    {
        $key = trim($_ENV['OPENAI_API_KEY'] ?? '');
        if ($key === '' || $hints === [] || !function_exists('curl_init')) {
            return null;   // graceful fallback: no key / nothing to enrich
        }

        $model = trim($_ENV['OPENAI_MODEL'] ?? '') ?: 'gpt-4o-mini';
        $places = implode('; ', array_map(
            static fn ($h) => $h['location'] . ' (' . $h['score'] . '%)',
            $hints
        ));

        $prompt = "A campus lost & found item was reported.\n"
            . 'Type: ' . $item['type'] . "\n"
            . 'Title: ' . $item['title'] . "\n"
            . 'Category: ' . $item['category'] . "\n"
            . 'Stated location: ' . $item['location'] . "\n"
            . 'A local model ranked these likely places: ' . $places . ".\n"
            . 'In ONE short, friendly sentence (max 30 words), advise the user where to look first. '
            . 'Do not invent places that are not in the list.';

        $body = json_encode([
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a concise campus lost-and-found assistant.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature' => 0.4,
            'max_tokens'  => 80,
        ]);

        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $key,
                ],
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => 8,
            ]);
            $raw  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($raw === false || $code < 200 || $code >= 300) {
                return null;
            }
            $data = json_decode($raw, true);
            $text = $data['choices'][0]['message']['content'] ?? '';
            $text = trim((string) $text);
            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            error_log('FoundIt OpenAI enrichment skipped: ' . $e->getMessage());
            return null;
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /** Borrow the most common campus locations as a cold-start history. */
    private function campusFallbackHistory(int $excludeId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT title, description, category, location, latitude, longitude, date_reported
             FROM items WHERE id <> ? ORDER BY created_at DESC LIMIT 50'
        );
        $stmt->execute([$excludeId]);
        return $stmt->fetchAll();
    }

    private function localSummary(array $item, array $hints, bool $usedFallback): string
    {
        if ($hints === []) {
            return 'Not enough history yet to suggest likely locations — add a few more reports and try again.';
        }
        $top = $hints[0]['location'];
        if ($usedFallback) {
            return 'Based on busy campus spots, start your search at ' . $top
                . '. Report a few items to get personalised suggestions.';
        }
        $second = $hints[1]['location'] ?? null;
        return $second
            ? 'Based on your recent activity, check ' . $top . ' first, then ' . $second . '.'
            : 'Based on your recent activity, ' . $top . ' is the most likely place to check first.';
    }

    private function normalizePlace(?string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
    }

    /** Reused keyword tokenizer (mirrors ItemController's matcher). */
    private function keywords(?string $text): array
    {
        $text  = mb_strtolower((string) $text);
        $words = preg_split('/[^a-z0-9]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        static $stop = ['the', 'and', 'for', 'with', 'near', 'around', 'somewhere', 'some',
                        'this', 'that', 'has', 'have', 'had', 'was', 'from', 'your', 'you',
                        'its', 'found', 'lost', 'left', 'item', 'about', 'other'];

        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 3 || in_array($w, $stop, true)) {
                continue;
            }
            $out[$w] = true;
        }
        return array_keys($out);
    }

    private function daysApart(?string $d1, ?string $d2): ?int
    {
        if (!$d1 || !$d2) {
            return null;
        }
        try {
            $a = new \DateTime($d1);
            $b = new \DateTime($d2);
        } catch (\Throwable $e) {
            return null;
        }
        return (int) floor(abs($a->getTimestamp() - $b->getTimestamp()) / 86400);
    }

    /** Great-circle distance in kilometres (Haversine). */
    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function fmtKm(float $km): string
    {
        return $km < 1 ? round($km * 1000) . ' m' : round($km, 1) . ' km';
    }
}
