<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\PdfHelper;
use App\Models\ClubCustomizationModel;
use App\Helpers\Database;

/**
 * Competition certificate/diploma PDF generation.
 *
 * GET /competitions/:id/certificates         — preview list
 * GET /competitions/:id/certificates.pdf     — download PDF (top 3 per category)
 * GET /competitions/:id/certificates.pdf?top=N — top N places
 */
class CertificateController extends BaseController
{
    private \PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad', 'sędzia']);
        $this->db = Database::getInstance();
    }

    public function preview(string $id): void
    {
        $comp  = $this->getCompetition((int)$id);
        $certs = $this->buildCertificates((int)$id);

        $this->render('competitions/certificates_preview', [
            'title'        => 'Dyplomy: ' . $comp['name'],
            'competition'  => $comp,
            'certificates' => $certs,
        ]);
    }

    public function pdf(string $id): void
    {
        $comp  = $this->getCompetition((int)$id);
        $certs = $this->buildCertificates((int)$id, (int)($_GET['top'] ?? 3));

        if (empty($certs)) {
            \App\Helpers\Session::flash('warning', 'Brak wyników do generowania dyplomów.');
            $this->redirect('competitions/' . (int)$id);
        }

        $clubId   = ClubContext::current() ?? (int)($comp['club_id'] ?? 1);
        $branding = ClubCustomizationModel::getForCurrentClub();
        $clubName = $this->getClubName($clubId);

        $html = $this->renderToString('pdf/certificate', [
            'competition'  => $comp,
            'certificates' => $certs,
            'clubName'     => $clubName,
        ]);

        $safe = preg_replace('/[^a-z0-9_-]/', '_', strtolower($comp['name']));
        PdfHelper::send($html, 'dyplomy_' . $safe . '_' . date('Ymd') . '.pdf', 'A5-L', false);
    }

    private function buildCertificates(int $compId, int $topN = 3): array
    {
        // Get top N places per event/category
        try {
            $stmt = $this->db->prepare(
                "SELECT ce.id AS entry_id, m.first_name, m.last_name,
                        ce.category, ce.final_place AS place
                 FROM competition_entries ce
                 JOIN members m ON m.id = ce.member_id
                 WHERE ce.competition_id = ?
                   AND ce.final_place IS NOT NULL
                   AND ce.final_place BETWEEN 1 AND ?
                 ORDER BY ce.category, ce.final_place"
            );
            $stmt->execute([$compId, $topN]);
            return $stmt->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getCompetition(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM competitions WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $comp = $stmt->fetch();
        if (!$comp) {
            \App\Helpers\Session::flash('error', 'Zawody nie istnieją.');
            header('Location: ' . url('competitions'));
            exit;
        }
        return $comp;
    }

    private function getClubName(int $clubId): string
    {
        try {
            $stmt = $this->db->prepare("SELECT name FROM clubs WHERE id=?");
            $stmt->execute([$clubId]);
            return $stmt->fetchColumn() ?: 'Klub Strzelecki';
        } catch (\Throwable) { return 'Klub Strzelecki'; }
    }
}
