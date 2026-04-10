<?php

namespace App\Helpers;

/**
 * Seeds a freshly created demo club with realistic sample data.
 * All demo users share the password defined in DEMO_PASSWORD.
 */
class DemoSeeder
{
    public const DEMO_PASSWORD = 'Demo2024!';

    private static \PDO $db;
    private static int  $clubId;

    /** Main entry-point called by DemoController */
    public static function seed(int $clubId): array
    {
        self::$db     = Database::pdo();
        self::$clubId = $clubId;

        $users    = self::createUsers();
        $memberIds = self::createMembers();
        self::assignMemberships($memberIds);
        self::createLicenses($memberIds);
        self::createWeapons();
        self::createAmmo();
        self::createTrainings($memberIds);
        self::createCompetitions($memberIds);
        self::createAnnouncements();
        self::createCalendarEvents();
        self::createClubFees($memberIds);

        return $users;
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    private static function createUsers(): array
    {
        $hash = password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT);
        $cid  = self::$clubId;
        $db   = self::$db;
        $users = [];

        $roles = [
            ['zarzad',   'demo_zarzad_' . $cid,   'Jan Prezes',    'prezes@demo' . $cid . '.pl'],
            ['instruktor','demo_instr_' . $cid,   'Piotr Trener',  'trener@demo' . $cid . '.pl'],
            ['sędzia',   'demo_sedzia_' . $cid,   'Maria Sędzia',  'sedzia@demo' . $cid . '.pl'],
        ];

        foreach ($roles as [$role, $username, $fullName, $email]) {
            $db->prepare(
                "INSERT INTO users (username, full_name, email, password, is_active, is_demo)
                 VALUES (?, ?, ?, ?, 1, 1)"
            )->execute([$username, $fullName, $email, $hash]);

            $userId = (int)$db->lastInsertId();

            $db->prepare(
                "INSERT INTO user_clubs (user_id, club_id, role, is_active) VALUES (?, ?, ?, 1)"
            )->execute([$userId, $cid, $role]);

            $users[] = [
                'role'     => $role,
                'username' => $username,
                'email'    => $email,
                'password' => self::DEMO_PASSWORD,
            ];
        }

        return $users;
    }

    // ── Members ───────────────────────────────────────────────────────────────

    private static function createMembers(): array
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $people = [
            ['Anna',      'Kowalska',      'F', '1990-03-15', '1990031512345', 'anna.kowalska@demo' . $cid . '.pl', '48100200301'],
            ['Marek',     'Nowak',         'M', '1985-07-22', '1985072212345', 'marek.nowak@demo' . $cid . '.pl',   '48100200302'],
            ['Katarzyna', 'Wiśniewska',    'F', '1992-11-08', '1992110812345', 'k.wisniewska@demo' . $cid . '.pl',  null],
            ['Piotr',     'Wójcik',        'M', '1978-04-30', '1978043012345', 'p.wojcik@demo' . $cid . '.pl',      '48100200304'],
            ['Magdalena', 'Lewandowska',   'F', '1995-06-14', '1995061412345', null,                                 null],
            ['Tomasz',    'Kamiński',      'M', '1980-09-03', '1980090312345', null,                                 '48100200306'],
            ['Alicja',    'Zielińska',     'F', '1998-01-25', '1998012512345', null,                                 null],
            ['Robert',    'Szymański',     'M', '1975-12-17', '1975121712345', null,                                 null],
            ['Barbara',   'Woźniak',       'F', '1988-05-09', '1988050912345', null,                                 null],
            ['Krzysztof', 'Kozłowski',     'M', '1983-08-21', '1983082112345', null,                                 null],
            ['Monika',    'Jankowska',     'F', '1993-02-28', '1993022812345', null,                                 null],
            ['Andrzej',   'Mazur',         'M', '1970-10-05', '1970100512345', null,                                 null],
        ];

        $ids = [];
        foreach ($people as $p) {
            $db->prepare(
                "INSERT INTO members
                    (club_id, first_name, last_name, gender, birth_date, pesel, email, phone, status, joined_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktywny', CURDATE())"
            )->execute([$cid, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]]);
            $ids[] = (int)$db->lastInsertId();
        }

