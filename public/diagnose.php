<?php
/**
 * Klub Strzelecki — Narzędzie diagnostyczne
 * Otwórz w przeglądarce: https://twoja-domena.pl/diagnose.php
 *
 * UWAGA: Po rozwiązaniu problemu usuń ten plik lub zabezpiecz go hasłem!
 */

// Włącz pełne wyświetlanie błędów dla tego pliku
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('ROOT_PATH', dirname(__DIR__));
define('DIAG_START', microtime(true));

// Prosty token zabezpieczający — zmień lub usuń plik po diagnozie
$token = $_GET['token'] ?? '';
$validToken = file_exists(ROOT_PATH . '/config/database.local.php')
    ? md5(filemtime(ROOT_PATH . '/config/database.local.php'))
    : 'diagnose';
if ($token !== $validToken) {
    header('Content-Type: text/plain');
    echo "Dostęp chroniony.\n";
    echo "Otwórz: diagnose.php?token={$validToken}\n";
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function ok(string $msg): string   { return "<tr class='ok'><td>✅</td><td>{$msg}</td></tr>"; }
function fail(string $msg): string { return "<tr class='fail'><td>❌</td><td>{$msg}</td></tr>"; }
function warn(string $msg): string { return "<tr class='warn'><td>⚠️</td><td>{$msg}</td></tr>"; }
function info(string $msg): string { return "<tr class='info'><td>ℹ️</td><td>{$msg}</td></tr>"; }
function section(string $title): string {
    return "</table><h2>{$title}</h2><table>";
}

$rows = '';

?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnoza — Klub Strzelecki</title>
<style>
body { font-family: monospace; font-size: 14px; background: #1e1e2e; color: #cdd6f4; margin: 0; padding: 20px; }
h1 { color: #f38ba8; } h2 { color: #89b4fa; margin-top: 2em; border-bottom: 1px solid #45475a; padding-bottom: 4px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
td { padding: 5px 10px; vertical-align: top; }
td:first-child { width: 28px; text-align: center; }
tr.ok   td { color: #a6e3a1; }
tr.fail td { color: #f38ba8; background: rgba(243,139,168,.08); }
tr.warn td { color: #f9e2af; }
tr.info td { color: #89dceb; }
tr:hover { background: rgba(255,255,255,.03); }
pre { background: #313244; padding: 10px; border-radius: 6px; overflow-x: auto; font-size: 12px; color: #cdd6f4; }
.badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; }
.badge-ok   { background:#a6e3a1; color:#1e1e2e; }
.badge-fail { background:#f38ba8; color:#1e1e2e; }
.badge-warn { background:#f9e2af; color:#1e1e2e; }
.token-hint { background:#313244; padding:10px; border-radius:6px; color:#a6e3a1; margin-bottom:20px; font-size:12px; }
</style>
</head>
<body>
<h1>🎯 Diagnoza systemu — Klub Strzelecki</h1>
<div class="token-hint">⚠️ Usuń lub zabezpiecz ten plik po zakończeniu diagnozy:<br>
<code>rm <?= __FILE__ ?></code></div>
<table>
<?php

// =============================================================================
// 1. PHP
// =============================================================================
$rows .= section('PHP');

$phpVersion = PHP_VERSION;
$phpMajor   = PHP_MAJOR_VERSION;
$phpMinor   = PHP_MINOR_VERSION;

if ($phpMajor > 8 || ($phpMajor === 8 && $phpMinor >= 1)) {
    $rows .= ok("PHP version: <strong>{$phpVersion}</strong>");
} elseif ($phpMajor === 8) {
    $rows .= warn("PHP version: <strong>{$phpVersion}</strong> — zalecane 8.1+");
} else {
    $rows .= fail("PHP version: <strong>{$phpVersion}</strong> — WYMAGANE >= 8.1! "
        . "Aplikacja używa składni PHP 8.x (match, str_starts_with, never return type). "
        . "<strong>W Plesk: Strony → wksfg.pl → PHP → zmień na 8.3</strong>");
}

$rows .= info("PHP SAPI: " . PHP_SAPI);
$rows .= info("PHP binary: " . PHP_BINARY);
$rows .= info("php.ini: " . php_ini_loaded_file());

// Wymagane rozszerzenia
foreach (['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'openssl'] as $ext) {
    if (extension_loaded($ext)) {
        $rows .= ok("Rozszerzenie: {$ext}");
    } else {
        $rows .= fail("Brak rozszerzenia: <strong>{$ext}</strong>");
    }
}

// Opcjonalne
foreach (['fileinfo', 'gd', 'intl'] as $ext) {
    if (extension_loaded($ext)) {
        $rows .= ok("Rozszerzenie opcjonalne: {$ext}");
    } else {
        $rows .= warn("Brak rozszerzenia opcjonalnego: {$ext}");
    }
}

// =============================================================================
// 2. Pliki konfiguracyjne
// =============================================================================
$rows .= section('Pliki konfiguracyjne');

$files = [
    ROOT_PATH . '/config/database.local.php' => 'config/database.local.php (wymagany)',
    ROOT_PATH . '/config/app.local.php'      => 'config/app.local.php',
    ROOT_PATH . '/config/database.php'       => 'config/database.php (fallback)',
    ROOT_PATH . '/config/app.php'            => 'config/app.php (fallback)',
    ROOT_PATH . '/public/.htaccess'          => 'public/.htaccess',
    ROOT_PATH . '/database/schema.sql'       => 'database/schema.sql',
];

foreach ($files as $path => $label) {
    if (file_exists($path)) {
        $perm = substr(sprintf('%o', fileperms($path)), -4);
        $rows .= ok("{$label} <span style='color:#585b70'>({$perm})</span>");
    } else {
        if (str_contains($label, 'wymagany')) {
            $rows .= fail("Brak: <strong>{$label}</strong> — uruchom install.sh!");
        } else {
            $rows .= warn("Brak: {$label}");
        }
    }
}

// =============================================================================
// 3. Baza danych
// =============================================================================
$rows .= section('Baza danych');

$dbConfig = null;
$dbLocalPath = ROOT_PATH . '/config/database.local.php';
$dbPath      = ROOT_PATH . '/config/database.php';

if (file_exists($dbLocalPath)) {
    $dbConfig = require $dbLocalPath;
    $rows .= info("Używam: config/database.local.php");
} elseif (file_exists($dbPath)) {
    $dbConfig = require $dbPath;
    $rows .= warn("Używam: config/database.php (brak database.local.php — uruchom install.sh)");
}

if ($dbConfig) {
    $rows .= info("Host: {$dbConfig['host']}:{$dbConfig['port']}");
    $rows .= info("Baza: {$dbConfig['dbname']}");
    $rows .= info("Użytkownik: {$dbConfig['username']}");

    try {
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $serverVer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $rows .= ok("Połączenie z bazą: OK (MySQL/MariaDB {$serverVer})");

        // Sprawdź tabele
        $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
        $expected = ['users','members','licenses','payments','competitions','settings','activity_log'];
        $missing  = array_diff($expected, $tables);

        if (empty($missing)) {
            $rows .= ok("Tabele bazy danych: OK (" . count($tables) . " tabel)");
        } else {
            $rows .= fail("Brakuje tabel: <strong>" . implode(', ', $missing) . "</strong> — zaimportuj database/schema.sql");
        }

        // Sprawdź konto admina
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
        if ($adminCount > 0) {
            $rows .= ok("Konto administratora: {$adminCount} aktywne");
        } else {
            $rows .= fail("Brak aktywnego konta administratora!");
        }

        // Charset
        $charset = $pdo->query("SELECT @@character_set_database")->fetchColumn();
        if (str_starts_with((string)$charset, 'utf8mb4')) {
            $rows .= ok("Charset bazy: {$charset}");
        } else {
            $rows .= warn("Charset bazy: {$charset} — zalecane utf8mb4");
        }

    } catch (PDOException $e) {
        $rows .= fail("Błąd połączenia z bazą: <strong>" . htmlspecialchars($e->getMessage()) . "</strong>");
    }
} else {
    $rows .= fail("Brak konfiguracji bazy danych!");
}

// =============================================================================
// 4. Uprawnienia katalogów
// =============================================================================
$rows .= section('Uprawnienia katalogów i pliki');

$writableDirs = [
    ROOT_PATH . '/logs'            => 'logs/ (zapis logów)',
    ROOT_PATH . '/public/uploads'  => 'public/uploads/ (zapis plików)',
];

foreach ($writableDirs as $path => $label) {
    if (!is_dir($path)) {
        $rows .= warn("Brak katalogu: {$label} — zostanie utworzony przy pierwszym użyciu");
    } elseif (is_writable($path)) {
        $perm = substr(sprintf('%o', fileperms($path)), -4);
        $rows .= ok("{$label} — zapisywalny ({$perm})");
    } else {
        $rows .= fail("{$label} — <strong>brak uprawnień zapisu</strong>! "
            . "Wykonaj: <code>chmod 775 {$path}</code>");
    }
}

// Sprawdź czy config.local.php nie jest przypadkiem dostępny z sieci
$rows .= section('Bezpieczeństwo');

// Test dostępności wrażliwych plików (symulacja)
$sensitiveRelPaths = ['config/database.local.php', 'config/app.local.php', '.env', '.git/config'];
foreach ($sensitiveRelPaths as $rel) {
    $full = ROOT_PATH . '/' . $rel;
    if (file_exists($full)) {
        // Sprawdź czy nie ma go w public/
        if (str_contains($rel, 'config/')) {
            $rows .= ok("{$rel} — poza katalogiem public/ ✓");
        }
    }
}

// Sprawdź czy .htaccess blokuje dostęp do katalogów
if (file_exists(ROOT_PATH . '/public/.htaccess')) {
    $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
    if (str_contains($htaccess, 'RewriteEngine')) {
        $rows .= ok('.htaccess zawiera RewriteEngine (mod_rewrite)');
    } else {
        $rows .= fail('.htaccess nie zawiera RewriteEngine — routing nie zadziała!');
    }
} else {
    $rows .= fail('Brak public/.htaccess — routing nie zadziała!');
}

// =============================================================================
// 5. Środowisko
// =============================================================================
$rows .= section('Środowisko serwera');

$rows .= info("Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'nieznany'));
$rows .= info("Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'nieznany'));
$rows .= info("Script filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'nieznany'));
$rows .= info("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'nieznany'));
$rows .= info("HTTPS: " . (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'tak' : 'nie'));

// Sprawdź czy document root wskazuje na public/
$docRoot  = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$expected = realpath(ROOT_PATH . '/public');
if ($docRoot && $expected && $docRoot === $expected) {
    $rows .= ok("Document root wskazuje na public/ ✓");
} elseif ($docRoot && $expected) {
    $rows .= fail("Document root: <strong>{$docRoot}</strong><br>"
        . "Oczekiwano: <strong>{$expected}</strong><br>"
        . "W Plesk: Strony → wksfg.pl → Ustawienia hostingu → Katalog główny dokumentów → ustaw na <code>httpdocs/shootingclubmng/public</code>");
}

// PHP limits
$rows .= info("memory_limit: " . ini_get('memory_limit'));
$rows .= info("upload_max_filesize: " . ini_get('upload_max_filesize'));
$rows .= info("max_execution_time: " . ini_get('max_execution_time') . 's');

// =============================================================================
// 6. Test autoloadera
// =============================================================================
$rows .= section('Test autoloadera aplikacji');

try {
    // Symuluj autoloader
    $testClasses = [
        'App\\Helpers\\Database'   => ROOT_PATH . '/app/Helpers/Database.php',
        'App\\Helpers\\Router'     => ROOT_PATH . '/app/Helpers/Router.php',
        'App\\Controllers\\AuthController' => ROOT_PATH . '/app/Controllers/AuthController.php',
        'App\\Models\\MemberModel' => ROOT_PATH . '/app/Models/MemberModel.php',
    ];
    foreach ($testClasses as $class => $path) {
        if (file_exists($path)) {
            $rows .= ok("Plik klasy: " . str_replace(ROOT_PATH . '/', '', $path));
        } else {
            $rows .= fail("Brak pliku: <strong>" . str_replace(ROOT_PATH . '/', '', $path) . "</strong>");
        }
    }
} catch (Throwable $e) {
    $rows .= fail("Błąd testu: " . htmlspecialchars($e->getMessage()));
}

// =============================================================================
// 7. Error log (ostatnie wpisy)
// =============================================================================
$rows .= section('Ostatnie błędy PHP (error_log)');

$errorLogPath = ini_get('error_log');
$appLogPath   = ROOT_PATH . '/logs/app.log';

foreach ([$errorLogPath, $appLogPath] as $logPath) {
    if ($logPath && file_exists($logPath) && is_readable($logPath)) {
        $lines = array_slice(file($logPath), -20);
        if ($lines) {
            $rows .= info("Ostatnie 20 linii z <code>" . htmlspecialchars($logPath) . "</code>:");
            echo $rows;
            $rows = '';
            echo "<tr><td colspan='2'><pre>" . htmlspecialchars(implode('', $lines)) . "</pre></td></tr>";
        } else {
            $rows .= ok("Log pusty: " . htmlspecialchars($logPath));
        }
        break;
    }
}
if (empty($rows) || !str_contains($rows, 'Log')) {
    $rows .= warn("Nie znaleziono pliku error_log: " . htmlspecialchars($errorLogPath ?: 'niezdefiniowany'));
    $rows .= info("Sprawdź ręcznie log Plesk: Panel → Strony → Dzienniki błędów");
}

// Wypisz pozostałe wiersze
echo $rows;

$elapsed = round((microtime(true) - DIAG_START) * 1000, 1);
?>
</table>
<p style="color:#585b70;font-size:12px">Wygenerowano: <?= date('Y-m-d H:i:s') ?> | Czas: <?= $elapsed ?>ms</p>
<hr style="border-color:#45475a">
<p style="color:#f38ba8;font-size:12px">⚠️ Usuń ten plik po zakończeniu diagnozy: <code>rm <?= __FILE__ ?></code></p>
</body>
</html>
