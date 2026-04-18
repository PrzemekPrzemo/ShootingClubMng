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
        $judgeLicenses   = (new \App\Models\JudgeLicenseModel())->getForMember($memberId);
        $openComps       = $this->portalModel->getOpenCompetitions($memberId);
        $recentResults   = array_slice($this->portalModel->getMemberResults($memberId), 0, 3);
        $payments        = $this->portalModel->getFeesSummary($memberId, $year);
        $pendingFees     = array_filter($payments, fn($p) => empty($p['paid_date'] ?? ''));

        $this->render('portal/dashboard', [
            'title'            => 'Portal Zawodnika',
            'licenses'         => $licenses,
            'judgeLicenses'    => $judgeLicenses,
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
            SELECT m.*,
                   mac.name AS age_category_name,
                   mc.name  AS sport_class
            FROM members m
            LEFT JOIN member_age_categories mac ON mac.id = m.age_category_id
            LEFT JOIN member_classes mc ON mc.id = m.member_class_id
            WHERE m.id = ?
        ");
        $stmt->execute([MemberAuth::id()]);
        $member = $stmt->fetch() ?: [];

        // Member disciplines with class + join date
        $dStmt = $db->prepare("
            SELECT d.name, md.class, md.joined_at
            FROM member_disciplines md
            JOIN disciplines d ON d.id = md.discipline_id
            WHERE md.member_id = ?
            ORDER BY d.name
        ");
        $dStmt->execute([MemberAuth::id()]);
        $disciplines = $dStmt->fetchAll();

        $this->render('portal/profile', [
            'title'       => 'Mój profil',
            'member'      => $member,
            'disciplines' => $disciplines,
        ]);
    }

    public function editProfile(): void
    {
        $stmt = Database::getInstance()->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([MemberAuth::id()]);
        $member = $stmt->fetch() ?: [];

        $this->render('portal/profile_edit', [
            'title'  => 'Edytuj dane kontaktowe',
            'member' => $member,
        ]);
    }

    public function updateProfile(): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();

        // Tylko pola dozwolone dla zawodnika (NIE: first_name, last_name, pesel, email)
        $allowed = ['phone', 'address_street', 'address_city', 'address_postal'];
        $data = [];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = trim($_POST[$field]);
            }
        }

        // Handle photo upload (ID-card format, max 2 MB, JPG/PNG)
        $newPhoto = $this->handleMemberPhotoUpload($memberId);
        if ($newPhoto !== null) {
            // Remove previous photo file if present
            $stmt = Database::getInstance()->prepare("SELECT photo_path FROM members WHERE id = ?");
            $stmt->execute([$memberId]);
            $oldPhoto = (string)($stmt->fetchColumn() ?: '');
            if ($oldPhoto && file_exists(ROOT_PATH . '/storage/photos/' . $oldPhoto)) {
                @unlink(ROOT_PATH . '/storage/photos/' . $oldPhoto);
            }
            $data['photo_path'] = $newPhoto;
        }

        if (!empty($data)) {
            $sets   = implode(', ', array_map(fn($f) => "`{$f}` = ?", array_keys($data)));
            $params = array_values($data);
            $params[] = $memberId;
            Database::getInstance()->prepare("UPDATE members SET {$sets} WHERE id = ?")
                ->execute($params);
            Session::flash('success', 'Dane zostały zaktualizowane.');
        }

        $this->redirectTo('portal/profile');
    }

    /**
     * Handle member photo upload from portal.
     * Max 2 MB, JPG/PNG only. Returns stored filename or null.
     */
    private function handleMemberPhotoUpload(int $memberId): ?string
    {
        if (empty($_FILES['photo']['name'])) {
            return null;
        }
        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Nie udało się wczytać zdjęcia (błąd przesyłania).');
            return null;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            Session::flash('error', 'Zdjęcie nie może przekraczać 2 MB.');
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            Session::flash('error', 'Dozwolone formaty zdjęcia: JPG, PNG.');
            return null;
        }
        $storageDir = ROOT_PATH . '/storage/photos';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) $ext = 'jpg';
        $filename = 'member' . $memberId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $storageDir . '/' . $filename)) {
            Session::flash('error', 'Nie udało się zapisać zdjęcia na serwerze.');
            return null;
        }
        return $filename;
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

        // Validate and sanitize dates (prevent invalid dates reaching DB)
        $examDateRaw  = trim($_POST['exam_date'] ?? '');
        $examDate     = ($examDateRaw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDateRaw) && strtotime($examDateRaw))
            ? $examDateRaw
            : date('Y-m-d');
        $validUntilRaw = trim($_POST['valid_until'] ?? '');
        $validUntil    = ($validUntilRaw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntilRaw) && strtotime($validUntilRaw))
            ? $validUntilRaw
            : null;
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
        $memberId = MemberAuth::id();
        $this->render('portal/results', [
            'title'   => 'Moje wyniki',
            'results' => $this->portalModel->getMemberResults($memberId),
            'stats'   => $this->portalModel->getMemberStats($memberId),
        ]);
    }

    // ── Competitions ──────────────────────────────────────────────────────────

    public function competitions(): void
    {
        $memberId = MemberAuth::id();
        $clubId   = MemberAuth::clubId();
        $db = Database::getInstance();

        // Waitlist entries for this member (scoped to club)
        $sql = "SELECT cw.position, c.name AS competition_name, c.competition_date
                FROM competition_waitlist cw
                JOIN competitions c ON c.id = cw.competition_id
                WHERE cw.member_id = ?";
        $params = [$memberId];
        if ($clubId !== null) { $sql .= " AND c.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY c.competition_date";
        $wlStmt = $db->prepare($sql);
        $wlStmt->execute($params);
        $myWaitlist = $wlStmt->fetchAll();

        $this->render('portal/competitions', [
            'title'            => 'Zawody',
            'openCompetitions' => $this->portalModel->getOpenCompetitions($memberId, $clubId),
            'allUpcoming'      => $this->portalModel->getUpcomingCompetitions($memberId, $clubId),
            'myEntries'        => $this->portalModel->getMemberEntries($memberId, $clubId),
            'myWaitlist'       => $myWaitlist,
        ]);
    }

    public function showRegister(string $id): void
    {
        // Scope CompetitionModel::getWithDetails to member's club
        $clubId = MemberAuth::clubId();
        if ($clubId !== null) {
            \App\Helpers\ClubContext::set($clubId);
        }

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

        // Check capacity — if full, offer waitlist
        if (!empty($competition['max_entries'])) {
            $countStmt = $db->prepare(
                "SELECT COUNT(*) FROM competition_entries WHERE competition_id = ? AND status NOT IN ('wycofany','zdyskwalifikowany')"
            );
            $countStmt->execute([(int)$id]);
            $currentCount = (int)$countStmt->fetchColumn();

            if ($currentCount >= (int)$competition['max_entries']) {
                // Check if already on waitlist
                $wlStmt = $db->prepare("SELECT id FROM competition_waitlist WHERE competition_id = ? AND member_id = ? LIMIT 1");
                $wlStmt->execute([(int)$id, $memberId]);
                if ($wlStmt->fetch()) {
                    Session::flash('warning', 'Już jesteś na liście rezerwowej tych zawodów.');
                } else {
                    // Find next position
                    $posStmt = $db->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM competition_waitlist WHERE competition_id = ?");
                    $posStmt->execute([(int)$id]);
                    $position = (int)$posStmt->fetchColumn();

                    // Tolerant INSERT: club_id column added in migration_v33 — fall back if absent
                    try {
                        $db->prepare("INSERT INTO competition_waitlist (competition_id, member_id, club_id, position) VALUES (?, ?, ?, ?)")
                           ->execute([(int)$id, $memberId, (int)$competition['club_id'], $position]);
                    } catch (\PDOException) {
                        $db->prepare("INSERT INTO competition_waitlist (competition_id, member_id, position) VALUES (?, ?, ?)")
                           ->execute([(int)$id, $memberId, $position]);
                    }

                    Session::flash('info', 'Zawody są pełne. Zostałeś/aś dodany/a do listy rezerwowej (pozycja ' . $position . '). Powiadomimy Cię, gdy pojawi się wolne miejsce.');
                }
                $this->redirectTo('portal/competitions');
            }
        }

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

        $competitionId = (int)$entry['competition_id'];
        $db->prepare("UPDATE competition_entries SET status = 'wycofany' WHERE id = ?")
           ->execute([(int)$entryId]);

        // Notify first person on waitlist
        $this->notifyWaitlistFirst($db, $competitionId);

        Session::flash('success', 'Zgłoszenie zostało wycofane.');
        $this->redirectTo('portal/competitions');
    }

    private function notifyWaitlistFirst(\PDO $db, int $competitionId): void
    {
        $stmt = $db->prepare(
            "SELECT cw.*, m.email, m.first_name, m.last_name, c.name AS competition_name
             FROM competition_waitlist cw
             JOIN members m ON m.id = cw.member_id
             JOIN competitions c ON c.id = cw.competition_id
             WHERE cw.competition_id = ? AND cw.notified_at IS NULL
             ORDER BY cw.position
             LIMIT 1"
        );
        $stmt->execute([$competitionId]);
        $first = $stmt->fetch();

        if (!$first || empty($first['email'])) return;

        $db->prepare("UPDATE competition_waitlist SET notified_at = NOW() WHERE id = ?")->execute([$first['id']]);

        // Send notification e-mail
        $clubId = \App\Helpers\ClubContext::current() ?? 1;
        $html = "<p>Drogi/a " . htmlspecialchars($first['first_name'] . ' ' . $first['last_name']) . ",</p>"
            . "<p>Pojawiło się wolne miejsce na zawodach <strong>" . htmlspecialchars($first['competition_name']) . "</strong>.</p>"
            . "<p>Zaloguj się do portalu, aby zarejestrować swój udział.</p>"
            . "<p>Pozdrawiamy,<br>Klub</p>";

        try {
            \App\Helpers\EmailService::send($clubId, $first['email'], $first['first_name'] . ' ' . $first['last_name'], 'Wolne miejsce na zawodach: ' . $first['competition_name'], $html);
        } catch (\Throwable) {}
    }

    // ── Fees ─────────────────────────────────────────────────────────────────

    public function fees(): void
    {
        $memberId = MemberAuth::id();
        $memberClubId = MemberAuth::clubId();
        $year     = (int)($_GET['year'] ?? date('Y'));
        $payments = $this->portalModel->getFeesSummary($memberId, $year, $memberClubId);

        // Przelewy24 per-club check
        $p24Enabled     = false;
        $p24Sandbox     = true;
        $onlinePayments = [];

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT club_id FROM members WHERE id = ? LIMIT 1");
            $stmt->execute([$memberId]);
            $clubId = (int)$stmt->fetchColumn();

            if ($clubId > 0) {
                $settings   = new \App\Models\ClubSettingsModel();
                $p24Enabled = (bool)$settings->get($clubId, 'p24_enabled', false)
                    && (string)$settings->get($clubId, 'p24_merchant_id', '') !== ''
                    && (string)$settings->get($clubId, 'p24_api_key', '')     !== '';
                $p24Sandbox = (bool)$settings->get($clubId, 'p24_sandbox', true);

                if ($p24Enabled) {
                    $opStmt = $db->prepare("
                        SELECT id, description, amount, status, created_at, p24_order_id
                        FROM   online_payments
                        WHERE  member_id = ? AND club_id = ?
                        ORDER  BY created_at DESC
                        LIMIT  5
                    ");
                    $opStmt->execute([$memberId, $clubId]);
                    $onlinePayments = $opStmt->fetchAll();
                }
            }
        } catch (\Throwable) {}

        $this->render('portal/fees', [
            'title'          => 'Opłaty i składki',
            'payments'       => $payments,
            'year'           => $year,
            'p24Enabled'     => $p24Enabled,
            'p24Sandbox'     => $p24Sandbox,
            'onlinePayments' => $onlinePayments,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function render(string $template, array $data = []): void
    {
        $data['memberUser']   = MemberAuth::member();
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');
        $data['flashWarning'] = Session::getFlash('warning');
        $data['flashInfo']    = Session::getFlash('info');
        $this->view->setLayout('portal');
        $this->view->render($template, $data);
    }

    // ── Personal weapons ─────────────────────────────────────────────────────

    public function myWeapons(): void
    {
        $memberId    = MemberAuth::id();
        $member      = (new \App\Models\MemberModel())->findById($memberId);
        $weaponModel = new \App\Models\MemberWeaponModel();
        $weapons     = $weaponModel->getForMember($memberId);

        $this->render('portal/weapons', [
            'title'   => 'Moja broń',
            'weapons' => $weapons,
            'member'  => $member,
            'types'   => \App\Models\MemberWeaponModel::$TYPES,
        ]);
    }

    public function storeWeapon(): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj nazwę / model broni.');
            $this->redirectTo('portal/weapons');
        }

        $type = $_POST['type'] ?? 'inne';
        if (!array_key_exists($type, \App\Models\MemberWeaponModel::$TYPES)) {
            $type = 'inne';
        }

        (new \App\Models\MemberWeaponModel())->create([
            'member_id'      => $memberId,
            'name'           => $name,
            'type'           => $type,
            'manufacturer'   => trim($_POST['manufacturer'] ?? ''),
            'caliber'        => trim($_POST['caliber'] ?? ''),
            'serial_number'  => trim($_POST['serial_number'] ?? ''),
            'permit_number'  => trim($_POST['permit_number'] ?? '') ?: null,
            'booklet_number' => trim($_POST['booklet_number'] ?? '') ?: null,
            'notes'          => trim($_POST['notes'] ?? ''),
            'is_active'      => 1,
        ]);

        Session::flash('success', 'Broń została dodana do Twojego profilu.');
        $this->redirectTo('portal/weapons');
    }

    public function deactivateWeapon(string $id): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();
        $db       = Database::getInstance();

        // Owner check
        $stmt = $db->prepare("SELECT id FROM member_weapons WHERE id = ? AND member_id = ? AND is_active = 1");
        $stmt->execute([(int)$id, $memberId]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Nie znaleziono aktywnej broni.');
            $this->redirectTo('portal/weapons');
        }

        $db->prepare("UPDATE member_weapons SET is_active = 0 WHERE id = ?")->execute([(int)$id]);
        Session::flash('success', 'Broń została oznaczona jako wycofana.');
        $this->redirectTo('portal/weapons');
    }

    // ── Trainings ────────────────────────────────────────────────────────────

    public function trainings(): void
    {
        $memberId = MemberAuth::id();
        $clubId   = MemberAuth::clubId();
        $db       = Database::getInstance();

        $sql = "SELECT t.*, u.full_name AS instructor_name,
                       ta.id AS enrolled_id, ta.attended
                FROM trainings t
                LEFT JOIN users u ON u.id = t.instructor_id
                LEFT JOIN training_attendees ta ON ta.training_id = t.id AND ta.member_id = ?
                WHERE t.training_date >= CURDATE()
                  AND t.status = 'planowany'";
        $params = [$memberId];
        if ($clubId !== null) {
            $sql .= " AND t.club_id = ?";
            $params[] = $clubId;
        }
        // is_public=1 — show only trainings explicitly published to athletes.
        // Tolerant of legacy rows where column might not exist yet.
        try {
            $db->query("SELECT is_public FROM trainings LIMIT 0");
            $sql .= " AND t.is_public = 1";
        } catch (\PDOException) {}
        $sql .= " ORDER BY t.training_date ASC, t.time_start ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $upcomingTrainings = $stmt->fetchAll();

        $this->render('portal/trainings', [
            'title'     => 'Treningi',
            'trainings' => $upcomingTrainings,
        ]);
    }

    public function enrollTraining(string $id): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();
        $db       = Database::getInstance();

        $stmt = $db->prepare("SELECT id FROM trainings WHERE id = ? AND training_date >= CURDATE() AND status = 'planowany'");
        $stmt->execute([(int)$id]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Trening nie jest dostępny do zapisów.');
            $this->redirectTo('portal/trainings');
        }

        try {
            $db->prepare("INSERT IGNORE INTO training_attendees (training_id, member_id, attended) VALUES (?, ?, 0)")
               ->execute([(int)$id, $memberId]);
            Session::flash('success', 'Zostałeś/aś zapisany/a na trening.');
        } catch (\PDOException) {
            Session::flash('error', 'Błąd zapisu. Spróbuj ponownie.');
        }
        $this->redirectTo('portal/trainings');
    }

    public function unenrollTraining(string $id): void
    {
        Csrf::verify();
        $memberId = MemberAuth::id();
        $db       = Database::getInstance();

        $db->prepare("DELETE FROM training_attendees WHERE training_id = ? AND member_id = ? AND attended = 0")
           ->execute([(int)$id, $memberId]);

        Session::flash('success', 'Zostałeś/aś wypisany/a z treningu.');
        $this->redirectTo('portal/trainings');
    }

    private function redirectTo(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}
