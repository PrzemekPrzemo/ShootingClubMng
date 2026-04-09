<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;

/**
 * Kontroler narzędzi RODO/GDPR.
 *
 * GET  /gdpr                        — informacje o przetwarzaniu danych
 * POST /members/:id/gdpr/export     — eksport danych zawodnika (art. 20 RODO) → ZIP/JSON
 * POST /members/:id/gdpr/anonymize  — anonimizacja danych osobowych
 * GET  /members/:id/gdpr/consents   — widok zarządzania zgodami
 * POST /members/:id/gdpr/consents   — zapisz/odwołaj zgody
 */
class GdprController extends BaseController
{
    private MemberModel $memberModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->memberModel = new MemberModel();
    }

    // ── Eksport danych (art. 20 RODO) ────────────────────────────────

    public function exportData(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        Csrf::verify();

        $member = $this->getMember((int)$id);
        $db     = Database::pdo();

        // Collect all personal data
        $export = [
            'export_date'  => date('Y-m-d H:i:s'),
            'gdpr_article' => 'Art. 20 RODO — Prawo do przenoszenia danych',
            'member'       => $this->sanitizeForExport($member),
        ];

        // Disciplines
        $stmt = $db->prepare("SELECT d.name, md.class, md.joined_at FROM member_disciplines md JOIN disciplines d ON d.id = md.discipline_id WHERE md.member_id = ?");
        $stmt->execute([(int)$id]);
        $export['disciplines'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Licenses
        $stmt = $db->prepare("SELECT license_number, valid_until, type FROM licenses WHERE member_id = ? ORDER BY valid_until DESC");
        $stmt->execute([(int)$id]);
        $export['licenses'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Payments
        $stmt = $db->prepare("SELECT payment_date, amount, payment_method, notes FROM payments WHERE member_id = ? ORDER BY payment_date DESC");
        $stmt->execute([(int)$id]);
        $export['payments'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Competition entries
        $stmt = $db->prepare("
            SELECT c.name AS competition, c.competition_date, ce.status, ce.class, ce.start_fee_paid
            FROM competition_entries ce
            JOIN competitions c ON c.id = ce.competition_id
            WHERE ce.member_id = ?
            ORDER BY c.competition_date DESC
        ");
        $stmt->execute([(int)$id]);
        $export['competition_entries'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Medical exams (type and validity only — no file content)
        $stmt = $db->prepare("
            SELECT et.name AS exam_type, me.valid_from, me.valid_until, me.status
            FROM member_medical_exams me
            JOIN medical_exam_types et ON et.id = me.exam_type_id
            WHERE me.member_id = ?
            ORDER BY me.valid_until DESC
        ");
        $stmt->execute([(int)$id]);
        $export['medical_exams'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Consents
        $stmt = $db->prepare("SELECT consent_type, granted_at, revoked_at FROM member_consents WHERE member_id = ? ORDER BY created_at");
        $stmt->execute([(int)$id]);
        $export['consents'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Mark export date in members table
        $db->prepare("UPDATE members SET gdpr_export_at = NOW() WHERE id = ?")->execute([(int)$id]);

        // Send as JSON file
        $filename = 'dane_osobowe_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $member['last_name'] . '_' . $member['first_name']) . '_' . date('Ymd') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ── Anonimizacja danych (art. 17 RODO — prawo do zapomnienia) ────

    public function anonymize(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        Csrf::verify();

        $member = $this->getMember((int)$id);
        $db     = Database::pdo();

        // Anonimizuj dane — zachowaj rekordy statystyczne (zawody, płatności), usuń dane osobowe
        $anonName  = 'ANONIMIZOWANY_' . $member['member_number'];
        $db->prepare("
            UPDATE members SET
                first_name          = 'USUNIĘTO',
                last_name           = ?   ,
                pesel               = NULL,
                birth_date          = NULL,
                gender              = NULL,
                email               = NULL,
                phone               = NULL,
                address_street      = NULL,
                address_city        = NULL,
                address_postal      = NULL,
                photo_path          = NULL,
                notes               = NULL,
                firearm_permit_number = NULL,
                status              = 'wykreslony',
                anonymized_at       = NOW()
            WHERE id = ?
        ")->execute([$anonName, (int)$id]);

        // Usuń zdjęcie z dysku
        if (!empty($member['photo_path'])) {
            $photoPath = ROOT_PATH . '/storage/photos/' . $member['photo_path'];
            if (file_exists($photoPath)) @unlink($photoPath);
        }

        // Usuń pliki badań lekarskich
        $exams = $db->prepare("SELECT file_path FROM member_medical_exams WHERE member_id = ? AND file_path IS NOT NULL");
        $exams->execute([(int)$id]);
        foreach ($exams->fetchAll(\PDO::FETCH_COLUMN) as $examFile) {
            $path = ROOT_PATH . '/storage/medical/' . $examFile;
            if (file_exists($path)) @unlink($path);
        }
        $db->prepare("UPDATE member_medical_exams SET file_path = NULL WHERE member_id = ?")->execute([(int)$id]);

        // Usuń dane z tabeli zgód (zachowaj typ zgody, usuń IP)
        $db->prepare("UPDATE member_consents SET ip_address = NULL, notes = NULL WHERE member_id = ?")->execute([(int)$id]);

        Session::flash('success', 'Dane zawodnika zostały zanonimizowane zgodnie z art. 17 RODO.');
        $this->redirect('members/' . (int)$id);
    }

    // ── Zarządzanie zgodami ───────────────────────────────────────────

    public function consents(string $id): void
    {
        $member = $this->getMember((int)$id);
        $db     = Database::pdo();

        $stmt = $db->prepare("SELECT * FROM member_consents WHERE member_id = ? ORDER BY consent_type");
        $stmt->execute([(int)$id]);
        $consents = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $c) {
            $consents[$c['consent_type']] = $c;
        }

        $this->render('gdpr/consents', [
            'title'    => 'Zgody RODO — ' . e($member['last_name']) . ' ' . e($member['first_name']),
            'member'   => $member,
            'consents' => $consents,
        ]);
    }

    public function saveConsents(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $member = $this->getMember((int)$id);
        $db     = Database::pdo();

        $types   = ['data_processing', 'marketing', 'photo', 'medical_data'];
        $granted = $_POST['consent'] ?? [];
        $ip      = $_SERVER['REMOTE_ADDR'] ?? null;

        foreach ($types as $type) {
            // Check existing
            $existing = $db->prepare("SELECT id, granted_at, revoked_at FROM member_consents WHERE member_id = ? AND consent_type = ? LIMIT 1");
            $existing->execute([(int)$id, $type]);
            $row = $existing->fetch();

            $isGranted = in_array($type, $granted);

            if (!$row) {
                $db->prepare("INSERT INTO member_consents (member_id, consent_type, granted_at, revoked_at, ip_address) VALUES (?, ?, ?, NULL, ?)")
                   ->execute([(int)$id, $type, $isGranted ? date('Y-m-d H:i:s') : null, $ip]);
            } else {
                if ($isGranted && !$row['granted_at']) {
                    $db->prepare("UPDATE member_consents SET granted_at = NOW(), revoked_at = NULL, ip_address = ? WHERE id = ?")
                       ->execute([$ip, $row['id']]);
                } elseif (!$isGranted && $row['granted_at'] && !$row['revoked_at']) {
                    $db->prepare("UPDATE member_consents SET revoked_at = NOW() WHERE id = ?")
                       ->execute([$row['id']]);
                }
            }
        }

        Session::flash('success', 'Zgody RODO zapisane.');
        $this->redirect('members/' . (int)$id . '/gdpr/consents');
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function getMember(int $id): array
    {
        $member = $this->memberModel->findById($id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }
        return $member;
    }

    private function sanitizeForExport(array $member): array
    {
        // Exclude internal fields from export
        unset($member['password'], $member['photo_path']);
        return $member;
    }
}
