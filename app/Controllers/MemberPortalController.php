<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Helpers\View;
use App\Helpers\Database;
use App\Models\MemberModel;
use App\Models\MemberPortalModel;
use App\Models\MedicalExamTypeModel;
use App\Models\NotificationModel;
use App\Models\CompetitionModel;

/**
 * Member self-service portal. All actions require member login.
 * Uses portal layout (not the staff main layout).
 */
class MemberPortalController
{
    private View $view;
    private MemberPortalModel $portalModel;

    public function __construct()
    {
        Session::start();
        MemberAuth::requireLogin();

        // After login, if password must change, redirect to change-password
        // (except for the change-password action itself)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (MemberAuth::mustChangePassword() && !str_contains($uri, '/portal/change-password')) {
            header('Location: ' . url('portal/change-password'));
            exit;
        }

        $this->view        = new View();
        $this->portalModel = new MemberPortalModel();
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $memberId = MemberAuth::id();
        $year     = (int)date('Y');

        $licenses        = $this->portalModel->getMemberLicenses($memberId);
        $openComps       = $this->portalModel->getOpenCompetitions($memberId);
        $recentResults   = array_slice($this->portalModel->getMemberResults($memberId), 0, 3);
        $payments        = $this->portalModel->getFeesSummary($memberId, $year);
        $pendingFees     = array_filter($payments, fn($p) => empty($p['paid_date'] ?? ''));

        $this->render('portal/dashboard', [
            'title'            => 'Portal Zawodnika',
            'licenses'         => $licenses,
            'openCompetitions' => $openComps,
            'recentResults'    => $recentResults,
            'pendingFees'      => $pendingFees,
        ]);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    public function profile(): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, mac.name AS age_category_name
            FROM members m
            LEFT JOIN member_age_categories mac ON mac.id = m.age_category_id
            WHERE m.id = ?
        ");
        $stmt->execute([MemberAuth::id()]);
        $member = $stmt->fetch() ?: [];

        // Member disciplines
        $dStmt = $db->prepare("
            SELECT d.name FROM member_disciplines md
            JOIN disciplines d ON d.id = md.discipline_id
            WHERE md.member_id = ?
        ");
        $dStmt->execute([MemberAuth::id()]);
        $disciplines = $dStmt->fetchAll();

        $this->render('portal/profile', [
            'title'       => 'Mój profil',
            'member'      => $member,
            'disciplines' => $disciplines,
        ]);
    }

    // ── Medical Exams ─────────────────────────────────────────────────────────

    public function exams(): void
    {
        $exams     = $this->portalModel->getMemberExams(MemberAuth::id());
        $examTypes = [];
        try {
            $examTypes = (new MedicalExamTypeModel())->getActive();
        } catch (\Throwable) {}

        $this->render('portal/exams', [
            'title'     => 'Badania lekarskie',
            'exams'     => $exams,
            'examTypes' => $examTypes,
        ]);
    }

    public function uploadExam(): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();

        // File validation
        if (empty($_FILES['file']['tmp_name'])) {
            Session::flash('error', 'Nie przesłano pliku.');
            $this->redirectTo('portal/exams');
        }

