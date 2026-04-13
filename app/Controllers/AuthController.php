<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\SettingModel;
use App\Models\UserModel;

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('auth');
        $this->userModel = new UserModel();
    }

    // ── Panel login (club selector + credentials) ─────────────────────────────

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        // Load system branding from settings
        $systemBranding = ['name' => 'Shootero', 'logo' => '', 'logoMts' => '0'];
        try {
            $sm       = new SettingModel();
            $logoFile = (string)($sm->get('system_logo', '') ?: '');
            $logoPath = ROOT_PATH . '/storage/system/' . basename($logoFile);
            $logoOk   = $logoFile !== '' && file_exists($logoPath);
            $systemBranding['name']    = $sm->get('system_name', 'Shootero') ?: 'Shootero';
            $systemBranding['logo']    = $logoOk ? $logoFile : '';
            $systemBranding['logoMts'] = $logoOk ? (string)filemtime($logoPath) : '0';
        } catch (\Throwable) {}

        // Detect if coming via club subdomain
        $subdomainClubId = ClubContext::current();
        $subdomainClub   = null;
        if ($subdomainClubId !== null) {
            try {
                $clubModel = new ClubModel();
                $club = $clubModel->findById($subdomainClubId);
                $branding = ClubCustomizationModel::getForCurrentClub();
                $subdomainClub = [
                    'id'        => $subdomainClubId,
                    'name'      => $club['name'] ?? '',
                    'logo_path' => $branding['logo_path'] ?? '',
                ];
            } catch (\Throwable) {}
        }

        $this->render('auth/login', [
            'title'          => 'Logowanie',
            'clubs'          => $subdomainClub ? [] : $this->getActiveClubs(),
            'systemBranding' => $systemBranding,
            'subdomainClub'  => $subdomainClub,
        ]);
    }

    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        Csrf::verify();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $clubId   = (int)($_POST['club_id'] ?? 0);

        if ($username === '' || $password === '') {
            Session::flash('error', 'Podaj login i hasło.');
            $this->redirect('auth/login');
        }

        if ($clubId <= 0) {
            Session::flash('error', 'Wybierz klub z listy.');
            $this->redirect('auth/login');
        }

        // Rate limiting: 5 attempts per 15 min
        $rlKey = RateLimiter::key('admin_login', ($_SERVER['REMOTE_ADDR'] ?? '') . $username);
        if (RateLimiter::isBlocked($rlKey)) {
            $secs = RateLimiter::secondsUntilReset($rlKey);
            Session::flash('error', 'Zbyt wiele prób logowania. Spróbuj za ' . ceil($secs / 60) . ' min.');
            $this->redirect('auth/login');
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            RateLimiter::attempt($rlKey);
            $this->logActivity(null, 'login_failed', 'users', null, "Nieudana próba: {$username}");
            Session::flash('error', 'Nieprawidłowy login lub hasło.');
            $this->redirect('auth/login');
        }
        RateLimiter::clear($rlKey);

        // 2FA
        if (!empty($user['totp_enabled'])) {
            Session::set('totp_required', true);
            Session::set('totp_pending_user_id', $user['id']);
            Session::set('totp_pending_user', $user);
            Session::set('totp_pending_club_id', $clubId);
            $this->redirect('2fa/verify');
        }

        $this->userModel->updateLastLogin($user['id']);
        Auth::login($user);
        $this->logActivity($user['id'], 'login', 'users', $user['id'], 'Zalogowanie do systemu');

        // Verify club membership — get ALL roles
        $roles = $this->userModel->getRolesInClub($user['id'], $clubId);
        if (empty($roles)) {
            Session::flash('error', 'Nie masz dostępu do wybranego klubu.');
            Auth::logout();
            $this->redirect('auth/login');
        }

        // Multiple roles → role selection screen
        if (count($roles) > 1) {
            Session::set('pending_role_select', [
                'user_id' => $user['id'],
                'club_id' => $clubId,
                'roles'   => $roles,
            ]);
            $this->redirect('auth/role-select');
        }

        // Single role — log in immediately
        Auth::setClub($clubId, $roles[0]);
        $this->redirectAfterLogin();
    }

    // ── Role selection after login ─────────────────────────────────────────────

    public function showRoleSelect(): void
    {
        $pending = Session::get('pending_role_select');
        if (!$pending || !Auth::check()) {
            $this->redirect('auth/login');
        }

        // Load system branding
        $systemBranding = ['name' => 'Shootero', 'logo' => ''];
        try {
            $sm = new SettingModel();
            $systemBranding['name'] = $sm->get('system_name', 'Shootero') ?: 'Shootero';
            $systemBranding['logo'] = $sm->get('system_logo', '') ?: '';
        } catch (\Throwable) {}

        $this->render('auth/role_select', [
            'title'          => 'Wybierz rolę',
            'roles'          => $pending['roles'],
            'clubId'         => $pending['club_id'],
            'systemBranding' => $systemBranding,
        ]);
    }

    public function processRoleSelect(): void
    {
        $pending = Session::get('pending_role_select');
        if (!$pending || !Auth::check()) {
            $this->redirect('auth/login');
        }

        Csrf::verify();

        $selectedRole = trim($_POST['role'] ?? '');
        if (!in_array($selectedRole, $pending['roles'], true)) {
            Session::flash('error', 'Nieprawidłowa rola.');
            $this->redirect('auth/role-select');
        }

        Session::remove('pending_role_select');
        Auth::setClub($pending['club_id'], $selectedRole);
        $this->redirectAfterLogin();
    }

    // ── Master login (superadmin — no club context) ───────────────────────────

    public function masterLoginForm(): void
    {
        if (Auth::check() && !empty($_SESSION['is_super_admin'])) {
            $this->redirect('admin/dashboard');
        }

        $this->render('auth/master_login', [
            'title' => 'Panel administratora systemu',
        ]);
    }

    public function masterLogin(): void
    {
        Csrf::verify();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $rlKey = RateLimiter::key('master_login', ($_SERVER['REMOTE_ADDR'] ?? '') . $username);
        if (RateLimiter::isBlocked($rlKey)) {
            $secs = RateLimiter::secondsUntilReset($rlKey);
            Session::flash('error', 'Zbyt wiele prób. Spróbuj za ' . ceil($secs / 60) . ' min.');
            $this->redirect('masterlogin');
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password']) || empty($user['is_super_admin'])) {
            RateLimiter::attempt($rlKey);
            $this->logActivity(null, 'master_login_failed', 'users', null, "Nieudana próba masterlogin: {$username}");
            Session::flash('error', 'Nieprawidłowe dane lub brak uprawnień superadmina.');
            $this->redirect('masterlogin');
        }
        RateLimiter::clear($rlKey);

        // 2FA
        if (!empty($user['totp_enabled'])) {
            Session::set('totp_required', true);
            Session::set('totp_pending_user_id', $user['id']);
            Session::set('totp_pending_user', $user);
            Session::set('totp_master_login', true);
            $this->redirect('2fa/verify');
        }

        $this->userModel->updateLastLogin($user['id']);
        Auth::login($user);
        $this->logActivity($user['id'], 'master_login', 'users', $user['id'], 'Logowanie superadmina');
        $this->redirect('admin/dashboard');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $userId = Auth::id();
        $this->logActivity($userId, 'logout', 'users', $userId, 'Wylogowanie z systemu');
        Auth::logout();
        $this->redirect('auth/login');
    }

    // ── Stop impersonation ───────────────────────────────────────────────────
    // This must live in AuthController (not AdminController) because during
    // impersonation Auth::isSuperAdmin() returns false, so AdminController's
    // requireSuperAdmin() would block access before the method runs.

    public function stopImpersonation(): void
    {
        if (!Auth::isImpersonating()) {
            $this->redirect('dashboard');
        }
        Auth::stopImpersonation();
        Session::flash('success', 'Zakończono impersonację. Wróciłeś do konta superadmina.');
        $this->redirect('admin/dashboard');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function redirectAfterLogin(): void
    {
        $intended = Session::get('intended_url');
        Session::remove('intended_url');
        if ($intended) {
            $this->redirect($intended);
        }

        $this->redirect(match(Auth::role() ?? '') {
            'sędzia', 'instruktor' => 'competitions',
            default                 => 'dashboard',
        });
    }

    private function getActiveClubs(): array
    {
        try {
            return Database::pdo()
                ->query("SELECT id, name, short_name FROM clubs WHERE is_active=1 AND (is_demo IS NULL OR is_demo=0) ORDER BY name")
                ->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    private function logActivity(?int $userId, string $action, string $entity, ?int $entityId, string $details): void
    {
        try {
            $this->userModel->getDb()->prepare(
                "INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)"
            )->execute([$userId, $action, $entity, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (\Throwable) {}
    }
}
