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
        $stats = $this->clubModel->getGlobalStats();
        $clubs = $this->clubModel->getActive();

        $this->render('admin/dashboard', [
            'title'  => 'Panel administratora',
            'stats'  => $stats,
            'clubs'  => $clubs,
        ]);
    }

    // ── Clubs CRUD ───────────────────────────────────────────────────────────

    public function clubs(): void
    {
        $clubs = $this->clubModel->findAll('name');
        foreach ($clubs as &$club) {
            $club['stats'] = $this->clubModel->getStats($club['id']);
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
            'title' => 'Nowy klub',
            'club'  => null,
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

        $this->render('admin/club_form', [
            'title' => 'Edycja klubu — ' . $club['name'],
            'club'  => $club,
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
        $this->userModel->removeFromClub((int)$userId, (int)$clubId);
        Session::flash('success', 'Użytkownik usunięty z klubu.');
        $this->redirect("admin/clubs/{$clubId}/users");
    }

    // ── Global settings ──────────────────────────────────────────────────────

    public function settings(): void
    {
        $settingModel = new SettingModel();
        $settings = [];
        foreach (['base_domain', 'allow_club_smtp', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass_enc'] as $key) {
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

        Session::flash('success', 'Zapisano ustawienia globalne.');
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
