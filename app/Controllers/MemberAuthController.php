<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Helpers\View;
use App\Helpers\Database;

/**
 * Handles member portal authentication:
 *   GET/POST /portal/login
 *   GET      /portal/logout
 *   GET/POST /portal/change-password
 *   GET/POST /portal/reset-password
 */
class MemberAuthController
{
    private View $view;

    public function __construct()
    {
        Session::start();
        $this->view = new View();
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (MemberAuth::check()) {
            $this->redirectTo('portal');
        }
        if (\App\Helpers\Auth::check()) {
            $this->redirectTo('dashboard');
        }
        $this->renderAuth('portal/login', ['title' => 'Logowanie — Klub Strzelecki']);
    }

    public function login(): void
    {
        Csrf::verify();

        $login    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$login || !$password) {
            Session::flash('error', 'Podaj e-mail / PESEL / nr licencji i hasło.');
            $this->redirectTo('portal/login');
        }

        $db      = Database::getInstance();
        $clubId  = ClubContext::current();

        // Wyszukaj zawodnika po email, PESEL lub numerze licencji
        // Jeśli subdomena ustawiona — szukaj tylko w tym klubie
        if ($clubId !== null) {
            $stmt = $db->prepare(
                "SELECT m.* FROM members m
                 LEFT JOIN licenses l ON l.member_id = m.id
                 WHERE m.club_id = ? AND (m.email = ? OR m.pesel = ? OR l.license_number = ?)
                 LIMIT 1"
            );
            $stmt->execute([$clubId, $login, $login, $login]);
        } else {
            // Bez subdomeny — szukaj globalnie (email lub PESEL)
            $stmt = $db->prepare(
                "SELECT m.* FROM members m
                 LEFT JOIN licenses l ON l.member_id = m.id
                 WHERE m.email = ? OR m.pesel = ? OR l.license_number = ?
                 LIMIT 1"
            );
            $stmt->execute([$login, $login, $login]);
        }
        $member = $stmt->fetch();

        if (!$member) {
            Session::flash('error', 'Nieprawidłowy e-mail lub hasło.');
            $this->redirectTo('portal/login');
        }

        // Status check
        if ($member['status'] === 'zawieszony') {
            Session::flash('error', 'Konto zawieszone. Skontaktuj się z biurem klubu.');
            $this->redirectTo('portal/login');
        }
        if ($member['status'] === 'wykreślony') {
            Session::flash('error', 'Konto wykreślone. Skontaktuj się z biurem klubu.');
            $this->redirectTo('portal/login');
        }

        // First-time login: password_hash is NULL → use PESEL as password
        if (empty($member['password_hash'])) {
            $pesel = trim($member['pesel'] ?? '');
            if (!$pesel || $password !== $pesel) {
                Session::flash('error', 'Nieprawidłowy e-mail lub hasło.');
                $this->redirectTo('portal/login');
            }
            // Bootstrap: hash the PESEL, force password change
            $hash = password_hash($pesel, PASSWORD_DEFAULT);
            $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 1 WHERE id = ?")
               ->execute([$hash, $member['id']]);
            $member['password_hash']        = $hash;
            $member['must_change_password'] = 1;
        }

        if (!password_verify($password, $member['password_hash'])) {
            Session::flash('error', 'Nieprawidłowy e-mail lub hasło.');
            $this->redirectTo('portal/login');
        }

        MemberAuth::login($member);

        // Ustaw kontekst klubu zawodnika
        if (!empty($member['club_id'])) {
            ClubContext::set((int)$member['club_id']);
        }

        if ($member['must_change_password']) {
            $this->redirectTo('portal/change-password');
        }
        $this->redirectTo('portal');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        MemberAuth::logout();
        Session::flash('success', 'Wylogowano z portalu zawodnika.');
        $this->redirectTo('portal/login');
    }

    // ── Change password ───────────────────────────────────────────────────────

    public function showChangePassword(): void
    {
        MemberAuth::requireLogin();
        $this->renderPortal('portal/change_password', [
            'title'      => 'Zmiana hasła',
            'memberUser' => MemberAuth::member(),
            'firstLogin' => MemberAuth::mustChangePassword(),
        ]);
    }

    public function changePassword(): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();

        $memberId = MemberAuth::id();
        $newPwd   = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (strlen($newPwd) < 8) {
            Session::flash('error', 'Hasło musi mieć co najmniej 8 znaków.');
            $this->redirectTo('portal/change-password');
        }
        if ($newPwd !== $confirm) {
            Session::flash('error', 'Hasła nie są identyczne.');
            $this->redirectTo('portal/change-password');
        }

        // Prevent using PESEL as new password
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT pesel FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();
        if ($row && trim($row['pesel'] ?? '') === $newPwd) {
            Session::flash('error', 'Nowe hasło nie może być numerem PESEL.');
            $this->redirectTo('portal/change-password');
        }

        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 0 WHERE id = ?")
           ->execute([$hash, $memberId]);

        // Update session flag
        Session::set('must_change_password', false);

        Session::flash('success', 'Hasło zostało zmienione.');
        $this->redirectTo('portal');
    }

    // ── Reset password ────────────────────────────────────────────────────────

    public function showResetPassword(): void
    {
        if (MemberAuth::check()) {
            $this->redirectTo('portal');
        }
        $this->renderAuth('portal/reset_password', ['title' => 'Reset hasła']);
    }

    public function resetPassword(): void
    {
        Csrf::verify();

        $email = trim($_POST['email'] ?? '');
        $pesel = trim($_POST['pesel'] ?? '');

        if (!$email || !$pesel) {
            Session::flash('error', 'Podaj e-mail i PESEL.');
            $this->redirectTo('portal/reset-password');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT id, pesel FROM members WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if (!$member || trim($member['pesel'] ?? '') !== $pesel) {
            Session::flash('error', 'Podane dane nie pasują do żadnego konta.');
            $this->redirectTo('portal/reset-password');
        }

        $hash = password_hash($pesel, PASSWORD_DEFAULT);
        $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 1 WHERE id = ?")
           ->execute([$hash, $member['id']]);

        Session::flash('success', 'Hasło zresetowane — zaloguj się używając numeru PESEL jako hasła.');
        $this->redirectTo('portal/login');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function renderPortal(string $template, array $data = []): void
    {
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');
        $data['flashWarning'] = Session::getFlash('warning');
        $this->view->setLayout('portal');
        $this->view->render($template, $data);
    }

    private function renderAuth(string $template, array $data = []): void
    {
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');
        $data['flashWarning'] = Session::getFlash('warning');
        $this->view->setLayout('auth');
        $this->view->render($template, $data);
    }

    private function redirectTo(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}
