<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Database;
use App\Helpers\Session;

/**
 * Security dashboard: static analysis of system configuration,
 * PHP settings, DB users, uploaded files, and code patterns.
 * No external connections — 100% local checks.
 */
class SecurityController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin']);
    }

    public function index(): void
    {
        $checks = $this->runAllChecks();
        $score  = $this->calcScore($checks);

        $this->render('security/index', [
            'title'  => 'Bezpieczeństwo systemu',
            'checks' => $checks,
            'score'  => $score,
        ]);
    }

    // ── All check groups ──────────────────────────────────────────────

    private function runAllChecks(): array
    {
        return [
            'PHP konfiguracja'        => $this->checkPhp(),
            'Sesja i CSRF'            => $this->checkSession(),
            'Pliki i katalogi'        => $this->checkFiles(),
            'Baza danych'             => $this->checkDatabase(),
            'Użytkownicy systemu'     => $this->checkUsers(),
            'Upload plików'           => $this->checkUploads(),
            'Nagłówki HTTP (zalecane)'=> $this->checkHeaders(),
        ];
    }

    // ── PHP configuration ─────────────────────────────────────────────

    private function checkPhp(): array
    {
        $results = [];

        $results[] = $this->check(
            'display_errors wyłączone',
            ini_get('display_errors') == '0',
            'Ustaw display_errors = Off w php.ini (produkcja)',
            'critical'
        );

        $results[] = $this->check(
            'log_errors włączone',
            ini_get('log_errors') == '1',
            'Ustaw log_errors = On w php.ini',
            'warning'
        );

        $results[] = $this->check(
            'expose_php wyłączone',
            ini_get('expose_php') == '0',
            'Ustaw expose_php = Off — ukrywa wersję PHP w nagłówkach HTTP',
            'warning'
        );

        $results[] = $this->check(
            'Wersja PHP ≥ 8.2',
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'Używasz PHP ' . PHP_VERSION . ' — zaktualizuj do PHP 8.2+',
            'critical'
        );

        $results[] = $this->check(
            'allow_url_fopen wyłączone',
            ini_get('allow_url_fopen') == '0',
            'Ustaw allow_url_fopen = Off — ogranicza ataki SSRF',
            'info'
        );

        $maxUpload = (int)ini_get('upload_max_filesize');
        $results[] = $this->check(
            'upload_max_filesize ≤ 20 MB',
            $maxUpload <= 20,
            "upload_max_filesize = {$maxUpload}M — rozważ obniżenie do ≤ 20M",
            'info'
        );

        $results[] = $this->check(
            'session.use_strict_mode włączone',
            ini_get('session.use_strict_mode') == '1',
            'Ustaw session.use_strict_mode = 1 — zapobiega session fixation',
            'warning'
        );

        $results[] = $this->check(
            'session.cookie_httponly włączone',
            ini_get('session.cookie_httponly') == '1',
            'Ustaw session.cookie_httponly = 1 — blokuje dostęp JS do ciasteczka sesji',
            'critical'
        );

        $results[] = $this->check(
            'session.cookie_samesite ustawione',
            in_array(strtolower((string)ini_get('session.cookie_samesite')), ['lax', 'strict']),
            'Ustaw session.cookie_samesite = Lax (lub Strict) w php.ini',
            'warning'
        );

        return $results;
    }

    // ── Session & CSRF ────────────────────────────────────────────────

    private function checkSession(): array
    {
        $results = [];

        $results[] = $this->check(
            'Plik CSRF helper istnieje',
            class_exists('\App\Helpers\Csrf'),
            'Brak klasy App\\Helpers\\Csrf — brak ochrony CSRF',
            'critical'
        );

        $sessionSave = ini_get('session.save_path');
        $results[] = $this->check(
            'session.save_path zdefiniowane',
            !empty($sessionSave),
            'Ustaw session.save_path w php.ini lub skonfiguruj inny handler',
            'info'
        );

        $results[] = $this->check(
            'session.gc_maxlifetime ≤ 7200 s',
            (int)ini_get('session.gc_maxlifetime') <= 7200,
            'Skróć session.gc_maxlifetime do ≤ 7200 sekund (2 godziny)',
            'warning'
        );

        return $results;
    }

    // ── Files and directories ─────────────────────────────────────────

    private function checkFiles(): array
    {
        $results = [];
        $root    = ROOT_PATH;

        // .env / config files in public/
        $sensitivePub = [
            $root . '/public/.env',
            $root . '/public/config.php',
            $root . '/public/database.php',
        ];
        $exposed = array_filter($sensitivePub, 'file_exists');
        $results[] = $this->check(
            'Brak wrażliwych plików w public/',
            empty($exposed),
            'Znaleziono wrażliwe pliki w public/: ' . implode(', ', array_map('basename', $exposed)),
            'critical'
        );

        // config/database.local.php outside public
        $localDb = $root . '/config/database.local.php';
        $results[] = $this->check(
            'database.local.php poza katalogiem public/',
            !file_exists($root . '/public/database.local.php'),
            'Plik konfiguracyjny bazy danych jest dostępny publicznie!',
            'critical'
        );

        // .git in public
        $results[] = $this->check(
            'Katalog .git poza public/',
            !is_dir($root . '/public/.git'),
            'Katalog .git jest dostępny z sieci — usuń go z public/',
            'critical'
        );

        // storage/uploads writable
        $uploadDir = $root . '/storage/uploads';
        if (is_dir($uploadDir)) {
            $perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
            $results[] = $this->check(
                'storage/uploads nie ma praw 0777',
                $perms !== '0777',
                "Katalog uploads ma uprawnienia 0777 ($perms) — zmień na 0755",
                'warning'
            );
        }

        // Check for php files in uploads
        $phpInUploads = false;
        if (is_dir($uploadDir)) {
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadDir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if ($file->getExtension() === 'php') {
                    $phpInUploads = true;
                    break;
                }
            }
        }
        $results[] = $this->check(
            'Brak plików PHP w katalogu uploads',
            !$phpInUploads,
            'Znaleziono pliki .php w storage/uploads — usuń je natychmiast!',
            'critical'
        );

        return $results;
    }

    // ── Database ──────────────────────────────────────────────────────

    private function checkDatabase(): array
    {
        $results = [];
        $pdo     = Database::pdo();

        // Check for default/weak DB passwords in config
        $localConfig = ROOT_PATH . '/config/database.local.php';
        $cfgFile     = file_exists($localConfig) ? $localConfig : ROOT_PATH . '/config/database.php';
        $cfg         = require $cfgFile;

        $weakPasswords = ['', 'root', 'password', '123456', 'mysql', 'admin'];
        $isWeak = in_array(strtolower($cfg['password'] ?? ''), $weakPasswords);
        $results[] = $this->check(
            'Hasło bazy danych nie jest typowe/puste',
            !$isWeak,
            'Hasło do bazy danych jest słabe lub puste — zmień je natychmiast',
            'critical'
        );

        // DB user not root
        try {
            $user = $pdo->query("SELECT USER() AS u")->fetchColumn();
            $isRoot = str_starts_with((string)$user, 'root@');
            $results[] = $this->check(
                'Aplikacja nie łączy się jako root',
                !$isRoot,
                "Aplikacja używa użytkownika root ($user) — utwórz dedykowanego użytkownika DB z minimalnymi uprawnieniami",
                'critical'
            );
        } catch (\Throwable) {}

        // Check SQL mode includes STRICT
        try {
            $mode = $pdo->query("SELECT @@sql_mode")->fetchColumn();
            $hasStrict = str_contains((string)$mode, 'STRICT_TRANS_TABLES') || str_contains((string)$mode, 'STRICT_ALL_TABLES');
            $results[] = $this->check(
                'SQL strict mode włączony',
                $hasStrict,
                'Włącz STRICT_TRANS_TABLES w sql_mode MySQL — zapobiega silent data truncation',
                'warning'
            );
        } catch (\Throwable) {}

        // Check for orphan admin accounts (users with no last login in 6 months)
        try {
            $stale = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 6 MONTH))")->fetchColumn();
            $results[] = $this->check(
                'Brak nieaktywnych kont admin (>6 mies.)',
                (int)$stale === 0,
                "Znaleziono $stale konto/a admin bez logowania przez 6+ miesięcy — rozważ dezaktywację",
                'warning'
            );
        } catch (\Throwable) {}

        // Check admin user count
        try {
            $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
            $results[] = $this->check(
                'Liczba aktywnych adminów ≤ 3',
                $adminCount <= 3,
                "Aktywnych administratorów: $adminCount — ogranicz liczbę kont admin do minimum",
                'info'
            );
        } catch (\Throwable) {}

        return $results;
    }

    // ── Users ─────────────────────────────────────────────────────────

    private function checkUsers(): array
    {
        $results = [];
        $pdo     = Database::pdo();

        // Check password hash algorithm
        try {
            $hashes = $pdo->query("SELECT password FROM users WHERE is_active = 1 LIMIT 20")->fetchAll(\PDO::FETCH_COLUMN);
            $weakHash = false;
            foreach ($hashes as $hash) {
                // MD5 (32 hex chars) or SHA1 (40 hex chars) — legacy insecure hashes
                if (preg_match('/^[a-f0-9]{32}$/', $hash) || preg_match('/^[a-f0-9]{40}$/', $hash)) {
                    $weakHash = true;
                    break;
                }
            }
            $results[] = $this->check(
                'Hasła nie są w formacie MD5/SHA1',
                !$weakHash,
                'Wykryto hasła w formacie MD5 lub SHA1 — wymuś zmianę haseł i użyj password_hash()',
                'critical'
            );
        } catch (\Throwable) {}

        // Check for accounts with no password (empty hash)
        try {
            $noPass = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE password IS NULL OR password = ''")->fetchColumn();
            $results[] = $this->check(
                'Brak kont bez hasła',
                $noPass === 0,
                "Znaleziono $noPass konto/a bez ustawionego hasła",
                'critical'
            );
        } catch (\Throwable) {}

        // Check for member portal accounts without passwords
        try {
            $noMemberPass = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE portal_password IS NULL OR portal_password = ''")->fetchColumn();
            $results[] = $this->check(
                'Portal: brak kont bez hasła',
                $noMemberPass === 0,
                "Znaleziono $noMemberPass kont zawodników bez ustawionego hasła portalowego",
                'info'
            );
        } catch (\Throwable) {}

        return $results;
    }

    // ── Uploaded files ────────────────────────────────────────────────

    private function checkUploads(): array
    {
        $results = [];

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $dangerExts  = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'py', 'rb', 'pl'];

        $uploadDir = ROOT_PATH . '/storage/uploads';
        $found = [];

        if (is_dir($uploadDir)) {
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadDir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if (in_array(strtolower($file->getExtension()), $dangerExts)) {
                    $found[] = $file->getFilename();
                }
            }
        }

        $results[] = $this->check(
            'Brak plików wykonywalnych w uploads',
            empty($found),
            'Znaleziono potencjalnie niebezpieczne pliki: ' . implode(', ', array_slice($found, 0, 5)),
            'critical'
        );

        // Check .htaccess or web.config in uploads to deny execution
        $htaccess = $uploadDir . '/.htaccess';
        $webConfig = $uploadDir . '/web.config';
        $hasProtection = file_exists($htaccess) || file_exists($webConfig);
        $results[] = $this->check(
            '.htaccess w uploads blokuje wykonanie PHP',
            $hasProtection,
            'Dodaj plik ' . $uploadDir . '/.htaccess z: <br><code>php_flag engine off<br>Options -ExecCGI</code>',
            'warning'
        );

        return $results;
    }

    // ── HTTP Headers ──────────────────────────────────────────────────

    private function checkHeaders(): array
    {
        $results = [];

        // Check headers_sent() — we can't send headers now (output started), but we check config
        $results[] = $this->check(
            'HTTPS wymuszane (server config)',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'Strona nie działa przez HTTPS — wdróż certyfikat SSL/TLS (np. Let\'s Encrypt)',
            'critical'
        );

        // We can check header values already sent by checking output if any
        // Realistically check PHP sessions cookie secure flag
        $secureCookie = ini_get('session.cookie_secure');
        $results[] = $this->check(
            'session.cookie_secure włączone',
            $secureCookie == '1',
            'Ustaw session.cookie_secure = 1 w php.ini — wymaga HTTPS',
            'warning'
        );

        return $results;
    }

    // ── Scoring ───────────────────────────────────────────────────────

    private function calcScore(array $groups): array
    {
        $total    = 0;
        $passed   = 0;
        $critical = 0;
        $warnings = 0;

        foreach ($groups as $checks) {
            foreach ($checks as $c) {
                $total++;
                if ($c['pass']) {
                    $passed++;
                } elseif ($c['severity'] === 'critical') {
                    $critical++;
                } elseif ($c['severity'] === 'warning') {
                    $warnings++;
                }
            }
        }

        $pct = $total > 0 ? round($passed / $total * 100) : 0;

        return [
            'total'    => $total,
            'passed'   => $passed,
            'failed'   => $total - $passed,
            'critical' => $critical,
            'warnings' => $warnings,
            'pct'      => $pct,
            'grade'    => $pct >= 90 ? 'A' : ($pct >= 75 ? 'B' : ($pct >= 60 ? 'C' : ($pct >= 40 ? 'D' : 'F'))),
            'color'    => $critical > 0 ? 'danger' : ($warnings > 0 ? 'warning' : 'success'),
        ];
    }

    private function check(string $label, bool $pass, string $recommendation, string $severity = 'warning'): array
    {
        return compact('label', 'pass', 'recommendation', 'severity');
    }
}
