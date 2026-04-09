<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\EmailService;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\UserModel;

/**
 * Self-service club registration (onboarding).
 * GET  /register         — show registration form
 * POST /register         — process registration
 * GET  /register/confirm — e-mail confirmation link
 */
class RegistrationController extends BaseController
{
    private ClubModel $clubModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('auth');
        $this->clubModel = new ClubModel();
        $this->userModel = new UserModel();
    }

    public function show(): void
    {
        $this->render('register/form', [
            'title'   => 'Rejestracja klubu',
            'errors'  => [],
            'old'     => [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $data = [
            'club_name'    => trim($_POST['club_name'] ?? ''),
            'club_email'   => trim($_POST['club_email'] ?? ''),
            'club_phone'   => trim($_POST['club_phone'] ?? ''),
            'club_nip'     => trim($_POST['club_nip'] ?? ''),
            'admin_name'   => trim($_POST['admin_name'] ?? ''),
            'admin_email'  => trim($_POST['admin_email'] ?? ''),
            'admin_pass'   => $_POST['admin_pass'] ?? '',
            'admin_pass2'  => $_POST['admin_pass2'] ?? '',
        ];

        $errors = $this->validate($data);
        if ($errors) {
            $this->render('register/form', [
                'title'  => 'Rejestracja klubu',
                'errors' => $errors,
                'old'    => $data,
            ]);
            return;
        }

        // Check if admin e-mail already used
        if ($this->userModel->findByEmail($data['admin_email'])) {
            $this->render('register/form', [
                'title'  => 'Rejestracja klubu',
                'errors' => ['Ten adres e-mail jest już zarejestrowany.'],
                'old'    => $data,
            ]);
            return;
        }

        // Create club (inactive until confirmed)
        $token    = bin2hex(random_bytes(24));
        $trialEnd = date('Y-m-d', strtotime('+30 days'));

        $clubId = $this->clubModel->create([
            'name'               => $data['club_name'],
            'email'              => $data['club_email'],
            'phone'              => $data['club_phone'] ?: null,
            'nip'                => $data['club_nip'] ?: null,
            'is_active'          => 0,                     // activated after e-mail confirmation
            'trial_ends_at'      => $trialEnd,
            'registration_token' => $token,
        ]);

        // Create admin user for this club
        $username = 'admin_' . $clubId;
        $userId = $this->userModel->createUser([
            'username'   => $username,
            'email'      => $data['admin_email'],
            'full_name'  => $data['admin_name'],
            'password'   => $data['admin_pass'],
            'role'       => 'admin',
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Assign user to club as zarząd
        $db = \App\Helpers\Database::pdo();
        $db->prepare(
            "INSERT INTO user_clubs (user_id, club_id, role, is_active) VALUES (?, ?, 'zarzad', 1)"
        )->execute([$userId, $clubId]);

        // Create default trial subscription
        $db->prepare(
            "INSERT IGNORE INTO club_subscriptions (club_id, plan, valid_until, status) VALUES (?, 'trial', ?, 'active')"
        )->execute([$clubId, $trialEnd]);

        // Send confirmation e-mail
        $confirmUrl = url('register/confirm?token=' . $token . '&club=' . $clubId);
        $html = "<p>Dziękujemy za rejestrację <strong>" . htmlspecialchars($data['club_name']) . "</strong>!</p>"
            . "<p>Kliknij link poniżej, aby potwierdzić adres e-mail i aktywować konto:</p>"
            . "<p><a href=\"{$confirmUrl}\">{$confirmUrl}</a></p>"
            . "<p>Link jest ważny przez 48 godzin.</p>"
            . "<p>Twój okres próbny (30 dni) rozpocznie się po aktywacji.</p>";

        EmailService::send(
            1, // global club_id for system emails
            $data['club_email'],
            $data['club_name'],
            'Aktywuj konto — ' . $data['club_name'],
            $html
        );

        $this->render('register/confirm_sent', [
            'title' => 'Sprawdź skrzynkę e-mail',
            'email' => $data['club_email'],
        ]);
    }

    public function confirm(): void
    {
        $token  = $_GET['token'] ?? '';
        $clubId = (int)($_GET['club'] ?? 0);

        if (!$token || !$clubId) {
            $this->render('register/confirm_error', ['title' => 'Błąd aktywacji', 'message' => 'Nieprawidłowy link aktywacyjny.']);
            return;
        }

        $db   = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("SELECT id, is_active, registration_token FROM clubs WHERE id = ? AND registration_token = ? LIMIT 1");
        $stmt->execute([$clubId, $token]);
        $club = $stmt->fetch();

        if (!$club) {
            $this->render('register/confirm_error', ['title' => 'Błąd aktywacji', 'message' => 'Link aktywacyjny jest nieprawidłowy lub wygasł.']);
            return;
        }

        if ($club['is_active']) {
            // Already active
            Session::flash('success', 'Konto jest już aktywne. Możesz się zalogować.');
            $this->redirect('auth/login');
        }

        // Activate club
        $db->prepare("UPDATE clubs SET is_active = 1, registration_token = NULL WHERE id = ?")->execute([$clubId]);

        $this->render('register/confirmed', ['title' => 'Konto aktywowane!', 'clubId' => $clubId]);
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['club_name']))  $errors[] = 'Nazwa klubu jest wymagana.';
        if (empty($data['club_email']) || !filter_var($data['club_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Podaj prawidłowy adres e-mail klubu.';
        }
        if (empty($data['admin_name']))  $errors[] = 'Imię i nazwisko administratora jest wymagane.';
        if (empty($data['admin_email']) || !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Podaj prawidłowy adres e-mail administratora.';
        }
        if (strlen($data['admin_pass']) < 8) {
            $errors[] = 'Hasło musi mieć co najmniej 8 znaków.';
        }
        if ($data['admin_pass'] !== $data['admin_pass2']) {
            $errors[] = 'Hasła nie są identyczne.';
        }
        return $errors;
    }
}
