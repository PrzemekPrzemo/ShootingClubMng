<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\DemoSeeder;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;
use App\Models\ClubSettingsModel;

/**
 * Manages demo environments for prospective customers.
 * Public: /demo               — landing page with credentials
 * Admin:  /admin/demos        — list & management (super admin only)
 */
class DemoController extends BaseController
{
    // ── Public landing ────────────────────────────────────────────────────────

    /** GET /demo?token=TOKEN — shows demo credentials (no auth required) */
    public function landing(): void
    {
        $db    = Database::getInstance();
        $token = trim($_GET['token'] ?? '');

        if ($token !== '') {
            $stmt = $db->prepare(
                "SELECT * FROM clubs WHERE demo_token = ? AND is_demo = 1 LIMIT 1"
            );
            $stmt->execute([$token]);
            $demo = $stmt->fetch();
        } else {
            // Latest active demo
            $stmt = $db->query(
                "SELECT * FROM clubs WHERE is_demo = 1 AND (demo_expires_at IS NULL OR demo_expires_at > NOW())
                 ORDER BY id DESC LIMIT 1"
            );
            $demo = $stmt->fetch();
        }

        if (!$demo) {
            $this->render('demo/no_demo', ['title' => 'Brak aktywnego demo']);
            return;
        }

        // Fetch demo users for this club
        $usersStmt = $db->prepare(
            "SELECT u.username, u.email, uc.role
             FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND u.is_demo = 1
             ORDER BY FIELD(uc.role, 'zarzad','instruktor','sędzia')"
        );
        $usersStmt->execute([$demo['id']]);
        $demoUsers = $usersStmt->fetchAll();

        // Fetch all portal members, with their linked staff username/role if any
        $portalStmt = $db->prepare(
            "SELECT m.first_name, m.last_name, m.email,
                    u.username AS linked_username, uc.role AS linked_role
             FROM members m
             LEFT JOIN users u ON u.member_id = m.id AND u.is_demo = 1
             LEFT JOIN user_clubs uc ON uc.user_id = u.id AND uc.club_id = m.club_id
             WHERE m.club_id = ? AND m.password_hash IS NOT NULL AND m.must_change_password = 0
             ORDER BY m.id ASC"
        );
        $portalStmt->execute([$demo['id']]);
        $portalMembers = $portalStmt->fetchAll();

        $this->render('demo/landing', [
            'title'         => 'Środowisko demonstracyjne — ' . $demo['name'],
            'demo'          => $demo,
            'demoUsers'     => $demoUsers,
            'portalMembers' => $portalMembers,
            'password'      => DemoSeeder::DEMO_PASSWORD,
        ]);
    }

    // ── Super-admin management ────────────────────────────────────────────────

    /** GET /admin/demos */
    public function adminIndex(): void
    {
        $this->requireSuperAdmin();
        $db = Database::getInstance();

        $demos = $db->query(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM users u JOIN user_clubs uc ON uc.user_id=u.id
                     WHERE uc.club_id=c.id AND u.is_demo=1) AS user_count,
                    (SELECT COUNT(*) FROM members WHERE club_id=c.id) AS member_count
             FROM clubs c WHERE c.is_demo=1
             ORDER BY c.id DESC"
        )->fetchAll();

