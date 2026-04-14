<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\ClubCustomizationModel;
use App\Models\ClubSettingsModel;
use App\Models\UserModel;
use App\Models\SettingModel;
use App\Models\SubscriptionModel;

/**
 * Panel super admina — zarządzanie klubami, ustawienia globalne.
 * Wszystkie metody wymagają is_super_admin.
 */
class AdminController extends BaseController
{
    private ClubModel $clubModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
        $this->clubModel = new ClubModel();
        $this->userModel = new UserModel();
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        // Always exit club context when entering admin dashboard
        ClubContext::clear();

        $stats = $this->clubModel->getGlobalStats();
        $clubs = $this->clubModel->getActive();

        $this->render('admin/dashboard', [
            'title'  => 'Panel administratora',
            'stats'  => $stats,
            'clubs'  => $clubs,
        ]);
    }

    /** GET /admin/exit-club — clear club context and return to admin panel */
    public function exitClub(): void
    {
        ClubContext::clear();
        $this->redirect('admin/dashboard');
    }

    // ── Clubs CRUD ───────────────────────────────────────────────────────────

    public function clubs(): void
    {
        $clubs   = $this->clubModel->findAll('name');
        $subModel = new SubscriptionModel();
        foreach ($clubs as &$club) {
            $club['stats'] = $this->clubModel->getStats($club['id']);
            $club['sub']   = $subModel->getForClub($club['id']);
        }
        unset($club);

        $this->render('admin/clubs', [
            'title' => 'Zarządzanie klubami',
            'clubs' => $clubs,
        ]);
    }

    public function createClub(): void
    {
        $this->render('admin/club_form', [
            'title'        => 'Nowy klub',
            'club'         => null,
            'subscription' => null,
            'clubModules'  => [],
            'smtpConfig'   => ['smtp_enabled' => false, 'smtp_host' => '', 'smtp_port' => 587, 'smtp_secure' => 'tls', 'smtp_user' => '', 'smtp_has_pass' => false, 'smtp_from_email' => '', 'smtp_from_name' => ''],
            'p24Config'    => ['p24_enabled' => false, 'p24_merchant_id' => '', 'p24_pos_id' => '', 'p24_has_api_key' => false, 'p24_has_crc_key' => false, 'p24_sandbox' => true],
        ]);
    }

    public function storeClub(): void
    {
        Csrf::verify();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
        ];

        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa klubu jest wymagana.');
            $this->redirect('admin/clubs/create');
        }

        $clubId = $this->clubModel->create($data);

        // Utwórz domyślną customizację i ustawienia
        (new ClubCustomizationModel())->save($clubId, []);
        $settings = new ClubSettingsModel();
        $settings->set($clubId, 'smtp_enabled', '0', 'Własny SMTP', 'boolean');

        // Subskrypcja
        $plan = trim($_POST['plan'] ?? '');
        if ($plan !== '') {
            $maxMembers = trim($_POST['max_members'] ?? '');
            (new SubscriptionModel())->upsert($clubId, [
                'plan'        => $plan,
                'valid_until' => $_POST['valid_until'] ?: null,
                'status'      => $_POST['sub_status'] ?? 'active',
                'max_members' => $maxMembers !== '' ? (int)$maxMembers : null,
            ]);
        }

        // Moduły (domyślnie wszystkie włączone — nie zapisujemy nic, getModules() zwróci puste = domyślnie true)

        Session::flash('success', "Klub \"{$data['name']}\" zostal utworzony.");
        $this->redirect('admin/clubs');
    }

    public function editClub(string $id): void
    {
        $club = $this->clubModel->findById((int)$id);
        if (!$club) {
            Session::flash('error', 'Klub nie istnieje.');
            $this->redirect('admin/clubs');
        }

        $settings     = new ClubSettingsModel();
        $subscription = (new SubscriptionModel())->getForClub((int)$id);
        $clubModules  = $settings->getModules((int)$id);
        $smtpConfig   = [
            'smtp_enabled'    => (bool)$settings->get((int)$id, 'smtp_enabled', false),
            'smtp_host'       => (string)$settings->get((int)$id, 'smtp_host', ''),
            'smtp_port'       => (int)$settings->get((int)$id, 'smtp_port', 587),
            'smtp_secure'     => (string)$settings->get((int)$id, 'smtp_secure', 'tls'),
            'smtp_user'       => (string)$settings->get((int)$id, 'smtp_user', ''),
            'smtp_has_pass'   => (string)$settings->get((int)$id, 'smtp_pass_enc', '') !== '',
            'smtp_from_email' => (string)$settings->get((int)$id, 'smtp_from_email', ''),
            'smtp_from_name'  => (string)$settings->get((int)$id, 'smtp_from_name', ''),
        ];

        $p24Config = [
            'p24_enabled'     => (bool)$settings->get((int)$id, 'p24_enabled', false),
            'p24_merchant_id' => (string)$settings->get((int)$id, 'p24_merchant_id', ''),
            'p24_pos_id'      => (string)$settings->get((int)$id, 'p24_pos_id', ''),
            'p24_has_api_key' => (string)$settings->get((int)$id, 'p24_api_key', '') !== '',
            'p24_has_crc_key' => (string)$settings->get((int)$id, 'p24_crc_key', '') !== '',
            'p24_sandbox'     => (bool)$settings->get((int)$id, 'p24_sandbox', true),
        ];

        $this->render('admin/club_form', [
            'title'        => 'Edycja klubu — ' . $club['name'],
            'club'         => $club,
            'subscription' => $subscription,
            'clubModules'  => $clubModules,
            'smtpConfig'   => $smtpConfig,
            'p24Config'    => $p24Config,
        ]);
    }

    public function updateClub(string $id): void
    {
        Csrf::verify();

        $club = $this->clubModel->findById((int)$id);
        if (!$club) {
            $this->redirect('admin/clubs');
        }

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa klubu jest wymagana.');
            $this->redirect("admin/clubs/{$id}/edit");
        }

        $this->clubModel->updateClub((int)$id, $data);

        // Subskrypcja
        $plan = trim($_POST['plan'] ?? '');
        if ($plan !== '') {
            $maxMembers = trim($_POST['max_members'] ?? '');
            (new SubscriptionModel())->upsert((int)$id, [
                'plan'        => $plan,
                'valid_until' => $_POST['valid_until'] ?: null,
                'status'      => $_POST['sub_status'] ?? 'active',
                'max_members' => $maxMembers !== '' ? (int)$maxMembers : null,
            ]);
        }

        // Moduły
        $settings = new ClubSettingsModel();
        $settings->setModules((int)$id, $_POST['modules'] ?? []);

        // SMTP settings
        $settings->set((int)$id, 'smtp_enabled', isset($_POST['smtp_enabled']) ? '1' : '0', 'Własny SMTP', 'boolean');
        foreach ([
            'smtp_host'       => 'SMTP Host',
            'smtp_port'       => 'SMTP Port',
            'smtp_secure'     => 'SMTP Szyfrowanie',
            'smtp_user'       => 'SMTP Użytkownik',
            'smtp_from_email' => 'Nadawca e-mail',
            'smtp_from_name'  => 'Nazwa nadawcy',
        ] as $key => $label) {
            $settings->set((int)$id, $key, trim($_POST[$key] ?? ''), $label, $key === 'smtp_port' ? 'number' : 'text');
        }
        $smtpPw = trim($_POST['smtp_pass_enc'] ?? '');
        if ($smtpPw !== '') {
            $settings->set((int)$id, 'smtp_pass_enc', $smtpPw, 'SMTP Hasło', 'text');
        }

        // Przelewy24 per-klub
        $settings->set((int)$id, 'p24_enabled',     isset($_POST['p24_enabled']) ? '1' : '0', 'P24 włączony',   'boolean');
        $settings->set((int)$id, 'p24_merchant_id', trim($_POST['p24_merchant_id'] ?? ''),     'P24 Merchant ID', 'text');
        $settings->set((int)$id, 'p24_pos_id',      trim($_POST['p24_pos_id'] ?? ''),          'P24 POS ID',      'text');
        $settings->set((int)$id, 'p24_sandbox',     isset($_POST['p24_sandbox']) ? '1' : '0', 'P24 Sandbox',     'boolean');
        $p24ApiKey = trim($_POST['p24_api_key'] ?? '');
        if ($p24ApiKey !== '') {
            $settings->set((int)$id, 'p24_api_key', $p24ApiKey, 'P24 API Key', 'text');
        }
        $p24CrcKey = trim($_POST['p24_crc_key'] ?? '');
        if ($p24CrcKey !== '') {
            $settings->set((int)$id, 'p24_crc_key', $p24CrcKey, 'P24 CRC Key', 'text');
        }

        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect('admin/clubs');
    }

    // ── Club users ───────────────────────────────────────────────────────────

    public function clubUsers(string $clubId): void
    {
        $club = $this->clubModel->findById((int)$clubId);
        if (!$club) {
            $this->redirect('admin/clubs');
        }

        $users    = $this->userModel->getUsersForClub((int)$clubId);
        $allUsers = $this->userModel->getAllUsers();

        $this->render('admin/club_users', [
            'title'    => 'Użytkownicy — ' . $club['name'],
            'club'     => $club,
            'users'    => $users,
            'allUsers' => $allUsers,
        ]);
    }

    public function addClubUser(string $clubId): void
    {
        Csrf::verify();
        $userId = (int)($_POST['user_id'] ?? 0);
        $role   = $_POST['role'] ?? 'instruktor';

        if ($userId > 0) {
            $this->userModel->assignToClub($userId, (int)$clubId, $role);
            Session::flash('success', 'Użytkownik przypisany do klubu.');
        }
        $this->redirect("admin/clubs/{$clubId}/users");
    }

    public function removeClubUser(string $clubId, string $userId): void
    {
        $this->userModel->hardRemoveFromClub((int)$userId, (int)$clubId);
        Session::flash('success', 'Użytkownik usunięty z klubu.');
        $this->redirect("admin/clubs/{$clubId}/users");
    }

    // ── Global settings ──────────────────────────────────────────────────────

    public function settings(): void
    {
        $settingModel = new SettingModel();
        $settings = [];
        foreach (['base_domain', 'allow_club_smtp', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass_enc', 'system_name', 'system_logo', 'global_api_key'] as $key) {
            $settings[$key] = $settingModel->get($key);
        }

        $this->render('admin/settings', [
            'title'    => 'Ustawienia globalne',
            'settings' => $settings,
        ]);
    }

    public function saveSettings(): void
    {
        Csrf::verify();
        $settingModel = new SettingModel();

        $allowed = ['base_domain', 'allow_club_smtp', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass_enc'];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $settingModel->set($key, trim($_POST[$key]));
            }
        }

        // System branding
        if (isset($_POST['system_name'])) {
            $settingModel->upsert('system_name', trim($_POST['system_name']), 'Nazwa systemu', 'text');
        }

        // System logo upload
        if (!empty($_FILES['system_logo']['tmp_name']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
                Session::flash('error', 'Niedozwolony format pliku. Dopuszczalne: PNG, JPG, SVG, WebP.');
                $this->redirect('admin/settings');
            }

            $tmpPath  = $_FILES['system_logo']['tmp_name'];
            $mimeMap  = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','svg'=>'image/svg+xml','webp'=>'image/webp'];
            $mime     = $mimeMap[$ext] ?? 'image/png';
            $logoDir  = ROOT_PATH . '/storage/system/';
            $savedToFile = false;

            // Attempt 1: save as file in storage/system/
            // Use @mkdir + try to actually write (is_writable() is unreliable on Plesk/shared hosting)
            @mkdir($logoDir, 0777, true);
            if (is_dir($logoDir)) {
                foreach (glob($logoDir . 'logo.*') ?: [] as $f) { @unlink($f); }
                $dest = $logoDir . 'logo.' . $ext;
                if (@move_uploaded_file($tmpPath, $dest) && file_exists($dest)) {
                    @chmod($dest, 0644);
                    $settingModel->upsert('system_logo',      'logo.' . $ext, 'Logo systemu', 'text');
                    $settingModel->upsert('system_logo_b64',  '',              'Logo base64',  'text');
                    $settingModel->upsert('system_logo_mime', $mime,           'Logo MIME',    'text');
                    $savedToFile = true;
                }
            }

            // Attempt 2 (fallback): store as base64 in database — no filesystem needed
            if (!$savedToFile) {
                // Re-read the original temp file (move_uploaded_file may have failed but file still exists)
                $raw = @file_get_contents($tmpPath);
                if ($raw === false || $raw === '') {
                    Session::flash('error', 'Nie udało się odczytać przesłanego pliku. Spróbuj ponownie.');
                    $this->redirect('admin/settings');
                }
                $b64 = 'data:' . $mime . ';base64,' . base64_encode($raw);
                $settingModel->upsert('system_logo',      'db',  'Logo systemu', 'text');
                $settingModel->upsert('system_logo_b64',  $b64,  'Logo base64',  'text');
                $settingModel->upsert('system_logo_mime', $mime, 'Logo MIME',    'text');
                Session::flash('warning', 'Logo zapisano w bazie danych — katalog storage/system/ nie jest zapisywalny przez proces PHP (użytkownik: ' . (function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? 'nieznany') : get_current_user()) . '). W panelu Plesk ustaw właściciela katalogu na tego użytkownika.');
            }
        }

        // Delete logo (if requested)
        if (isset($_POST['delete_logo'])) {
            $logoDir = ROOT_PATH . '/storage/system/';
            foreach (glob($logoDir . 'logo.*') ?: [] as $f) { @unlink($f); }
            $settingModel->upsert('system_logo',      '', 'Logo systemu', 'text');
            $settingModel->upsert('system_logo_b64',  '', 'Logo base64',  'text');
            $settingModel->upsert('system_logo_mime', '', 'Logo MIME',    'text');
        }

        Session::flash('success', 'Zapisano ustawienia globalne.');
        $this->redirect('admin/settings');
    }

    /** GET /admin/system-logo — serwuje logo systemu (plik lub base64 z DB) */
    public function serveSystemLogo(): void
    {
        $settingModel = new SettingModel();
        $fileName     = (string)($settingModel->get('system_logo', '') ?: '');

        // Option A: logo stored as file on disk
        if ($fileName !== '' && $fileName !== 'db') {
            $path = ROOT_PATH . '/storage/system/' . basename($fileName);
            if (file_exists($path)) {
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','svg'=>'image/svg+xml','webp'=>'image/webp'][$ext] ?? 'image/png';
                $mts  = filemtime($path);
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=604800, immutable');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mts) . ' GMT');
                header('ETag: "' . $mts . '"');
                readfile($path);
                exit;
            }
        }

        // Option B: logo stored as base64 in database (fallback for servers without writable storage)
        if ($fileName === 'db') {
            $b64 = (string)($settingModel->get('system_logo_b64', '') ?: '');
            if (str_starts_with($b64, 'data:')) {
                // Parse data URI: data:<mime>;base64,<data>
                [$meta, $data] = explode(',', $b64, 2);
                $mime = str_replace(['data:', ';base64'], '', $meta);
                $raw  = base64_decode($data);
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . strlen($raw));
                header('Cache-Control: public, max-age=86400');
                echo $raw;
                exit;
            }
        }

        http_response_code(404);
        exit;
    }

    /** POST /admin/settings/regenerate-api-key */
    public function regenerateGlobalApiKey(): void
    {
        Csrf::verify();
        $settingModel = new SettingModel();
        $newKey = bin2hex(random_bytes(24));
        $settingModel->upsert('global_api_key', $newKey, 'Globalny klucz API', 'text');
        Session::flash('success', 'Globalny klucz API został wygenerowany.');
        $this->redirect('admin/settings');
    }

    // ── Switch club ──────────────────────────────────────────────────────────

    public function switchClub(string $id): void
    {
        $club = $this->clubModel->findById((int)$id);
        if (!$club) {
            Session::flash('error', 'Klub nie istnieje.');
            $this->redirect('admin/dashboard');
        }

        Auth::setClub((int)$id, 'admin');
        Session::flash('success', "Przełączono kontekst na: {$club['name']}");
        $this->redirect('dashboard');
    }

    // ── Impersonation ────────────────────────────────────────────────────────

    /** GET /admin/impersonate/club/:clubId/user/:userId — logowanie jako użytkownik klubu */
    public function impersonateClubUser(string $clubId, string $userId): void
    {
        $club = $this->clubModel->findById((int)$clubId);
        $user = $this->userModel->findById((int)$userId);

        if (!$club || !$user) {
            Session::flash('error', 'Nie znaleziono użytkownika lub klubu.');
            $this->redirect('admin/dashboard');
        }

        $roleInClub = $this->userModel->getRoleInClub((int)$userId, (int)$clubId) ?? 'admin';

        // Log impersonation
        $this->logImpersonation((int)$userId, 'club_user', (int)$clubId);

        Auth::impersonateClubUser($user, (int)$clubId, $roleInClub);
        Session::flash('warning', "Tryb impersonacji: logujesz się jako <strong>{$user['full_name']}</strong> w klubie <strong>{$club['name']}</strong>. <a href='" . url('admin/stop-impersonation') . "'>Zakończ</a>");
        $this->redirect('dashboard');
    }

    /** GET /admin/impersonate/user/:userId — logowanie jako użytkownik (bez podawania clubId w URL) */
    public function impersonateUser(string $userId): void
    {
        $user = $this->userModel->findById((int)$userId);
        if (!$user || !empty($user['is_super_admin'])) {
            Session::flash('error', 'Nie można impersonować tego użytkownika.');
            $this->redirect('admin/users');
        }

        $clubs = $this->userModel->getClubsForUser((int)$userId);
        if (empty($clubs)) {
            Session::flash('error', "Użytkownik {$user['full_name']} nie jest przypisany do zadnego klubu.");
            $this->redirect('admin/users');
        }

        $firstClub = $clubs[0];
        $this->logImpersonation((int)$userId, 'club_user', (int)$firstClub['club_id']);
        Auth::impersonateClubUser($user, (int)$firstClub['club_id'], $firstClub['highest_role']);
        Session::flash('warning', "Tryb impersonacji: logujesz się jako <strong>{$user['full_name']}</strong> w klubie <strong>{$firstClub['club_name']}</strong>. <a href='" . url('admin/stop-impersonation') . "'>Zakończ</a>");
        $this->redirect('dashboard');
    }

    /** GET /admin/impersonate/member/:memberId — logowanie jako zawodnik (portal) */
    public function impersonateMember(string $memberId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$memberId]);
        $member = $stmt->fetch();

        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('admin/dashboard');
        }

        // Log impersonation
        $this->logImpersonation((int)$memberId, 'member', (int)$member['club_id']);

        Auth::impersonateMember($member);
        Session::flash('warning', "Impersonacja zawodnika: <strong>{$member['first_name']} {$member['last_name']}</strong>. <a href='" . url('admin/stop-impersonation') . "'>Zakończ</a>");
        $this->redirect('portal');
    }

    /** GET /admin/stop-impersonation */
    public function stopImpersonation(): void
    {
        Auth::stopImpersonation();
        Session::flash('success', 'Zakończono impersonację. Wróciłeś do konta superadmina.');
        $this->redirect('admin/dashboard');
    }

    // ── User management (global) ─────────────────────────────────────────────

    /** GET /admin/users */
    public function users(): void
    {
        $users = $this->userModel->getAllUsers();
        // Dołącz przypisania klubów dla każdego użytkownika
        foreach ($users as &$u) {
            $u['clubs'] = $this->userModel->getClubsForUser($u['id']);
        }
        unset($u);

        $this->render('admin/users', [
            'title' => 'Użytkownicy systemu',
            'users' => $users,
        ]);
    }

    /** GET /admin/users/create */
    public function createUser(): void
    {
        $clubs = $this->clubModel->findAll('name');
        $this->render('admin/user_form', [
            'title'  => 'Nowy użytkownik',
            'user'   => null,
            'clubs'  => $clubs,
        ]);
    }

    /** POST /admin/users/create */
    public function storeUser(): void
    {
        Csrf::verify();

        $password = trim($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            Session::flash('error', 'Hasło musi mieć co najmniej 8 znaków.');
            $this->redirect('admin/users/create');
        }

        // System role is derived from club roles; default to 'instruktor'
        $clubRoles = array_filter((array)($_POST['club_roles'] ?? []));
        $sysRole   = $clubRoles
            ? \App\Models\UserModel::highestRole($clubRoles)
            : 'instruktor';

        $data = [
            'username'  => trim($_POST['username'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role'      => $sysRole,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'password'  => $password,
        ];

        if (empty($data['username']) || empty($data['email'])) {
            Session::flash('error', 'Login i e-mail są wymagane.');
            $this->redirect('admin/users/create');
        }

        $userId = $this->userModel->createUser($data);

        // Assign to club with selected roles
        $clubId = (int)($_POST['club_id'] ?? 0);
        if ($clubId > 0 && !empty($clubRoles)) {
            $this->userModel->setRolesInClub($userId, $clubId, $clubRoles);
        }

        Session::flash('success', "Użytkownik \"{$data['username']}\" utworzony.");
        $this->redirect('admin/users');
    }

    /** GET /admin/users/:id/edit */
    public function editUser(string $id): void
    {
        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            Session::flash('error', 'Użytkownik nie istnieje.');
            $this->redirect('admin/users');
        }

        $clubs     = $this->clubModel->findAll('name');
        $userClubs = $this->userModel->getClubsForUser((int)$id);

        // Build member list: members from all clubs this user belongs to
        $linkableMembers = [];
        $db = \App\Helpers\Database::pdo();
        if (!empty($userClubs)) {
            $clubIds = array_column($userClubs, 'club_id');
            $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
            $stmt = $db->prepare(
                "SELECT m.id, m.first_name, m.last_name, m.member_number, c.name AS club_name
                 FROM members m
                 JOIN clubs c ON c.id = m.club_id
                 WHERE m.club_id IN ($placeholders) AND m.status = 'aktywny'
                 ORDER BY m.last_name, m.first_name"
            );
            $stmt->execute(array_values($clubIds));
            $linkableMembers = $stmt->fetchAll();
        } else {
            // Super admin with no club context — fetch all active members
            $stmt = $db->query(
                "SELECT m.id, m.first_name, m.last_name, m.member_number, c.name AS club_name
                 FROM members m
                 JOIN clubs c ON c.id = m.club_id
                 WHERE m.status = 'aktywny'
                 ORDER BY m.last_name, m.first_name"
            );
            $linkableMembers = $stmt->fetchAll();
        }

        $this->render('admin/user_form', [
            'title'           => 'Edycja: ' . $user['full_name'],
            'user'            => $user,
            'clubs'           => $clubs,
            'userClubs'       => $userClubs,
            'linkableMembers' => $linkableMembers,
        ]);
    }

    /** POST /admin/users/:id/edit */
    public function updateUser(string $id): void
    {
        Csrf::verify();

        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            $this->redirect('admin/users');
        }

        // Derive system role from club roles if provided
        $clubRoles = array_filter((array)($_POST['club_roles'] ?? []));
        $sysRole   = $clubRoles
            ? \App\Models\UserModel::highestRole($clubRoles)
            : ($user['role'] ?? 'instruktor');

        // member_id: '' means clear, numeric = set, absent = don't change
        $memberIdRaw = $_POST['member_id'] ?? null;
        $data = [
            'username'  => trim($_POST['username'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role'      => $sysRole,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($memberIdRaw !== null) {
            $data['member_id'] = $memberIdRaw !== '' ? (int)$memberIdRaw : null;
        }
        $pw = trim($_POST['password'] ?? '');
        if ($pw !== '') {
            $data['password'] = $pw;
        }

        $this->userModel->updateUser((int)$id, $data);

        // Update club roles if a club was selected
        $clubId = (int)($_POST['club_id'] ?? 0);
        if ($clubId > 0 && !empty($clubRoles)) {
            $this->userModel->setRolesInClub((int)$id, $clubId, $clubRoles);
        }

        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect("admin/users/{$id}/edit");
    }

    /** POST /admin/users/:id/delete — dezaktywacja konta */
    public function deleteUser(string $id): void
    {
        Csrf::verify();
        $user = $this->userModel->findById((int)$id);
        if ($user && !$user['is_super_admin']) {
            $this->userModel->update((int)$id, ['is_active' => 0]);
            Session::flash('success', 'Użytkownik dezaktywowany.');
        }
        $this->redirect('admin/users');
    }

    /** POST /admin/users/:id/permanent-delete — trwałe usunięcie konta */
    public function permanentDeleteUser(string $id): void
    {
        Csrf::verify();

        $user = $this->userModel->findById((int)$id);
        if (!$user) {
            Session::flash('error', 'Użytkownik nie istnieje.');
            $this->redirect('admin/users');
        }
        if (!empty($user['is_super_admin'])) {
            Session::flash('error', 'Nie można usunąć konta superadmina.');
            $this->redirect('admin/users');
        }

        // Dodatkowe zabezpieczenie: sprawdź potwierdzenie loginu
        $confirm = trim($_POST['confirm_username'] ?? '');
        if ($confirm !== $user['username']) {
            Session::flash('error', 'Podany login nie zgadza się — usunięcie anulowane.');
            $this->redirect('admin/users');
        }

        $name = $user['full_name'] ?: $user['username'];
        if ($this->userModel->permanentDelete((int)$id)) {
            $this->logActivity(Auth::id(), 'user_permanent_delete', 'users', (int)$id, "Trwałe usunięcie konta: {$name}");
            Session::flash('success', "Konto użytkownika <strong>{$name}</strong> zostało trwale usunięte z systemu.");
        } else {
            Session::flash('error', 'Nie udało się usunąć użytkownika.');
        }
        $this->redirect('admin/users');
    }

    /** POST /admin/users/:userId/clubs/:clubId/remove */
    public function removeUserFromClub(string $userId, string $clubId): void
    {
        Csrf::verify();
        $this->userModel->removeFromClub((int)$userId, (int)$clubId);
        Session::flash('success', 'Usunięto przypisanie do klubu.');
        $this->redirect("admin/users/{$userId}/edit");
    }

    private function logImpersonation(int $targetId, string $targetType, int $clubId): void
    {
        try {
            $db = Database::getInstance();
            $db->prepare(
                "INSERT INTO impersonation_log (admin_user_id, target_type, target_id, target_club_id)
                 VALUES (?, ?, ?, ?)"
            )->execute([Auth::id(), $targetType, $targetId, $clubId]);
        } catch (\Throwable) {}
    }

}
