<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Totp;

/**
 * Two-Factor Authentication (TOTP) — for admin users.
 *
 * GET  /2fa/setup    — show QR code + secret
 * POST /2fa/setup    — enable 2FA (verify first code)
 * POST /2fa/disable  — disable 2FA (requires current code)
 * GET  /2fa/verify   — show verification form (after login)
 * POST /2fa/verify   — verify code and complete login
 */
class TwoFactorController extends BaseController
{
    private \PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->db = Database::getInstance();
    }

    // ── Setup (enable 2FA) ────────────────────────────────────────────

    public function setup(): void
    {
        $user   = Auth::user();
        $userId = (int)$user['id'];

        // Generate a temporary secret stored in session until confirmed
        if (!Session::get('totp_pending_secret')) {
            Session::set('totp_pending_secret', Totp::generateSecret());
        }
        $secret    = Session::get('totp_pending_secret');
        $otpUrl    = Totp::otpauthUrl($secret, $user['username']);
        $qrUrl     = Totp::qrCodeUrl($otpUrl);

        $this->render('2fa/setup', [
            'title'      => 'Bezpieczeństwo konta',
            'secret'     => $secret,
            'qrUrl'      => $qrUrl,
            'otpUrl'     => $otpUrl,
            'is_enabled' => $this->isEnabled($userId),
            'pw_error'   => Session::getFlash('pw_error'),
            'pw_success' => Session::getFlash('pw_success'),
        ]);
    }

    public function enable(): void
    {
        Csrf::verify();
        $code   = trim($_POST['code'] ?? '');
        $secret = Session::get('totp_pending_secret');
        $userId = (int)Auth::id();

        if (!$secret || !Totp::verify($secret, $code)) {
            Session::flash('error', 'Nieprawidłowy kod. Spróbuj ponownie.');
            $this->redirect('2fa/setup');
        }

        // Save secret + generate backup codes
        $this->db->prepare(
            "UPDATE users SET totp_secret=?, totp_enabled=1 WHERE id=?"
        )->execute([$secret, $userId]);

        $this->saveBackupCodes($userId);
        Session::remove('totp_pending_secret');

        Session::flash('success', '2FA zostało włączone. Zapisz kody zapasowe!');
        $this->redirect('2fa/backup-codes');
    }

    public function disable(): void
    {
        Csrf::verify();
        $code   = trim($_POST['code'] ?? '');
        $userId = (int)Auth::id();

        $stmt = $this->db->prepare("SELECT totp_secret FROM users WHERE id=? AND totp_enabled=1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !Totp::verify($row['totp_secret'], $code)) {
            Session::flash('error', 'Nieprawidłowy kod weryfikacyjny.');
            $this->redirect('2fa/setup');
        }

        $this->db->prepare(
            "UPDATE users SET totp_secret=NULL, totp_enabled=0 WHERE id=?"
        )->execute([$userId]);
        $this->db->prepare("DELETE FROM totp_backup_codes WHERE user_id=?")->execute([$userId]);

        Session::flash('success', '2FA zostało wyłączone.');
        $this->redirect('2fa/setup');
    }

    // ── Backup codes ──────────────────────────────────────────────────

    public function backupCodes(): void
    {
        $codes = Session::get('totp_new_backup_codes', []);
        $this->render('2fa/backup_codes', [
            'title' => 'Kody zapasowe 2FA',
            'codes' => $codes,
        ]);
    }

    // ── Verify (after login) ──────────────────────────────────────────

    public function showVerify(): void
    {
        if (!Session::get('totp_required')) {
            $this->redirect('dashboard');
        }
        $this->view->setLayout('auth');
        $this->render('2fa/verify', ['title' => 'Weryfikacja 2FA']);
    }

    public function verify(): void
    {
        Csrf::verify();
        if (!Session::get('totp_required')) {
            $this->redirect('dashboard');
        }

        $code   = trim($_POST['code'] ?? '');
        $userId = (int)Session::get('totp_pending_user_id');

        $stmt = $this->db->prepare("SELECT totp_secret FROM users WHERE id=? AND totp_enabled=1");
        $stmt->execute([$userId]);
        $row  = $stmt->fetch();

        $verified = ($row && Totp::verify($row['totp_secret'], $code))
                 || $this->useBackupCode($userId, $code);

        if ($verified) {
            // Complete the login that was interrupted by 2FA
            $pendingUser = Session::get('totp_pending_user');
            Session::remove('totp_required');
            Session::remove('totp_pending_user_id');
            Session::remove('totp_pending_user');

            if ($pendingUser) {
                $pendingUser['totp_verified'] = true;
                Auth::login($pendingUser);
                try {
                    $db = Database::getInstance();
                    $db->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$userId]);
                } catch (\Throwable) {}
            }

            $this->redirect('dashboard');
        }

        Session::flash('error', 'Nieprawidłowy kod 2FA.');
        $this->redirect('2fa/verify');
    }

    // ── Password change ───────────────────────────────────────────────

    public function changePassword(): void
    {
        Csrf::verify();
        $userId  = (int)Auth::id();
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            Session::flash('pw_error', 'Nowe hasło musi mieć co najmniej 8 znaków.');
            $this->redirect('2fa/setup');
        }
        if ($new !== $confirm) {
            Session::flash('pw_error', 'Hasła nie są zgodne.');
            $this->redirect('2fa/setup');
        }

        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            Session::flash('pw_error', 'Aktualne hasło jest nieprawidłowe.');
            $this->redirect('2fa/setup');
        }

        $this->db->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);

        Session::flash('pw_success', 'Hasło zostało zmienione.');
        $this->redirect('2fa/setup');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function isEnabled(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT totp_enabled FROM users WHERE id=?");
            $stmt->execute([$userId]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) { return false; }
    }

    private function saveBackupCodes(int $userId): void
    {
        $this->db->prepare("DELETE FROM totp_backup_codes WHERE user_id=?")->execute([$userId]);
        $plain = [];
        for ($i = 0; $i < 8; $i++) {
            $code   = strtoupper(bin2hex(random_bytes(4)));
            $hash   = hash('sha256', $code);
            $plain[] = $code;
            $this->db->prepare(
                "INSERT INTO totp_backup_codes (user_id, code_hash) VALUES (?,?)"
            )->execute([$userId, $hash]);
        }
        Session::set('totp_new_backup_codes', $plain);
    }

    private function useBackupCode(int $userId, string $code): bool
    {
        $code = strtoupper(preg_replace('/[^A-F0-9]/', '', $code));
        if (strlen($code) !== 8) return false;
        $hash = hash('sha256', $code);
        $stmt = $this->db->prepare(
            "SELECT id FROM totp_backup_codes WHERE user_id=? AND code_hash=? AND used_at IS NULL"
        );
        $stmt->execute([$userId, $hash]);
        $row = $stmt->fetch();
        if (!$row) return false;
        $this->db->prepare("UPDATE totp_backup_codes SET used_at=NOW() WHERE id=?")->execute([$row['id']]);
        return true;
    }
}
