<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\ClubCustomizationModel;
use App\Models\ClubSettingsModel;
use App\Models\SettingModel;
use App\Models\UserModel;

/**
 * Zarządzanie ustawieniami klubu — dostępne dla zarząd + admin.
 * Wymaga aktywnego kontekstu klubu.
 */
class ClubManagementController extends BaseController
{
    private ClubModel $clubModel;
    private ClubSettingsModel $settingsModel;
    private ClubCustomizationModel $customModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad']);
        $this->requireClubContext();

        $this->clubModel    = new ClubModel();
        $this->settingsModel = new ClubSettingsModel();
        $this->customModel  = new ClubCustomizationModel();
    }

    // ── Ustawienia klubu ─────────────────────────────────────────────────────

    public function settings(): void
    {
        $clubId = $this->currentClub();
        $club   = $this->clubModel->findById($clubId);
        $settings = $this->settingsModel->getAll($clubId);

        $this->render('club/settings', [
            'title'    => 'Ustawienia klubu',
            'club'     => $club,
            'settings' => $settings,
        ]);
    }

    public function saveSettings(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

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
            $this->redirect('club/settings');
        }

        $this->clubModel->updateClub($clubId, $data);

        // Notification settings per club
        $notifyKeys = ['notify_comp_days', 'notify_lic_days', 'notify_med_days'];
        foreach ($notifyKeys as $key) {
            if (isset($_POST[$key])) {
                $this->settingsModel->set($clubId, $key, trim($_POST[$key]));
            }
        }

        Session::flash('success', 'Zapisano ustawienia klubu.');
        $this->redirect('club/settings');
    }

    // ── Customizacja wizualna ────────────────────────────────────────────────

    public function customization(): void
    {
        $clubId = $this->currentClub();
        $custom = $this->customModel->getForClub($clubId);

        $this->render('club/customization', [
            'title'  => 'Wygląd klubu',
            'custom' => $custom,
        ]);
    }

    public function saveCustomization(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $data = [
            'primary_color' => trim($_POST['primary_color'] ?? '#0d6efd'),
            'navbar_bg'     => trim($_POST['navbar_bg'] ?? '#212529'),
            'custom_css'    => trim($_POST['custom_css'] ?? '') ?: null,
            'subdomain'     => trim($_POST['subdomain'] ?? '') ?: null,
        ];

        // Walidacja subdomeny
        if ($data['subdomain'] !== null) {
            $data['subdomain'] = preg_replace('/[^a-z0-9\-]/', '', strtolower($data['subdomain']));
            if ($this->customModel->isSubdomainTaken($data['subdomain'], $clubId)) {
                Session::flash('error', 'Ta subdomena jest już zajęta.');
                $this->redirect('club/customization');
            }
        }

        // Upload logo
        if (!empty($_FILES['logo']['tmp_name'])) {
            $allowed = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
            $mime = mime_content_type($_FILES['logo']['tmp_name']);
            if (in_array($mime, $allowed, true)) {
                $ext = match ($mime) {
                    'image/png'     => 'png',
                    'image/jpeg'    => 'jpg',
                    'image/svg+xml' => 'svg',
                    'image/webp'    => 'webp',
                    default         => 'png',
                };
                $dir = ROOT_PATH . '/storage/logos';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $filename = "club_{$clubId}.{$ext}";
                move_uploaded_file($_FILES['logo']['tmp_name'], "{$dir}/{$filename}");
                $data['logo_path'] = $filename;
            } else {
                Session::flash('warning', 'Dozwolone formaty logo: PNG, JPG, SVG, WebP.');
            }
        }

        $this->customModel->save($clubId, $data);
        Session::flash('success', 'Zapisano wygląd klubu.');
        $this->redirect('club/customization');
    }

    // ── SMTP per klub ────────────────────────────────────────────────────────

    public function smtp(): void
    {
        $clubId = $this->currentClub();
        $settings = $this->settingsModel->getAll($clubId);

        // Sprawdź czy admin zezwolił na SMTP per klub
        $globalAllow = (new SettingModel())->get('allow_club_smtp', '0');

        $this->render('club/smtp', [
            'title'       => 'Konfiguracja SMTP',
            'settings'    => $settings,
            'allowSmtp'   => (bool)$globalAllow,
        ]);
    }

    public function saveSmtp(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $keys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass_enc', 'smtp_from_email', 'smtp_from_name'];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $this->settingsModel->set($clubId, $key, trim($_POST[$key]));
            }
        }

        Session::flash('success', 'Zapisano konfigurację SMTP.');
        $this->redirect('club/smtp');
    }

    // ── Użytkownicy klubu ────────────────────────────────────────────────────

    public function users(): void
    {
        $clubId = $this->currentClub();
        $userModel = new UserModel();
        $users = $userModel->getUsersForClub($clubId);

        $this->render('club/users', [
            'title' => 'Kadra klubu',
            'users' => $users,
        ]);
    }

    // ── Logo serving ─────────────────────────────────────────────────────────

    public function serveLogo(): void
    {
        $clubId = ClubContext::current();
        if (!$clubId) {
            http_response_code(404);
            exit;
        }

        $custom = $this->customModel->getForClub($clubId);
        $path   = $custom['logo_path'] ?? null;

        if (!$path) {
            http_response_code(404);
            exit;
        }

        $fullPath = ROOT_PATH . '/storage/logos/' . $path;
        if (!file_exists($fullPath)) {
            http_response_code(404);
            exit;
        }

        $mime = mime_content_type($fullPath);
        header("Content-Type: {$mime}");
        header('Cache-Control: public, max-age=86400');
        readfile($fullPath);
        exit;
    }
}