        // Give Anna Kowalska a portal account
        $hash = password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT);
        $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 0 WHERE id = ? LIMIT 1")
           ->execute([$hash, $ids[0]]);

        return $ids;
    }

    private static function assignMemberships(array $memberIds): void
    {
        // Assign disciplines to members (1=pistol, 2=rifle assumed global)
        $db  = self::$db;
        $disc = $db->query("SELECT id FROM disciplines WHERE club_id IS NULL LIMIT 3")->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($disc)) {
            return;
        }
        foreach ($memberIds as $i => $memberId) {
            $db->prepare(
                "INSERT IGNORE INTO member_disciplines (member_id, discipline_id) VALUES (?, ?)"
            )->execute([$memberId, $disc[$i % count($disc)]]);
        }
    }

    // ── Licenses ──────────────────────────────────────────────────────────────

    private static function createLicenses(array $memberIds): void
    {
        $db  = self::$db;
        $typeId = $db->query("SELECT id FROM license_types WHERE club_id IS NULL LIMIT 1")->fetchColumn();
        if (!$typeId) {
            return;
        }

        $year = date('Y');
        foreach (array_slice($memberIds, 0, 8) as $i => $memberId) {
            $expiry = ($i < 6) ? ($year + 1) . '-12-31' : ($year - 1) . '-12-31'; // 2 expired
            $db->prepare(
                "INSERT INTO licenses (member_id, license_type_id, license_number, issued_date, expiry_date, is_active)
                 VALUES (?, ?, ?, CURDATE(), ?, ?)"
            )->execute([$memberId, $typeId, 'LIC-' . $year . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT), $expiry, ($i < 6) ? 1 : 0]);
        }
    }

    // ── Weapons ───────────────────────────────────────────────────────────────

    private static function createWeapons(): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $weapons = [
            ['Pistolet Glock 17',    'pistolet', '9mm',  'GL17-001-DEMO'],
            ['Pistolet Walther P99', 'pistolet', '9mm',  'WP99-002-DEMO'],
            ['Pistolet CZ 75',       'pistolet', '9mm',  'CZ75-003-DEMO'],
            ['Karabin AR-15',        'karabin',  '.223', 'AR15-004-DEMO'],
            ['Karabin Tikka T3',     'karabin',  '.308', 'TK3-005-DEMO'],
            ['Strzelba Mossberg',    'strzelba', '12/70','MS50-006-DEMO'],
        ];

        foreach ($weapons as [$name, $type, $caliber, $serial]) {
            $db->prepare(
                "INSERT INTO weapons (club_id, name, type, caliber, serial_number, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)"
            )->execute([$cid, $name, $type, $caliber, $serial]);
        }
    }

    // ── Ammo ──────────────────────────────────────────────────────────────────

    private static function createAmmo(): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $rows = [
            ['Amunicja 9mm FMJ',  '9mm',  500, 200],
            ['Amunicja .223 FMJ', '.223', 300, 100],
            ['Amunicja .308 HPBT','.308', 150,  50],
        ];

        foreach ($rows as [$name, $caliber, $qty_initial, $qty_current]) {
            $db->prepare(
                "INSERT INTO ammo_stock (club_id, name, caliber, quantity_initial, quantity_current)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$cid, $name, $caliber, $qty_initial, $qty_current]);
        }
    }

    // ── Trainings ─────────────────────────────────────────────────────────────

    private static function createTrainings(array $memberIds): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $trainings = [
            ['Trening grupowy — pistolety', date('Y-m-d', strtotime('-14 days')), 'zakończony',  array_slice($memberIds, 0, 6)],
            ['Trening indywidualny',        date('Y-m-d', strtotime('-7 days')),  'zakończony',  array_slice($memberIds, 0, 3)],
            ['Trening przygotowawczy',      date('Y-m-d', strtotime('+7 days')),  'zaplanowany', array_slice($memberIds, 2, 5)],
        ];

        foreach ($trainings as [$title, $dt, $status, $participants]) {
            $db->prepare(
                "INSERT INTO trainings (club_id, title, training_date, status) VALUES (?, ?, ?, ?)"
            )->execute([$cid, $title, $dt, $status]);
            $trainingId = (int)$db->lastInsertId();

            foreach ($participants as $memberId) {
                $db->prepare(
                    "INSERT IGNORE INTO training_entries (training_id, member_id, attended)
                     VALUES (?, ?, ?)"
                )->execute([$trainingId, $memberId, $status === 'zakończony' ? 1 : 0]);
            }
        }
    }

    // ── Competitions ──────────────────────────────────────────────────────────

    private static function createCompetitions(array $memberIds): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        // Past competition with results
        $db->prepare(
            "INSERT INTO competitions (club_id, name, competition_date, location, status, description)
             VALUES (?, 'Zawody Wiosenne " . date('Y') . "', ?, 'Strzelnica miejska', 'zakończone', 'Zawody strzeleckie dla zawodników klubu')"
        )->execute([$cid, date('Y-m-d', strtotime('-30 days'))]);
        $compId1 = (int)$db->lastInsertId();

        foreach (array_slice($memberIds, 0, 8) as $rank => $memberId) {
            $score = 95 - $rank * 3 + rand(-2, 2);
            $db->prepare(
                "INSERT INTO competition_entries (competition_id, member_id, final_score, final_place, status)
                 VALUES (?, ?, ?, ?, 'zatwierdzone')"
            )->execute([$compId1, $memberId, $score, $rank + 1]);
        }

        // Upcoming competition with registrations
        $db->prepare(
            "INSERT INTO competitions (club_id, name, competition_date, location, status, description, max_entries)
             VALUES (?, 'Mistrzostwa Klubowe " . date('Y') . "', ?, 'Strzelnica klubowa', 'otwarte', 'Coroczne mistrzostwa klubu we wszystkich dyscyplinach', 20)"
        )->execute([$cid, date('Y-m-d', strtotime('+21 days'))]);
        $compId2 = (int)$db->lastInsertId();

        foreach (array_slice($memberIds, 0, 5) as $memberId) {
            $db->prepare(
                "INSERT INTO competition_entries (competition_id, member_id, status) VALUES (?, ?, 'oczekujące')"
            )->execute([$compId2, $memberId]);
        }
    }

    // ── Announcements ─────────────────────────────────────────────────────────

    private static function createAnnouncements(): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $items = [
            ['Zebranie zarządu', 'Zebranie zarządu odbędzie się dnia ' . date('d.m.Y', strtotime('+10 days')) . ' o godz. 18:00 w siedzibie klubu. Obecność obowiązkowa.'],
            ['Nowe zasady korzystania ze strzelnicy', 'Informujemy, że od nowego miesiąca obowiązują zaktualizowane zasady korzystania ze strzelnicy. Prosimy o zapoznanie się z regulaminem dostępnym w biurze klubu.'],
        ];

        foreach ($items as [$title, $content]) {
            $db->prepare(
                "INSERT INTO announcements (club_id, title, content, is_active) VALUES (?, ?, ?, 1)"
            )->execute([$cid, $title, $content]);
        }
    }

    // ── Calendar events ───────────────────────────────────────────────────────

    private static function createCalendarEvents(): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $events = [
            ['Trening cotygodniowy',  date('Y-m-d', strtotime('+3 days')),  '10:00', '12:00', 'trening'],
            ['Dzień otwarty klubu',   date('Y-m-d', strtotime('+14 days')), '09:00', '17:00', 'wydarzenie'],
        ];

        foreach ($events as [$title, $dt, $timeStart, $timeEnd, $type]) {
            $db->prepare(
                "INSERT INTO calendar_events (club_id, title, event_date, time_start, time_end, type)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([$cid, $title, $dt, $timeStart, $timeEnd, $type]);
        }
    }

    // ── Club fees ─────────────────────────────────────────────────────────────

    private static function createClubFees(array $memberIds): void
    {
        $db   = self::$db;
        $cid  = self::$clubId;
        $year = (int)date('Y');

        foreach (array_slice($memberIds, 0, 10) as $i => $memberId) {
            $paid = $i < 7; // 7 paid, 3 unpaid
            $db->prepare(
                "INSERT INTO club_fees (club_id, member_id, fee_year, amount, is_paid, paid_date)
                 VALUES (?, ?, ?, 200.00, ?, ?)"
            )->execute([$cid, $memberId, $year, $paid ? 1 : 0, $paid ? date('Y-m-d', strtotime('-' . (30 + $i * 5) . ' days')) : null]);
        }
    }

    // ── Cleanup ───────────────────────────────────────────────────────────────

    /**
     * Delete ALL data for a demo club in the correct FK order.
     * Uses FOREIGN_KEY_CHECKS=0 for safety.
     */
    public static function destroy(int $clubId): void
    {
        $db = Database::pdo();
        $db->exec('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            // Sub-entities of competitions
            'competition_series_results', 'competition_event_results',
            'competition_results', 'competition_entries',
            'competition_events', 'competition_groups', 'competition_waitlist',
            'competitions',
            // Training
            'training_entries', 'trainings',
            // Member sub-entities
            'member_disciplines', 'member_medical_exams', 'member_weapons',
            'member_consents', 'licenses', 'club_fees', 'payments',
            'members',
            // Club misc
            'calendar_events', 'calendar_event_categories', 'announcements',
            'weapons', 'ammo_stock', 'payment_types',
            'email_queue', 'sms_queue', 'notifications', 'activity_log',
            'email_templates',
            // Billing & ads
            'club_subscriptions', 'billing_invoices', 'ads',
            'impersonation_log',
        ];

        foreach ($tables as $table) {
            try {
                $col = ($table === 'impersonation_log') ? 'target_club_id' : 'club_id';
                $db->prepare("DELETE FROM `{$table}` WHERE `{$col}` = ?")->execute([$clubId]);
            } catch (\Throwable) {
                // table may not exist yet
            }
        }

        // Remove user_clubs entries, then demo users
        $demoUserIds = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ?");
        $demoUserIds->execute([$clubId]);
        $uids = $demoUserIds->fetchAll(\PDO::FETCH_COLUMN);

        $db->prepare("DELETE FROM user_clubs WHERE club_id = ?")->execute([$clubId]);

        foreach ($uids as $uid) {
            // Only delete if user has is_demo=1
            $db->prepare("DELETE FROM users WHERE id = ? AND is_demo = 1 LIMIT 1")->execute([$uid]);
        }

        // Club core (cascades to club_settings, club_customization)
        $db->prepare("DELETE FROM `clubs` WHERE id = ? LIMIT 1")->execute([$clubId]);

        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
