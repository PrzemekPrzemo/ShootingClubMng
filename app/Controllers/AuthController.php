<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
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
        $this->render('auth/login', ['title' => 'Logowanie']);
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

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            // Log failed attempt
            $this->logActivity(null, 'login_failed', 'users', null, "Nieudana próba logowania: {$username}");
            Session::flash('error', 'Nieprawidłowy login lub hasło.');
            $this->redirect('auth/login');
        }

        $this->userModel->updateLastLogin($user['id']);
        Auth::login($user);
        $this->logActivity($user['id'], 'login', 'users', $user['id'], 'Zalogowanie do systemu');

        $intended = Session::get('intended_url', 'dashboard');
        Session::remove('intended_url');
        $this->redirect($intended);
    }

    public function logout(): void
    {
        $userId = Auth::id();
        $this->logActivity($userId, 'logout', 'users', $userId, 'Wylogowanie z systemu');
        Auth::logout();
        $this->redirect('auth/login');
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