        $this->render('admin/demos', [
            'title' => 'Środowiska demo',
            'demos' => $demos,
        ]);
    }

    /** POST /admin/demos — create new demo environment */
    public function adminCreate(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $name     = trim($_POST['name'] ?? '') ?: ('Demo ' . date('Y-m-d H:i'));
        $hours    = max(1, min(720, (int)($_POST['expires_hours'] ?? 24)));
        $shortName = strtoupper(preg_replace('/[^a-z0-9]/i', '', $name));
        $shortName = substr($shortName ?: 'DEMO', 0, 8);
        $token    = bin2hex(random_bytes(16));

        $db = Database::getInstance();

        $db->prepare(
            "INSERT INTO clubs (name, short_name, is_active, is_demo, demo_expires_at, demo_token)
             VALUES (?, ?, 1, 1, DATE_ADD(NOW(), INTERVAL ? HOUR), ?)"
        )->execute([$name, $shortName, $hours, $token]);
        $clubId = (int)$db->lastInsertId();

        // Default customization & settings
        (new ClubCustomizationModel())->save($clubId, []);
        $settings = new ClubSettingsModel();
        $settings->set($clubId, 'smtp_enabled', '0', 'SMTP', 'boolean');
        $settings->set($clubId, 'sms_enabled',  '0', 'SMS',  'boolean');

        // Seed sample data
        DemoSeeder::seed($clubId);

        Session::flash('success', "Środowisko demo \"{$name}\" zostało utworzone. Token: <code>{$token}</code>");
        $this->redirect('admin/demos');
    }

    /** POST /admin/demos/:id/reset — wipe data and re-seed */
    public function adminReset(string $id): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ? AND is_demo = 1 LIMIT 1");
        $stmt->execute([(int)$id]);
        $demo = $stmt->fetch();

        if (!$demo) {
            Session::flash('error', 'Demo nie istnieje.');
            $this->redirect('admin/demos');
        }

        // Wipe data but keep the club record + settings
        $this->wipeClubData((int)$id);
        (new ClubCustomizationModel())->save((int)$id, []);
        DemoSeeder::seed((int)$id);

        Session::flash('success', "Dane demo \"{$demo['name']}\" zostały zresetowane.");
        $this->redirect('admin/demos');
    }

    /** POST /admin/demos/:id/extend */
    public function adminExtend(string $id): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $hours = max(1, min(720, (int)($_POST['hours'] ?? 24)));
        $db    = Database::getInstance();

        $db->prepare(
            "UPDATE clubs SET demo_expires_at = DATE_ADD(GREATEST(demo_expires_at, NOW()), INTERVAL ? HOUR)
             WHERE id = ? AND is_demo = 1 LIMIT 1"
        )->execute([$hours, (int)$id]);

        Session::flash('success', "Przedłużono demo o {$hours}h.");
        $this->redirect('admin/demos');
    }

    /** POST /admin/demos/:id/delete */
    public function adminDelete(string $id): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT name FROM clubs WHERE id = ? AND is_demo = 1 LIMIT 1");
        $stmt->execute([(int)$id]);
        $name = $stmt->fetchColumn();

        if (!$name) {
            Session::flash('error', 'Demo nie istnieje.');
            $this->redirect('admin/demos');
        }

        DemoSeeder::destroy((int)$id);

        Session::flash('success', "Środowisko demo \"{$name}\" zostało usunięte.");
        $this->redirect('admin/demos');
    }

    // ── Quick-login (super admin shortcut) ────────────────────────────────────

    /**
     * GET /admin/demos/:id/login?role=zarzad
     * Impersonates a demo user as the super admin (for review purposes).
     */
    public function adminQuickLogin(string $id): void
    {
        $this->requireSuperAdmin();

        $role = $_GET['role'] ?? 'zarzad';
        $db   = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT u.* FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND uc.role = ? AND u.is_demo = 1
             LIMIT 1"
        );
        $stmt->execute([(int)$id, $role]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::flash('error', 'Nie znaleziono użytkownika demo dla tej roli.');
            $this->redirect('admin/demos');
        }

        Auth::impersonateClubUser($user, (int)$id, $role);
        Session::flash('warning', "Tryb demo: zalogowano jako <strong>{$user['full_name']}</strong>. <a href='" . url('admin/stop-impersonation') . "'>Zakończ</a>");
        $this->redirect('dashboard');
    }

    /** GET /admin/demos/:id/login-portal — impersonates the first demo portal member */
    public function adminQuickLoginPortal(string $id): void
    {
        $this->requireSuperAdmin();

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM members WHERE club_id = ? AND password_hash IS NOT NULL AND must_change_password = 0 LIMIT 1"
        );
        $stmt->execute([(int)$id]);
        $member = $stmt->fetch();

        if (!$member) {
            Session::flash('error', 'Brak zawodnika z dostępem do portalu w tym demo.');
            $this->redirect('admin/demos');
        }

        Auth::impersonateMember($member);
        Session::flash('warning', "Tryb demo: portal jako <strong>{$member['first_name']} {$member['last_name']}</strong>. <a href='" . url('admin/stop-impersonation') . "'>Zakończ</a>");
        $this->redirect('portal');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Wipe club operational data (for reset), keeping the club record itself */
    private function wipeClubData(int $clubId): void
    {
        $db = Database::pdo();
        $db->exec('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'competition_series_results', 'competition_event_results',
            'competition_results', 'competition_entries',
            'competition_events', 'competition_groups', 'competition_waitlist',
            'competitions',
            'training_entries', 'trainings',
            'member_disciplines', 'member_medical_exams', 'member_weapons',
            'member_consents', 'licenses', 'club_fees', 'payments',
            'members',
            'calendar_events', 'announcements',
            'weapons', 'ammo_stock',
            'email_queue', 'sms_queue', 'notifications',
        ];

        foreach ($tables as $table) {
            try {
                $db->prepare("DELETE FROM `{$table}` WHERE club_id = ?")->execute([$clubId]);
            } catch (\Throwable) {}
        }

        // Wipe demo users
        $uids = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ?");
        $uids->execute([$clubId]);
        foreach ($uids->fetchAll(\PDO::FETCH_COLUMN) as $uid) {
            $db->prepare("DELETE FROM users WHERE id = ? AND is_demo = 1 LIMIT 1")->execute([$uid]);
        }
        $db->prepare("DELETE FROM user_clubs WHERE club_id = ?")->execute([$clubId]);

        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
