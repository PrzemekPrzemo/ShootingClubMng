<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
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

        $this->render('auth/login', [
            'title'        => 'Logowanie',
            'clubs'        => $this->getActiveClubs(),
            'clubBranding' => [],
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

        // Verify club membership
        $role = $this->userModel->getRoleInClub($user['id'], $clubId);
        if ($role === null) {
            Session::flash('error', 'Nie masz dostępu do wybranego klubu.');
            Auth::logout();
            $this->redirect('auth/login');
        }

        Auth::setClub($clubId, $role);
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
