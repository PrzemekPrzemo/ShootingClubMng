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

        $userRecords = self::createUsers();
        $memberIds   = self::createMembers();
        // Link staff users → member records (for dual-context portal access)
        self::linkUsersToMembers($userRecords, $memberIds);
        self::assignMemberships($memberIds);
        self::createLicenses($memberIds);
        self::createWeapons();
        self::createAmmo();
        self::createTrainings($memberIds);
        self::createCompetitions($memberIds);
        self::createAnnouncements();
        self::createCalendarEvents();
        self::createClubFees($memberIds);
        self::createPortalData($memberIds);

        // Return only the public credentials (without internal ids)
        return array_map(fn($u) => [
            'role'     => $u['role'],
            'username' => $u['username'],
            'email'    => $u['email'],
            'password' => $u['password'],
        ], $userRecords);
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    /**
     * Creates 3 staff accounts (zarzad, instruktor, sedzia).
     * Returns array with internal 'id' field (used for member linking) plus display credentials.
     */
    private static function createUsers(): array
    {
        $hash = password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT);
        $cid  = self::$clubId;
        $db   = self::$db;
        $users = [];

        $roles = [
            ['zarzad',    'demo_zarzad_' . $cid,  'Jan Prezes',   'prezes@demo' . $cid . '.pl'],
            ['instruktor','demo_instr_' . $cid,   'Piotr Trener', 'trener@demo' . $cid . '.pl'],
            ['sędzia',   'demo_sedzia_' . $cid,   'Maria Sędzia', 'sedzia@demo' . $cid . '.pl'],
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
                'id'       => $userId,  // used by linkUsersToMembers()
                'role'     => $role,
                'username' => $username,
                'email'    => $email,
                'password' => self::DEMO_PASSWORD,
            ];
        }

        return $users;
    }

    /**
     * Link staff user accounts → member records so they can access the portal
     * in dual-context mode (same password, different view).
     *
     * Mapping:
     *   users[0] zarzad     → members[1] Marek Nowak
     *   users[1] instruktor → members[3] Piotr Wójcik
     *   users[2] sędzia     → members[2] Katarzyna Wiśniewska
     */
    private static function linkUsersToMembers(array $users, array $memberIds): void
    {
        $db = self::$db;
        $links = [
            0 => 1,  // zarzad → Marek Nowak
            1 => 3,  // instruktor → Piotr Wójcik
            2 => 2,  // sędzia → Katarzyna Wiśniewska
        ];
        foreach ($links as $userIdx => $memberIdx) {
            if (isset($users[$userIdx]['id']) && isset($memberIds[$memberIdx])) {
                $db->prepare("UPDATE users SET member_id = ? WHERE id = ? LIMIT 1")
                   ->execute([$memberIds[$memberIdx], $users[$userIdx]['id']]);
            }
        }
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

        $ids  = [];
        $year = date('Y');
        // member_number is UNIQUE globally — seed starting seq from the global max
        $maxStmt = $db->prepare(
            "SELECT MAX(CAST(SUBSTRING(member_number, 7) AS UNSIGNED))
             FROM members WHERE member_number LIKE ?"
        );
        $maxStmt->execute(["KS{$year}%"]);
        $seq = (int)$maxStmt->fetchColumn() + 1;

        foreach ($people as $p) {
            // Retry loop: skip any number that's already taken (handles race conditions
            // and leftover rows from partially-failed previous seedings)
            $inserted = false;
            while (!$inserted) {
                $memberNumber = sprintf('KS%s%04d', $year, $seq++);
                try {
                    $db->prepare(
                        "INSERT INTO members
                            (club_id, first_name, last_name, gender, birth_date, pesel, email, phone,
                             member_number, status, join_date)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktywny', CURDATE())"
                    )->execute([$cid, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $memberNumber]);
                    $ids[]    = (int)$db->lastInsertId();
                    $inserted = true;
                } catch (\PDOException $e) {
                    // Only swallow duplicate-member_number errors; rethrow everything else
                    if (str_contains($e->getMessage(), 'member_number')) {
                        continue; // bump seq and try next number
                    }
                    throw $e;
                }
            }
        }

        // Give first 4 members portal accounts (Anna, Marek, Katarzyna, Piotr W.)
        $hash = password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT);
        foreach (array_slice($ids, 0, 4) as $memberId) {
            $db->prepare("UPDATE members SET password_hash = ?, must_change_password = 0 WHERE id = ? LIMIT 1")
               ->execute([$hash, $memberId]);
        }

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
        $cid = self::$clubId;

        // Get a created_by user for this club
        $stmt = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ? LIMIT 1");
        $stmt->execute([$cid]);
        $createdBy = (int)$stmt->fetchColumn();
        if (!$createdBy) {
            return;
        }

        $year = date('Y');
        foreach (array_slice($memberIds, 0, 8) as $i => $memberId) {
            $expiry  = ($i < 6) ? ($year + 1) . '-12-31' : ($year - 1) . '-12-31'; // 2 expired
            $status  = ($i < 6) ? 'aktywna' : 'wygasla';
            $licNum  = 'LIC-' . $year . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $db->prepare(
                "INSERT INTO licenses
                    (member_id, license_type, license_number, issue_date, valid_until, status, created_by)
                 VALUES (?, 'zawodnicza', ?, CURDATE(), ?, ?, ?)"
            )->execute([$memberId, $licNum, $expiry, $status, $createdBy]);
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

        // status ENUM: 'planowany','odbyl_sie','odwolany'
        $trainings = [
            ['Trening grupowy — pistolety', date('Y-m-d', strtotime('-14 days')), 'odbyl_sie', array_slice($memberIds, 0, 6)],
            ['Trening indywidualny',        date('Y-m-d', strtotime('-7 days')),  'odbyl_sie', array_slice($memberIds, 0, 3)],
            ['Trening przygotowawczy',      date('Y-m-d', strtotime('+7 days')),  'planowany', array_slice($memberIds, 2, 5)],
        ];

        foreach ($trainings as [$title, $dt, $status, $participants]) {
            $db->prepare(
                "INSERT INTO trainings (club_id, title, training_date, status) VALUES (?, ?, ?, ?)"
            )->execute([$cid, $title, $dt, $status]);
            $trainingId = (int)$db->lastInsertId();

            foreach ($participants as $memberId) {
                $db->prepare(
                    "INSERT IGNORE INTO training_attendees (training_id, member_id, attended)
                     VALUES (?, ?, ?)"
                )->execute([$trainingId, $memberId, $status === 'odbyl_sie' ? 1 : 0]);
            }
        }
    }

    // ── Competitions ──────────────────────────────────────────────────────────

    private static function createCompetitions(array $memberIds): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        // Get a user_id for registered_by / entered_by fields
        $stmt = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ? LIMIT 1");
        $stmt->execute([$cid]);
        $userId = (int)$stmt->fetchColumn();
        if (!$userId) {
            return;
        }

        // Past competition — status ENUM: 'planowane','otwarte','zamkniete','zakonczone' (no diacritics)
        $db->prepare(
            "INSERT INTO competitions (club_id, name, competition_date, location, status, description)
             VALUES (?, ?, ?, 'Strzelnica miejska', 'zakonczone', 'Zawody strzeleckie dla zawodników klubu')"
        )->execute([$cid, 'Zawody Wiosenne ' . date('Y'), date('Y-m-d', strtotime('-30 days'))]);
        $compId1 = (int)$db->lastInsertId();

        // competition_entries: status ENUM 'zgloszony','potwierdzony','wycofany','zdyskwalifikowany'
        // Scores go into competition_results (separate table)
        foreach (array_slice($memberIds, 0, 8) as $rank => $memberId) {
            $db->prepare(
                "INSERT INTO competition_entries (competition_id, member_id, status, registered_by)
                 VALUES (?, ?, 'potwierdzony', ?)"
            )->execute([$compId1, $memberId, $userId]);

            $score = 95 - $rank * 3 + rand(-2, 2);
            $db->prepare(
                "INSERT INTO competition_results (competition_id, member_id, score, place, entered_by)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$compId1, $memberId, $score, $rank + 1, $userId]);
        }

        // Upcoming open competition
        $db->prepare(
            "INSERT INTO competitions (club_id, name, competition_date, location, status, description, max_entries)
             VALUES (?, ?, ?, 'Strzelnica klubowa', 'otwarte', 'Coroczne mistrzostwa klubu we wszystkich dyscyplinach', 20)"
        )->execute([$cid, 'Mistrzostwa Klubowe ' . date('Y'), date('Y-m-d', strtotime('+21 days'))]);
        $compId2 = (int)$db->lastInsertId();

        foreach (array_slice($memberIds, 0, 5) as $memberId) {
            $db->prepare(
                "INSERT INTO competition_entries (competition_id, member_id, status, registered_by)
                 VALUES (?, ?, 'zgloszony', ?)"
            )->execute([$compId2, $memberId, $userId]);
        }
    }

    // ── Announcements ─────────────────────────────────────────────────────────

    private static function createAnnouncements(): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $stmt = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ? LIMIT 1");
        $stmt->execute([$cid]);
        $createdBy = (int)$stmt->fetchColumn();
        if (!$createdBy) {
            return;
        }

        // announcements table: title, body, is_published, created_by (no content/is_active columns)
        $items = [
            ['Zebranie zarządu', 'Zebranie zarządu odbędzie się dnia ' . date('d.m.Y', strtotime('+10 days')) . ' o godz. 18:00 w siedzibie klubu. Obecność obowiązkowa.'],
            ['Nowe zasady korzystania ze strzelnicy', 'Informujemy, że od nowego miesiąca obowiązują zaktualizowane zasady korzystania ze strzelnicy. Prosimy o zapoznanie się z regulaminem dostępnym w biurze klubu.'],
        ];

        foreach ($items as [$title, $body]) {
            $db->prepare(
                "INSERT INTO announcements (club_id, title, body, is_published, created_by)
                 VALUES (?, ?, ?, 1, ?)"
            )->execute([$cid, $title, $body, $createdBy]);
        }
    }

    // ── Calendar events ───────────────────────────────────────────────────────

    private static function createCalendarEvents(): void
    {
        $db  = self::$db;
        $cid = self::$clubId;

        $stmt = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ? LIMIT 1");
        $stmt->execute([$cid]);
        $createdBy = (int)$stmt->fetchColumn();

        // calendar_events: no time_start/time_end; type ENUM 'zawody_zewnetrzne','spotkanie','szkolenie','wyjazd','inne'
        $events = [
            ['Trening cotygodniowy',  date('Y-m-d', strtotime('+3 days')),  'szkolenie'],
            ['Dzień otwarty klubu',   date('Y-m-d', strtotime('+14 days')), 'inne'],
        ];

        foreach ($events as [$title, $dt, $type]) {
            $db->prepare(
                "INSERT INTO calendar_events (club_id, title, event_date, type, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$cid, $title, $dt, $type, $createdBy ?: null]);
        }
    }

    // ── Club fees ─────────────────────────────────────────────────────────────
    // club_fees table tracks federation-level fees (PZSS etc.) — not per-member.
    // Per-member annual fee payments are created in createPortalData() via payment_types + payments.
    private static function createClubFees(array $memberIds): void
    {
        // Nothing to insert here for demo purposes.
    }

    // ── Portal demo data (rich profiles for 4 portal members) ────────────────

    private static function createPortalData(array $memberIds): void
    {
        $db  = self::$db;
        $cid = self::$clubId;
        $year = (int)date('Y');

        // Get a user_id for created_by fields
        $stmt = $db->prepare("SELECT user_id FROM user_clubs WHERE club_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$cid]);
        $adminUserId = (int)$stmt->fetchColumn();
        if (!$adminUserId) {
            return;
        }

        // Payment types for this demo club (shared by all portal members)
        $db->prepare(
            "INSERT INTO payment_types (club_id, name, amount, is_active) VALUES (?, 'Składka roczna', 240.00, 1)"
        )->execute([$cid]);
        $ptAnnual = (int)$db->lastInsertId();

        $db->prepare(
            "INSERT INTO payment_types (club_id, name, amount, is_active) VALUES (?, 'Wpisowe na zawody', 35.00, 1)"
        )->execute([$cid]);
        $ptComp = (int)$db->lastInsertId();

        // ── Anna Kowalska [0] — pure portal member, wyczynowy ─────────────────
        $anna = $memberIds[0];
        $db->prepare(
            "UPDATE members SET address_street=?, address_city=?, address_postal=?,
             member_type='wyczynowy', notes=? WHERE id=? LIMIT 1"
        )->execute(['ul. Strzelecka 12/3', 'Warszawa', '00-123',
            'Zawodniczka z 5-letnim stażem. Specjalizacja: pistolet sportowy.', $anna]);

        $db->prepare(
            "INSERT INTO member_medical_exams (member_id, exam_date, valid_until, notes, created_by) VALUES (?,?,?,?,?)"
        )->execute([$anna, date('Y-m-d', strtotime('-5 months')), date('Y-m-d', strtotime('+7 months')),
            'Badanie profilaktyczne — zdolna do uprawiania sportu strzeleckiego bez ograniczeń.', $adminUserId]);
        $db->prepare(
            "INSERT INTO member_medical_exams (member_id, exam_date, valid_until, notes, created_by) VALUES (?,?,?,?,?)"
        )->execute([$anna, date('Y-m-d', strtotime('-17 months')), date('Y-m-d', strtotime('-5 months')),
            'Badanie poprzednie — wygasło.', $adminUserId]);

        foreach ([
            ['Walther PPQ M2',      'pistolet', '9mm', 'Walther',            'WPQ2-2021-DEMO',   'POZ/WA/1234/2021'],
            ['CZ 75 SP-01 Shadow',  'pistolet', '9mm', 'Česká zbrojovka',    'CZ75SP-2022-DEMO', 'POZ/WA/5678/2022'],
        ] as [$n, $t, $cal, $mfr, $s, $p]) {
            $db->prepare(
                "INSERT INTO member_weapons (member_id,name,type,caliber,manufacturer,serial_number,permit_number,is_active,created_by)
                 VALUES (?,?,?,?,?,?,?,1,?)"
            )->execute([$anna, $n, $t, $cal, $mfr, $s, $p, $adminUserId]);
        }

        // Anna payments: previous year + current year + competition fee
        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'przelew',?,?)"
        )->execute([$anna, $ptAnnual, date('Y-m-d', strtotime('-380 days')), $year - 1,
            'PRLW/' . ($year - 1) . '/00042', $adminUserId]);
        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'gotówka',?,?)"
        )->execute([$anna, $ptAnnual, date('Y-m-d', strtotime('-15 days')), $year,
            'KP/' . $year . '/00018', $adminUserId]);
        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,notes,created_by)
             VALUES (?,?,35.00,?,?,'gotówka','Zawody Wiosenne',?)"
        )->execute([$anna, $ptComp, date('Y-m-d', strtotime('-32 days')), $year, $adminUserId]);

        // ── Marek Nowak [1] — linked to zarząd (Jan Prezes), wyczynowy ────────
        $marek = $memberIds[1];
        $db->prepare(
            "UPDATE members SET address_street=?, address_city=?, address_postal=?,
             member_type='wyczynowy', notes=? WHERE id=? LIMIT 1"
        )->execute(['ul. Karabinierów 5/8', 'Kraków', '30-010',
            'Zawodnik z 12-letnim stażem. Specjalizacja: karabin sportowy. Vice-prezes zarządu.', $marek]);

        $db->prepare(
            "INSERT INTO member_medical_exams (member_id, exam_date, valid_until, notes, created_by) VALUES (?,?,?,?,?)"
        )->execute([$marek, date('Y-m-d', strtotime('-8 months')), date('Y-m-d', strtotime('+4 months')),
            'Badanie profilaktyczne — zdolny do uprawiania sportu. Wynik badań słuchu w normie.', $adminUserId]);

        foreach ([
            ['SIG Sauer MCX',  'karabin',  '5.56mm', 'SIG Sauer',  'MCX-2020-DEMO',  'WRO/KR/3344/2020'],
            ['Glock 34',        'pistolet', '9mm',    'Glock GmbH', 'G34-2019-DEMO',  'WRO/KR/1122/2019'],
        ] as [$n, $t, $cal, $mfr, $s, $p]) {
            $db->prepare(
                "INSERT INTO member_weapons (member_id,name,type,caliber,manufacturer,serial_number,permit_number,is_active,created_by)
                 VALUES (?,?,?,?,?,?,?,1,?)"
            )->execute([$marek, $n, $t, $cal, $mfr, $s, $p, $adminUserId]);
        }

        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'przelew',?,?)"
        )->execute([$marek, $ptAnnual, date('Y-m-d', strtotime('-395 days')), $year - 1,
            'PRLW/' . ($year - 1) . '/00031', $adminUserId]);
        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'przelew',?,?)"
        )->execute([$marek, $ptAnnual, date('Y-m-d', strtotime('-10 days')), $year,
            'PRLW/' . $year . '/00009', $adminUserId]);
        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,notes,created_by)
             VALUES (?,?,35.00,?,?,'gotówka','Zawody Wiosenne',?)"
        )->execute([$marek, $ptComp, date('Y-m-d', strtotime('-33 days')), $year, $adminUserId]);

        // ── Katarzyna Wiśniewska [2] — linked to sędzia (Maria Sędzia), aktywny ─
        $kasia = $memberIds[2];
        $db->prepare(
            "UPDATE members SET address_street=?, address_city=?, address_postal=?,
             member_type='aktywny', notes=? WHERE id=? LIMIT 1"
        )->execute(['al. Olimpijska 22/14', 'Wrocław', '50-001',
            'Zawodniczka z 6-letnim stażem. Sędzia strzelecki II klasy. Specjalizacja: pistolet sportowy.', $kasia]);

        $db->prepare(
            "INSERT INTO member_medical_exams (member_id, exam_date, valid_until, notes, created_by) VALUES (?,?,?,?,?)"
        )->execute([$kasia, date('Y-m-d', strtotime('-2 months')), date('Y-m-d', strtotime('+10 months')),
            'Badanie profilaktyczne — zdolna do uprawiania sportu strzeleckiego.', $adminUserId]);

        foreach ([
            ['SIG Sauer P226', 'pistolet', '9mm', 'SIG Sauer', 'SP226-2023-DEMO', 'WRO/WR/8899/2023'],
        ] as [$n, $t, $cal, $mfr, $s, $p]) {
            $db->prepare(
                "INSERT INTO member_weapons (member_id,name,type,caliber,manufacturer,serial_number,permit_number,is_active,created_by)
                 VALUES (?,?,?,?,?,?,?,1,?)"
            )->execute([$kasia, $n, $t, $cal, $mfr, $s, $p, $adminUserId]);
        }

        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'gotówka',?,?)"
        )->execute([$kasia, $ptAnnual, date('Y-m-d', strtotime('-20 days')), $year,
            'KP/' . $year . '/00022', $adminUserId]);

        // ── Piotr Wójcik [3] — linked to instruktor (Piotr Trener), wyczynowy ─
        $piotr = $memberIds[3];
        $db->prepare(
            "UPDATE members SET address_street=?, address_city=?, address_postal=?,
             member_type='wyczynowy', notes=? WHERE id=? LIMIT 1"
        )->execute(['ul. Celna 3/1', 'Poznań', '60-100',
            'Zawodnik z 18-letnim stażem. Instruktor strzelecki klasy I. Korzysta ze sprzętu klubowego.', $piotr]);

        $db->prepare(
            "INSERT INTO member_medical_exams (member_id, exam_date, valid_until, notes, created_by) VALUES (?,?,?,?,?)"
        )->execute([$piotr, date('Y-m-d', strtotime('-1 month')), date('Y-m-d', strtotime('+11 months')),
            'Badanie profilaktyczne — zdolny do uprawiania sportu. Wynik EKG w normie.', $adminUserId]);

        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'przelew',?,?)"
        )->execute([$piotr, $ptAnnual, date('Y-m-d', strtotime('-370 days')), $year - 1,
            'PRLW/' . ($year - 1) . '/00055', $adminUserId]);
        $db->prepare(
            "INSERT INTO payments (member_id,payment_type_id,amount,payment_date,period_year,method,reference,created_by)
             VALUES (?,?,240.00,?,?,'przelew',?,?)"
        )->execute([$piotr, $ptAnnual, date('Y-m-d', strtotime('-5 days')), $year,
            'PRLW/' . $year . '/00041', $adminUserId]);
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
