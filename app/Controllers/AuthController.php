<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
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

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        // Unified login is at /portal/login — redirect there
        header('Location: ' . url('portal/login'));
        exit;
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

        // Rate limiting: 5 attempts per 15 min per IP+username
        $rlKey = RateLimiter::key('admin_login', ($_SERVER['REMOTE_ADDR'] ?? '') . $username);
        if (RateLimiter::isBlocked($rlKey)) {
            $secs = RateLimiter::secondsUntilReset($rlKey);
            $mins = (int)ceil($secs / 60);
            Session::flash('error', "Zbyt wiele prób logowania. Spróbuj za {$mins} min.");
            $this->redirect('auth/login');
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            RateLimiter::attempt($rlKey);
            $this->logActivity(null, 'login_failed', 'users', null, "Nieudana próba logowania: {$username}");
            Session::flash('error', 'Nieprawidłowy login lub hasło.');
            $this->redirect('auth/login');
        }
        RateLimiter::clear($rlKey);

        // Check if 2FA is required before completing login
        if (!empty($user['totp_enabled'])) {
            Session::set('totp_required', true);
            Session::set('totp_pending_user_id', $user['id']);
            // Store full user data temporarily for post-2FA login
            Session::set('totp_pending_user', $user);
            $this->redirect('2fa/verify');
        }

        $this->userModel->updateLastLogin($user['id']);
        Auth::login($user);
        $this->logActivity($user['id'], 'login', 'users', $user['id'], 'Zalogowanie do systemu');

        // --- Multi-club: ustal kontekst klubu ---
        if (!empty($user['is_super_admin'])) {
            // Super admin → panel globalny (chyba że subdomena wymusza klub)
            $subClubId = ClubContext::current();
            if ($subClubId !== null) {
                $role = $this->userModel->getRoleInClub($user['id'], $subClubId) ?? 'admin';
                Auth::setClub($subClubId, $role);
                $this->redirect('dashboard');
            }
            $this->redirect('admin/dashboard');
        }

        $clubs = $this->userModel->getClubsForUser($user['id']);

        if (count($clubs) === 0) {
            Session::flash('error', 'Twoje konto nie jest przypisane do żadnego klubu.');
            Auth::logout();
            $this->redirect('auth/login');
        }

        if (count($clubs) === 1) {
            Auth::setClub((int)$clubs[0]['club_id'], $clubs[0]['role']);
        } else {
            // Jeśli subdomena określa klub — użyj go
            $subClubId = ClubContext::current();
            if ($subClubId !== null) {
                $match = array_filter($clubs, fn($c) => (int)$c['club_id'] === $subClubId);
                if ($match) {
                    $c = reset($match);
                    Auth::setClub($subClubId, $c['role']);
                }
            }
            // Brak subdomeny + wiele klubów → selektor
            if (ClubContext::current() === null) {
                Session::set('pending_clubs', $clubs);
                $this->redirect('club-select');
            }
        }

        $intended = Session::get('intended_url');
        Session::remove('intended_url');

        if ($intended) {
            $this->redirect($intended);
        }

        // Role-based default landing page
        $effectiveRole = Auth::role() ?? 'admin';
        $this->redirect(match($effectiveRole) {
            'sędzia'     => 'competitions',
            'instruktor' => 'competitions',
            default      => 'dashboard',
        });
    }

    public function logout(): void
    {
        $userId = Auth::id();
        $this->logActivity($userId, 'logout', 'users', $userId, 'Wylogowanie z systemu');
        Auth::logout();
        $this->redirect('portal/login');
    }

    private function logActivity(?int $userId, string $action, string $entity, ?int $entityId, string $details): void
    {
        try {
            $stmt = $this->userModel->getDb()->prepare(
                "INSERT INTO activity_log (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$userId, $action, $entity, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (\Throwable) {
            // non-critical
        }
    }
}