        $file = $_FILES['file'];
        $allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMime, true)) {
            Session::flash('error', 'Dozwolone formaty: PDF, JPG, PNG.');
            $this->redirectTo('portal/exams');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            Session::flash('error', 'Plik przekracza limit 5 MB.');
            $this->redirectTo('portal/exams');
        }

        $storageDir = ROOT_PATH . '/storage/medical';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $ext      = match($mime) { 'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', default => 'png' };
        $filename = 'member_' . $memberId . '_exam_' . date('YmdHis') . '.' . $ext;
        $destPath = $storageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            Session::flash('error', 'Nie udało się zapisać pliku na serwerze.');
            $this->redirectTo('portal/exams');
        }

        $examDate  = $_POST['exam_date']  ?? date('Y-m-d');
        $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
        $typeId    = !empty($_POST['exam_type_id']) ? (int)$_POST['exam_type_id'] : null;

        // If valid_until not provided, try to auto-calculate from exam type
        if (!$validUntil && $typeId) {
            try {
                $db = Database::getInstance();
                $s  = $db->prepare("SELECT validity_months FROM medical_exam_types WHERE id = ?");
                $s->execute([$typeId]);
                $row = $s->fetch();
                if ($row && $row['validity_months']) {
                    $validUntil = date('Y-m-d', strtotime($examDate . ' + ' . $row['validity_months'] . ' months'));
                }
            } catch (\Throwable) {}
        }

        $db = Database::getInstance();
        try {
            $db->prepare("
                INSERT INTO member_medical_exams
                    (member_id, exam_date, valid_until, exam_type_id, file_path, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NULL, NOW())
            ")->execute([$memberId, $examDate, $validUntil, $typeId, 'medical/' . $filename]);
        } catch (\PDOException $e) {
            // Fallback without new columns if migration not run
            try {
                $db->prepare("
                    INSERT INTO member_medical_exams
                        (member_id, exam_date, valid_until, created_by, created_at)
                    VALUES (?, ?, ?, NULL, NOW())
                ")->execute([$memberId, $examDate, $validUntil]);
            } catch (\PDOException) {
                Session::flash('error', 'Błąd zapisu do bazy danych.');
                $this->redirectTo('portal/exams');
            }
        }

        // Create notification for staff
        $memberUser = MemberAuth::member();
        (new NotificationModel())->create([
            'type'              => 'exam_upload',
            'title'             => 'Nowe badanie lekarskie',
            'message'           => ($memberUser['full_name'] ?? 'Zawodnik') . ' przesłał/a zaświadczenie lekarskie.',
            'for_roles'         => 'admin,zarzad',
            'related_member_id' => $memberId,
        ]);

        Session::flash('success', 'Zaświadczenie zostało przesłane. Biuro klubu je zweryfikuje.');
        $this->redirectTo('portal/exams');
    }

    // ── Results ───────────────────────────────────────────────────────────────

    public function results(): void
    {
        $this->render('portal/results', [
            'title'   => 'Moje wyniki',
            'results' => $this->portalModel->getMemberResults(MemberAuth::id()),
        ]);
    }

    // ── Competitions ──────────────────────────────────────────────────────────

    public function competitions(): void
    {
        $memberId = MemberAuth::id();
        $this->render('portal/competitions', [
            'title'            => 'Zawody',
            'openCompetitions' => $this->portalModel->getOpenCompetitions($memberId),
            'myEntries'        => $this->portalModel->getMemberEntries($memberId),
        ]);
    }

    public function showRegister(string $id): void
    {
        $compModel   = new CompetitionModel();
        $competition = $compModel->getWithDetails((int)$id);
        if (!$competition || $competition['status'] !== 'otwarte') {
            Session::flash('error', 'Zapisy na te zawody są zamknięte.');
            $this->redirectTo('portal/competitions');
        }

        $events = [];
        try {
            $events = $compModel->getEvents((int)$id);
        } catch (\Throwable) {}

        // Check if already registered
        $memberId = MemberAuth::id();
        $db = Database::getInstance();
        $s  = $db->prepare("SELECT id FROM competition_entries WHERE competition_id = ? AND member_id = ?");
        $s->execute([(int)$id, $memberId]);
        $existing = $s->fetch();

        $selectedEventIds = [];
        if ($existing) {
            $selectedEventIds = $this->portalModel->getEntryEventIds((int)$existing['id']);
        }

        $this->render('portal/competition_register', [
            'title'            => 'Rejestracja na zawody',
            'competition'      => array_merge($competition, ['discipline_name' => $competition['discipline_name'] ?? '']),
            'events'           => $events,
            'selectedEventIds' => $selectedEventIds,
        ]);
    }

    public function storeRegister(string $id): void
    {
        Csrf::verify();
        $memberId  = MemberAuth::id();
        $compModel = new CompetitionModel();

        $competition = $compModel->getWithDetails((int)$id);
        if (!$competition || $competition['status'] !== 'otwarte') {
            Session::flash('error', 'Zapisy na te zawody są zamknięte.');
            $this->redirectTo('portal/competitions');
        }

        $eventIds = array_map('intval', (array)($_POST['event_ids'] ?? []));
        $db       = Database::getInstance();

        // Upsert entry (allow re-registration if previously withdrawn)
        $s = $db->prepare("SELECT id, status FROM competition_entries WHERE competition_id = ? AND member_id = ?");
        $s->execute([(int)$id, $memberId]);
        $existing = $s->fetch();

        if ($existing) {
            $entryId = (int)$existing['id'];
            $db->prepare("UPDATE competition_entries SET status = 'zgloszony', registered_by = NULL WHERE id = ?")
               ->execute([$entryId]);
        } else {
            try {
                $db->prepare("
                    INSERT INTO competition_entries (competition_id, member_id, status, registered_by, registered_at)
                    VALUES (?, ?, 'zgloszony', NULL, NOW())
                ")->execute([(int)$id, $memberId]);
                $entryId = (int)$db->lastInsertId();
            } catch (\PDOException) {
                Session::flash('error', 'Błąd zapisu. Spróbuj ponownie.');
                $this->redirectTo('portal/competitions');
            }
        }

        // Save selected events
        if ($eventIds) {
            try {
                $db->prepare("DELETE FROM competition_entry_events WHERE competition_entry_id = ?")
                   ->execute([$entryId]);
                foreach ($eventIds as $evId) {
                    $db->prepare("INSERT IGNORE INTO competition_entry_events (competition_entry_id, competition_event_id) VALUES (?, ?)")
                       ->execute([$entryId, $evId]);
                }
            } catch (\PDOException) {}
        }

        // Notification for staff
        $memberUser = MemberAuth::member();
        (new NotificationModel())->create([
            'type'              => 'competition_entry',
            'title'             => 'Nowe zgłoszenie na zawody',
            'message'           => ($memberUser['full_name'] ?? 'Zawodnik') . ' zapisał/a się na zawody: ' . $competition['name'],
            'for_roles'         => 'admin,zarzad,instruktor',
            'related_member_id' => $memberId,
        ]);

        Session::flash('success', 'Zgłoszenie zostało złożone. Oczekuje na potwierdzenie przez klub.');
        $this->redirectTo('portal/competitions');
    }

    public function cancelRegistration(string $entryId): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();
        $db       = Database::getInstance();

        $s = $db->prepare("
            SELECT ce.*, c.status AS competition_status
            FROM competition_entries ce
            JOIN competitions c ON c.id = ce.competition_id
            WHERE ce.id = ? AND ce.member_id = ?
        ");
        $s->execute([(int)$entryId, $memberId]);
        $entry = $s->fetch();

        if (!$entry) {
            Session::flash('error', 'Nie znaleziono zgłoszenia.');
            $this->redirectTo('portal/competitions');
        }
        if ($entry['status'] !== 'zgloszony') {
            Session::flash('error', 'Nie można wycofać potwierdzonego lub wycofanego zgłoszenia.');
            $this->redirectTo('portal/competitions');
        }
        if ($entry['competition_status'] !== 'otwarte') {
            Session::flash('error', 'Zapisy zostały zamknięte — zgłoszenie nie może być wycofane.');
            $this->redirectTo('portal/competitions');
        }

        $db->prepare("UPDATE competition_entries SET status = 'wycofany' WHERE id = ?")
           ->execute([(int)$entryId]);

        Session::flash('success', 'Zgłoszenie zostało wycofane.');
        $this->redirectTo('portal/competitions');
    }

    // ── Fees ─────────────────────────────────────────────────────────────────

    public function fees(): void
    {
        $year     = (int)($_GET['year'] ?? date('Y'));
        $payments = $this->portalModel->getFeesSummary(MemberAuth::id(), $year);

        $this->render('portal/fees', [
            'title'    => 'Opłaty i składki',
            'payments' => $payments,
            'year'     => $year,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function render(string $template, array $data = []): void
    {
        $data['memberUser']   = MemberAuth::member();
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');
        $data['flashWarning'] = Session::getFlash('warning');
        $this->view->setLayout('portal');
        $this->view->render($template, $data);
    }

    private function redirectTo(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}
