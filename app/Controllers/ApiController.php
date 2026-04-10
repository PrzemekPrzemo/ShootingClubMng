<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Models\ClubSettingsModel;

/**
 * Read-only JSON API — v1.
 *
 * Authentication: API key in X-API-Key header or ?api_key= query param.
 * The API key is stored in club_settings: key = 'api_key', per-club.
 * A special global key is stored in the settings table as 'global_api_key'.
 *
 * Endpoints:
 *   GET /api/v1/clubs/:slug/competitions   — upcoming competitions for a club
 *   GET /api/v1/competitions/:id/results   — competition results (public flag check)
 *   GET /api/v1/clubs/:slug/members/count  — member count (auth required)
 */
class ApiController extends BaseController
{
    private \PDO $db;

    public function __construct()
    {
        // No session needed for API — skip parent constructor's session start
        $this->db = Database::pdo();
    }

    // ── Competitions for a club ───────────────────────────────────────────────

    public function clubCompetitions(string $slug): void
    {
        $club = $this->resolveClub($slug);
        if (!$club) {
            $this->jsonError(404, 'Klub nie znaleziony.');
        }

        $this->requireApiKey((int)$club['id']);

        $stmt = $this->db->prepare("
            SELECT c.id, c.name, c.competition_date, c.location,
                   c.status, c.max_entries, c.entry_fee,
                   d.name AS discipline_name,
                   (SELECT COUNT(*) FROM competition_entries WHERE competition_id = c.id) AS entries_count
            FROM competitions c
            LEFT JOIN disciplines d ON d.id = c.discipline_id
            WHERE c.club_id = ?
              AND c.competition_date >= CURDATE()
              AND c.status IN ('otwarte','planowane')
            ORDER BY c.competition_date ASC
            LIMIT 50
        ");
        $stmt->execute([(int)$club['id']]);
        $rows = $stmt->fetchAll();

        $this->json([
            'club'         => ['id' => $club['id'], 'name' => $club['name']],
            'competitions' => $rows,
            'count'        => count($rows),
        ]);
    }

    // ── Competition results ───────────────────────────────────────────────────

    public function competitionResults(string $id): void
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.name, c.competition_date, c.status, c.is_public,
                   c.club_id, cl.name AS club_name
            FROM competitions c
            LEFT JOIN clubs cl ON cl.id = c.club_id
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        $comp = $stmt->fetch();

        if (!$comp) {
            $this->jsonError(404, 'Zawody nie znalezione.');
        }

        // Public competitions don't need an API key;
        // non-public require the club's API key
        if (empty($comp['is_public'])) {
            $this->requireApiKey((int)$comp['club_id']);
        }

        // Series results
        $stmt = $this->db->prepare("
            SELECT csr.position, csr.total_score, csr.x_count,
                   m.first_name, m.last_name, m.member_number,
                   cs.name AS series_name
            FROM competition_series_results csr
            JOIN competition_entries ce ON ce.id = csr.entry_id
            JOIN members m ON m.id = ce.member_id
            JOIN competition_series cs ON cs.id = csr.series_id
            WHERE cs.competition_id = ?
            ORDER BY csr.position ASC
            LIMIT 200
        ");
        $stmt->execute([(int)$id]);
        $results = $stmt->fetchAll();

        $this->json([
            'competition' => [
                'id'               => $comp['id'],
                'name'             => $comp['name'],
                'competition_date' => $comp['competition_date'],
                'status'           => $comp['status'],
                'club'             => $comp['club_name'],
            ],
            'results' => $results,
            'count'   => count($results),
        ]);
    }

    // ── Member count ─────────────────────────────────────────────────────────

    public function clubMemberCount(string $slug): void
    {
        $club = $this->resolveClub($slug);
        if (!$club) {
            $this->jsonError(404, 'Klub nie znaleziony.');
        }

        $this->requireApiKey((int)$club['id']);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'");
        $stmt->execute([(int)$club['id']]);
        $count = (int)$stmt->fetchColumn();

        $this->json([
            'club'          => ['id' => $club['id'], 'name' => $club['name']],
            'active_members' => $count,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveClub(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT cl.id, cl.name, cc.subdomain FROM clubs cl
             LEFT JOIN club_customization cc ON cc.club_id = cl.id
             WHERE cc.subdomain = ? OR cl.short_name = ?
             LIMIT 1"
        );
        $stmt->execute([$slug, $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function requireApiKey(int $clubId): void
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');

        if (!$key) {
            $this->jsonError(401, 'Brak klucza API. Podaj X-API-Key w nagłówku lub api_key w URL.');
        }

        // Club-level key
        try {
            $settings = new ClubSettingsModel();
            $clubKey  = $settings->get($clubId, 'api_key', '');
            if ($clubKey && hash_equals($clubKey, $key)) return;
        } catch (\Throwable) {}

        // Global key (super-admin set)
        try {
            $sm = new \App\Models\SettingModel();
            $globalKey = $sm->get('global_api_key', '');
            if ($globalKey && hash_equals($globalKey, $key)) return;
        } catch (\Throwable) {}

        $this->jsonError(403, 'Nieprawidłowy klucz API.');
    }

    private function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache, max-age=60');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function jsonError(int $status, string $message): never
    {
        $this->json(['error' => $message], $status);
    }

    /** Override: no session needed for API endpoints */
    protected function requireLogin(): void {}
}
