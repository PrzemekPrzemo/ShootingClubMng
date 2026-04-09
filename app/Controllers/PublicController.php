<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Models\ClubModel;
use App\Models\CompetitionModel;
use App\Models\SettingModel;

/**
 * Publiczne strony wyników (bez logowania).
 *
 * GET /pub                          — lista wszystkich klubów (landing)
 * GET /pub/:club_slug/competitions  — lista zawodów klubu
 * GET /pub/:club_slug/competitions/:id — wyniki zawodów
 */
class PublicController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // No requireLogin — public access
    }

    public function clubList(): void
    {
        $clubModel = new ClubModel();
        $db = Database::pdo();

        // Load clubs that have at least one public competition
        $stmt = $db->query(
            "SELECT DISTINCT c.id, c.name, c.short_name, cc.subdomain,
                    cc.primary_color, cc.logo_path,
                    (SELECT COUNT(*) FROM competitions cm WHERE cm.club_id = c.id AND cm.is_public = 1 AND cm.status = 'zakonczone') AS public_competitions
             FROM clubs c
             LEFT JOIN club_customization cc ON cc.club_id = c.id
             WHERE c.is_active = 1
             ORDER BY c.name"
        );
        $clubs = $stmt->fetchAll();

        $this->view->setLayout('public');
        $this->render('public/club_list', [
            'title' => 'Wyniki zawodów',
            'clubs' => $clubs,
        ]);
    }

    public function clubCompetitions(string $slug): void
    {
        $club = $this->getClubBySlug($slug);

        $db   = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id, name, competition_date, location, status
             FROM competitions
             WHERE club_id = ? AND is_public = 1 AND status IN ('zakonczone','zamkniete')
             ORDER BY competition_date DESC
             LIMIT 100"
        );
        $stmt->execute([$club['id']]);
        $competitions = $stmt->fetchAll();

        $this->view->setLayout('public');
        $this->render('public/competitions', [
            'title'        => 'Zawody — ' . $club['name'],
            'club'         => $club,
            'competitions' => $competitions,
            'slug'         => $slug,
        ]);
    }

    public function competitionResults(string $slug, string $id): void
    {
        $club        = $this->getClubBySlug($slug);
        $db          = Database::pdo();

        // Load competition (must belong to club and be public)
        $stmt = $db->prepare(
            "SELECT * FROM competitions WHERE id = ? AND club_id = ? AND is_public = 1 LIMIT 1"
        );
        $stmt->execute([(int)$id, $club['id']]);
        $competition = $stmt->fetch();

        if (!$competition) {
            $this->view->setLayout('public');
            $this->render('public/not_found', ['title' => 'Nie znaleziono', 'club' => $club, 'slug' => $slug]);
            return;
        }

        $compModel = new CompetitionModel();
        $rankings  = $compModel->calcRankings((int)$id);

        $this->view->setLayout('public');
        $this->render('public/results', [
            'title'       => $competition['name'] . ' — Wyniki',
            'club'        => $club,
            'competition' => $competition,
            'rankings'    => $rankings,
            'slug'        => $slug,
        ]);
    }

    private function getClubBySlug(string $slug): array
    {
        $db   = Database::pdo();

        // Try subdomain first
        $stmt = $db->prepare(
            "SELECT c.*, cc.subdomain, cc.primary_color, cc.logo_path, cc.navbar_bg
             FROM clubs c
             LEFT JOIN club_customization cc ON cc.club_id = c.id
             WHERE (cc.subdomain = ? OR c.id = ?) AND c.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$slug, is_numeric($slug) ? (int)$slug : 0]);
        $club = $stmt->fetch();

        if (!$club) {
            $this->view->setLayout('public');
            $this->render('public/not_found', ['title' => 'Klub nie istnieje', 'club' => [], 'slug' => $slug]);
            exit;
        }

        return $club;
    }
}
