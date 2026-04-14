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
            $logoOk   = false;
            $logoMts  = '0';
            if ($logoFile === 'db') {
                $b64    = (string)($sm->get('system_logo_b64', '') ?: '');
                $logoOk = str_starts_with($b64, 'data:');
                $logoMts = $logoOk ? (string)crc32($b64) : '0';
            } elseif ($logoFile !== '') {
                $logoPath = ROOT_PATH . '/storage/system/' . basename($logoFile);
                $logoOk   = file_exists($logoPath);
                $logoMts  = $logoOk ? (string)filemtime($logoPath) : '0';
            }
            $systemBranding['name']    = $sm->get('system_name', 'Shootero') ?: 'Shootero';
            $systemBranding['logo']    = $logoOk ? $logoFile : '';
            $systemBranding['logoMts'] = $logoMts;
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

        if ($username === '' || $password === '') {
            Session::flash('error', 'Podaj login i hasło.');
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
            // Fallback — try member portal login (unified login UX)
            if ($this->tryMemberPortalLogin($username, $password)) {
                return; // member login succeeded and redirected
            }

            RateLimiter::attempt($rlKey);
            $this->logActivity(null, 'login_failed', 'users', null, "Nieudana próba: {$username}");
            Session::flash('error', 'Nieprawidłowy login lub hasło.');
            $this->redirect('auth/login');
        }
        RateLimiter::clear($rlKey);

        // 2FA (club context resolved later — store pending user, redirect)
        if (!empty($user['totp_enabled'])) {
            Session::set('totp_required', true);
            Session::set('totp_pending_user_id', $user['id']);
            Session::set('totp_pending_user', $user);
            Session::set('totp_pending_club_id', null); // resolved after 2FA
            $this->redirect('2fa/verify');
        }

        $this->userModel->updateLastLogin($user['id']);
        Auth::login($user);
        $this->logActivity($user['id'], 'login', 'users', $user['id'], 'Zalogowanie do systemu');

        $this->resolveClubContext($user);
    }

    /**
     * Resolves club/role context after credentials are verified.
     * Called from login() and from 2FA verify (after totp passes).
     * Priority: subdomain club → single club → club picker.
     */
    public function resolveClubContext(array $user): void
    {
        // Super admin — no club context needed
        if (!empty($user['is_super_admin'])) {
            $this->redirect('admin/dashboard');
        }

        // Subdomain pre-selects the club
        $subdomainClubId = ClubContext::current();
        if ($subdomainClubId !== null) {
            $roles = $this->userModel->getRolesInClub($user['id'], $subdomainClubId);
            if (empty($roles)) {
                Session::flash('error', 'Nie masz dostępu do tego klubu.');
                Auth::logout();
                $this->redirect('auth/login');
            }
            $this->applyClubAndRole($user['id'], $subdomainClubId, $roles);
            return;
        }

        // Get all clubs the user belongs to
        $clubs = $this->userModel->getClubsForUser($user['id']);

        if (empty($clubs)) {
            Session::flash('error', 'Twoje konto nie jest przypisane do żadnego klubu. Skontaktuj się z administratorem.');
            Auth::logout();
            $this->redirect('auth/login');
        }

        if (count($clubs) === 1) {
            // Single club — resolve role immediately
            $this->applyClubAndRole($user['id'], (int)$clubs[0]['club_id'], $clubs[0]['roles']);
            return;
        }

        // Multiple clubs — show club picker
        Session::set('pending_clubs', $clubs);
        $this->redirect('club-select');
    }

    /**
     * Fallback member portal login — tries to log in as an athlete (member)
     * when staff login fails. Unified login UX so users don't need to know
     * whether they're in users table or members table.
     *
     * @return bool true if login succeeded and redirect was issued, false otherwise.
     */
    private function tryMemberPortalLogin(string $login, string $password): bool
    {
        $login    = trim($login);
        $password = trim($password);
        if ($login === '' || $password === '') return false;

        $db      = Database::getInstance();
        $clubId  = ClubContext::current();
        $loginLc = mb_strtolower($login);

        if ($clubId !== null) {
            $stmt = $db->prepare(
                "SELECT DISTINCT m.* FROM members m
                 LEFT JOIN licenses l ON l.member_id = m.id
                 WHERE m.club_id = ?
                   AND (LOWER(m.email) = ? OR m.pesel = ? OR l.license_number = ?)
                 LIMIT 1"
            );
            $stmt->execute([$clubId, $loginLc, $login, $login]);
        } else {
            $stmt = $db->prepare(
                "SELECT DISTINCT m.* FROM members m
                 LEFT JOIN licenses l ON l.member_id = m.id
                 WHERE LOWER(m.email) = ? OR m.pesel = ? OR l.license_number = ?
                 LIMIT 1"
            );
            $stmt->execute([$loginLc, $login, $login]);
        }
        $member = $stmt->fetch();
        if (!$member) return false;

        // Status check — blocked members cannot log in
        if (in_array($member['status'], ['zawieszony', 'wykreślony', 'wykreslony'], true)) {
            return false;
        }

        $pesel     = trim((string)($member['pesel'] ?? ''));
        $verified  = false;

        if (!empty($member['password_hash'])) {
            if (password_verify($password, $member['password_hash'])) {
                $verified = true;
            } elseif ($pesel && $password === $pesel) {
                // PESEL fallback — reset hash to PESEL and force change
                $hash = password_hash($pesel, PASSWORD_DEFAULT);
                $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 1 WHERE id = ?")
                   ->execute([$hash, $member['id']]);
                $member['password_hash']        = $hash;
                $member['must_change_password'] = 1;
                $verified = true;
            }
        } elseif ($pesel && $password === $pesel) {
            // Bootstrap: first-time login uses PESEL
            $hash = password_hash($pesel, PASSWORD_DEFAULT);
            $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 1 WHERE id = ?")
               ->execute([$hash, $member['id']]);
            $member['password_hash']        = $hash;
            $member['must_change_password'] = 1;
            $verified = true;
        }

        if (!$verified) return false;

        // Log in as member
        \App\Helpers\MemberAuth::login($member);
        if (!empty($member['club_id'])) {
            ClubContext::set((int)$member['club_id']);
        }

        if (!empty($member['must_change_password'])) {
            header('Location: ' . url('portal/change-password'));
            exit;
        }
        header('Location: ' . url('portal'));
        exit;
    }

    /**
     * Sets club context; if multiple roles → show role select; else log in.
     */
    private function applyClubAndRole(int $userId, int $clubId, array $roles): void
    {
        if (count($roles) > 1) {
            Session::set('pending_role_select', [
                'user_id' => $userId,
                'club_id' => $clubId,
                'roles'   => $roles,
            ]);
            $this->redirect('auth/role-select');
        }

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
