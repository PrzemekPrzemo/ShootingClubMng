<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;

/**
 * Super-admin security audit panel.
 * Performs static analysis of code patterns, DB structure, file permissions.
 * No external connections — 100% local.
 *
 * GET /admin/security
 */
class AdminSecurityController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $checks = $this->runAll();
        $score  = $this->calcScore($checks);

        $this->render('admin/security_audit', [
            'title'  => 'Audyt bezpieczeństwa',
            'checks' => $checks,
            'score'  => $score,
        ]);
    }

    // ── Run all check groups ──────────────────────────────────────────

    private function runAll(): array
    {
        return [
            'PHP konfiguracja'         => $this->checkPhp(),
            'Sesja i CSRF'             => $this->checkSession(),
            'Pliki i uprawnienia'      => $this->checkFiles(),
            'Baza danych'              => $this->checkDatabase(),
            'Użytkownicy systemu'      => $this->checkUsers(),
            'Nagłówki HTTP'            => $this->checkHeaders(),
            'Analiza kodu — wzorce'    => $this->checkCodePatterns(),
            'Subskrypcje i blokady'    => $this->checkSubscriptions(),
            'Rate limiting'            => $this->checkRateLimiting(),
            '2FA i uwierzytelnianie'   => $this->checkAuth2FA(),
        ];
    }

    // ── PHP configuration ─────────────────────────────────────────────

    private function checkPhp(): array
    {
        $r = [];
        $r[] = $this->check('display_errors wyłączone',    ini_get('display_errors') == '0',   'Ustaw display_errors=Off',       'critical');
        $r[] = $this->check('expose_php wyłączone',        ini_get('expose_php') == '0',        'Ustaw expose_php=Off',           'warning');
        $r[] = $this->check('PHP ≥ 8.1',                  version_compare(PHP_VERSION,'8.1','>='), 'Zaktualizuj PHP do 8.1+',   'warning');
        $r[] = $this->check('allow_url_fopen wyłączone',  ini_get('allow_url_fopen') == '0',   'Ogranicz allow_url_fopen',       'info');
        $r[] = $this->check('session.cookie_httponly',    ini_get('session.cookie_httponly') == '1', 'Ustaw session.cookie_httponly=1', 'critical');
        $r[] = $this->check('session.cookie_secure',      ini_get('session.cookie_secure') == '1',  'Ustaw session.cookie_secure=1 (HTTPS)', 'warning');
        $r[] = $this->check('session.cookie_samesite',    in_array(ini_get('session.cookie_samesite'), ['Strict','Lax']), 'Ustaw session.cookie_samesite=Strict', 'warning');
        return $r;
    }

    // ── Session & CSRF ────────────────────────────────────────────────

    private function checkSession(): array
    {
        $r = [];
        $csrfFile = ROOT_PATH . '/app/Helpers/Csrf.php';
        $r[] = $this->check('CSRF helper istnieje',        file_exists($csrfFile),    'Brak pliku app/Helpers/Csrf.php',   'critical');
        if (file_exists($csrfFile)) {
            $src = file_get_contents($csrfFile);
            $r[] = $this->check('CSRF weryfikacja w kodzie',   strpos($src,'verify') !== false,  'Brak metody verify() w Csrf.php',   'critical');
            $r[] = $this->check('CSRF używa hash_equals',      strpos($src,'hash_equals') !== false,'Użyj hash_equals() do porównania tokenów', 'warning');
        }
        $r[] = $this->check('session_regenerate_id',      $this->codeContains('app/Helpers/Auth.php', 'session_regenerate_id'), 'Wywołaj session_regenerate_id po logowaniu', 'critical');
        return $r;
    }

    // ── Files & permissions ───────────────────────────────────────────

    private function checkFiles(): array
    {
        $r = [];
        $r[] = $this->check('.env poza public/',  !file_exists(ROOT_PATH . '/public/.env'),  'Przenieś .env poza katalog public/', 'critical');
        $r[] = $this->check('Brak config.php w public/', !file_exists(ROOT_PATH . '/public/config.php'), 'Usuń config.php z public/', 'warning');

        $storageWritable = is_writable(ROOT_PATH . '/storage') || !is_dir(ROOT_PATH . '/storage');
        $r[] = $this->check('storage/ zapisywalny',    $storageWritable,  'Ustaw chmod 775 na storage/',           'info');

        $publicIndex = ROOT_PATH . '/public/index.php';
        if (file_exists($publicIndex)) {
            $perms = substr(sprintf('%o', fileperms($publicIndex)), -4);
            $r[] = $this->check('index.php uprawnienia ≤ 644', in_array($perms, ['0644','0640','0600']), "Aktualne uprawnienia: {$perms} — zalecane 644", 'info');
        }

        $r[] = $this->check('Brak phpinfo.php',       !file_exists(ROOT_PATH . '/public/phpinfo.php'), 'Usuń phpinfo.php z public/', 'critical');
        $r[] = $this->check('Brak adminer.php',       !file_exists(ROOT_PATH . '/public/adminer.php'), 'Usuń adminer.php z public/', 'critical');
        return $r;
    }

    // ── Database ──────────────────────────────────────────────────────

    private function checkDatabase(): array
    {
        $r = [];
        try {
            $db   = Database::getInstance();
            $stmt = $db->query("SELECT user FROM mysql.user WHERE user='root' AND host='%' LIMIT 1");
            $r[] = $this->check('Brak root z host=%', $stmt->rowCount() === 0, "Użytkownik root dostępny z każdego hosta!", 'critical');
        } catch (\Throwable) {
            $r[] = $this->check('Dostęp do mysql.user', false, 'Brak dostępu do mysql.user — zweryfikuj ręcznie', 'info');
        }

        try {
            $db   = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE password NOT LIKE '\$2y\$%' AND password != ''");
            $weak = (int)$stmt->fetchColumn();
            $r[] = $this->check('Wszystkie hasła bcrypt', $weak === 0, "{$weak} haseł nie jest bcrypt!", 'critical');
        } catch (\Throwable) {
            $r[] = $this->check('Hasła bcrypt (check)', false, 'Nie udało się sprawdzić formatu haseł', 'warning');
        }

        try {
            $db   = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_super_admin = 1");
            $admins = (int)$stmt->fetchColumn();
            $r[] = $this->check('≤ 3 superadminów', $admins <= 3, "Liczba superadminów: {$admins} — ogranicz do minimum", 'warning');
        } catch (\Throwable) {}

        return $r;
    }

    // ── System users ──────────────────────────────────────────────────

    private function checkUsers(): array
    {
        $r = [];
        try {
            $db   = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE last_login < DATE_SUB(NOW(), INTERVAL 6 MONTH) AND last_login IS NOT NULL");
            $old  = (int)$stmt->fetchColumn();
            $r[] = $this->check('Brak nieaktywnych kont (6 m-cy)', $old === 0, "{$old} kont nieaktywnych > 6 mies. — rozważ deaktywację", 'warning');
        } catch (\Throwable) {}

        try {
            $db   = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE totp_enabled = 1");
            $with2fa = (int)$stmt->fetchColumn();
            $total   = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_super_admin=1")->fetchColumn();
            $r[] = $this->check('Superadmini mają 2FA', $total === 0 || $with2fa >= $total, "Tylko {$with2fa}/{$total} superadminów ma włączone 2FA", 'warning');
        } catch (\Throwable) {
            $r[] = $this->check('2FA sprawdzenie', false, 'Tabela totp — wykonaj migration_v27.sql', 'info');
        }

        return $r;
    }

    // ── HTTP Headers ──────────────────────────────────────────────────

    private function checkHeaders(): array
    {
        $r = [];
        $htaccess = ROOT_PATH . '/public/.htaccess';
        if (file_exists($htaccess)) {
            $src = file_get_contents($htaccess);
            $r[] = $this->check('X-Frame-Options w .htaccess',        strpos($src,'X-Frame-Options') !== false,        'Dodaj: Header always set X-Frame-Options DENY',          'warning');
            $r[] = $this->check('X-Content-Type-Options w .htaccess', strpos($src,'X-Content-Type-Options') !== false,'Dodaj: Header always set X-Content-Type-Options nosniff','warning');
            $r[] = $this->check('Content-Security-Policy w .htaccess',strpos($src,'Content-Security-Policy') !== false,'Dodaj nagłówek CSP',                                     'info');
            $r[] = $this->check('HSTS w .htaccess',                   strpos($src,'Strict-Transport-Security') !== false,'Dodaj HSTS gdy używasz HTTPS',                        'info');
        } else {
            $r[] = $this->check('.htaccess istnieje', false, 'Brak pliku public/.htaccess', 'warning');
        }
        return $r;
    }

    // ── Code pattern analysis ─────────────────────────────────────────

    private function checkCodePatterns(): array
    {
        $r = [];
        $controllersDir = ROOT_PATH . '/app/Controllers';

        // Check for eval() usage
        $evalCount = $this->countCodePattern($controllersDir, '~\beval\s*\(~');
        $r[] = $this->check('Brak eval() w kontrolerach', $evalCount === 0, "Znaleziono {$evalCount} wywołań eval() w kontrolerach", 'critical');

        // Check for direct $_GET/$_POST without sanitization in SQL
        $rawSql = $this->countCodePattern($controllersDir, '~\$_(GET|POST|REQUEST)\s*\[.+\]\s*\)?\s*;?\s*(?!.*prepare|.*filter)~');
        $r[] = $this->check('Brak surowych $_GET/$_POST w SQL', $rawSql < 5, "Potencjalne {$rawSql} miejsc z niefiltrowanym inputem — sprawdź ręcznie", 'warning');

        // Check for shell_exec / system calls
        $shellCount = $this->countCodePattern($controllersDir, '~\b(shell_exec|system|passthru|exec|popen)\s*\(~');
        $r[] = $this->check('Brak shell_exec w kontrolerach', $shellCount === 0, "Znaleziono {$shellCount} wywołań shell w kontrolerach", 'critical');

        // Check models use prepared statements
        $modelsDir = ROOT_PATH . '/app/Models';
        $rawQuery  = $this->countCodePattern($modelsDir, '~->query\s*\(\s*"[^"]*\$_~');
        $r[] = $this->check('Modele używają prepared statements', $rawQuery === 0, "Znaleziono {$rawQuery} potencjalnych raw queries z inputem usera", 'critical');

        // Check for XSS — unescaped echo in views
        $viewsDir  = ROOT_PATH . '/app/Views';
        $rawEcho   = $this->countCodePattern($viewsDir, '~<\?=\s*\$(?!content|csrf|flashSuccess|flashWarning)[a-zA-Z_]+\[~');
        $r[] = $this->check('Widoki używają e() do escapowania', $rawEcho < 10, "Znaleziono ok. {$rawEcho} potencjalnych XSS (<?= \$var bez e()) — sprawdź widoki", 'warning');

        // Check file upload validation
        $uploadCount = $this->countCodePattern($controllersDir, '~\$_FILES~');
        $mimeCheck   = $this->countCodePattern($controllersDir, '~mime_content_type|finfo_file|getimagesize~');
        $r[] = $this->check('Upload z walidacją MIME', $uploadCount === 0 || $mimeCheck > 0, 'Upload plików bez weryfikacji MIME — dodaj walidację', 'warning');

        return $r;
    }

    // ── Subscriptions ─────────────────────────────────────────────────

    private function checkSubscriptions(): array
    {
        $r = [];
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT COUNT(*) FROM club_subscriptions WHERE status='active' AND valid_until < NOW() AND valid_until IS NOT NULL"
            );
            $expired = (int)$stmt->fetchColumn();
            $r[] = $this->check('Brak wygasłych aktywnych subskrypcji', $expired === 0, "{$expired} subskrypcji oznaczonych 'active' ale po terminie — zaktualizuj statusy", 'warning');
        } catch (\Throwable) {
            $r[] = $this->check('Tabela club_subscriptions', false, 'Uruchom migration_v26.sql', 'info');
        }
        $checkFile = ROOT_PATH . '/app/Controllers/BaseController.php';
        $r[] = $this->check('checkSubscription() w BaseController', $this->codeContains($checkFile, 'checkSubscription'), 'Brak metody checkSubscription() — subskrypcje nie są egzekwowane', 'warning');
        return $r;
    }

    // ── Rate limiting ─────────────────────────────────────────────────

    private function checkRateLimiting(): array
    {
        $r = [];
        $rlFile = ROOT_PATH . '/app/Helpers/RateLimiter.php';
        $r[] = $this->check('RateLimiter helper istnieje', file_exists($rlFile), 'Brak app/Helpers/RateLimiter.php', 'warning');

        $authCtrl = ROOT_PATH . '/app/Controllers/AuthController.php';
        $r[] = $this->check('Rate limiting na admin login', $this->codeContains($authCtrl, 'RateLimiter'), 'Brak rate limiting w AuthController', 'warning');

        $memCtrl = ROOT_PATH . '/app/Controllers/MemberAuthController.php';
        $r[] = $this->check('Rate limiting na portal login', $this->codeContains($memCtrl, 'RateLimiter'), 'Brak rate limiting w MemberAuthController', 'warning');

        return $r;
    }

    // ── 2FA ───────────────────────────────────────────────────────────

    private function checkAuth2FA(): array
    {
        $r = [];
        $r[] = $this->check('TwoFactorController istnieje', file_exists(ROOT_PATH . '/app/Controllers/TwoFactorController.php'), 'Brak TwoFactorController — zaimplementuj 2FA', 'warning');
        $r[] = $this->check('Kolumna totp_secret w users',  $this->dbColumnExists('users', 'totp_secret'),  'Uruchom migration_v27.sql (kolumna totp_secret)', 'info');
        return $r;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function check(string $name, bool $pass, string $suggestion, string $level = 'info'): array
    {
        return ['name' => $name, 'pass' => $pass, 'suggestion' => $suggestion, 'level' => $level];
    }

    private function calcScore(array $groups): array
    {
        $total = $critical = $warnings = $passed = 0;
        foreach ($groups as $checks) {
            foreach ($checks as $c) {
                $total++;
                if ($c['pass']) { $passed++; continue; }
                if ($c['level'] === 'critical') $critical++;
                elseif ($c['level'] === 'warning') $warnings++;
            }
        }
        $pct = $total > 0 ? (int)round($passed / $total * 100) : 100;
        return compact('total', 'passed', 'critical', 'warnings', 'pct');
    }

    private function codeContains(string $file, string $needle): bool
    {
        if (!file_exists($file)) return false;
        return strpos(file_get_contents($file), $needle) !== false;
    }

    private function countCodePattern(string $dir, string $pattern): int
    {
        if (!is_dir($dir)) return 0;
        $count = 0;
        $iter  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iter as $file) {
            if ($file->getExtension() !== 'php') continue;
            $src = file_get_contents($file->getPathname());
            preg_match_all($pattern, $src, $m);
            $count += count($m[0]);
        }
        return $count;
    }

    private function dbColumnExists(string $table, string $column): bool
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
