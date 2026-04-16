<?php
/**
 * Migration runner — CLI only.
 *
 * Usage:
 *   php cli/migrate.php             # run all migrations
 *   php cli/migrate.php --dry-run   # show which files would run
 *
 * Tolerates "already applied" errors (duplicate column / key / existing table)
 * so it's safe to re-run. Does not require a migrations table — each file's
 * CREATE / ALTER is safe-guarded by MySQL's own errors which we detect.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

define('ROOT_PATH', dirname(__DIR__));

// Simple PSR-4 autoloader for App\* namespace
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $path = ROOT_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
        if (file_exists($path)) require_once $path;
    }
});

$dryRun = in_array('--dry-run', $argv, true);

$dir   = ROOT_PATH . '/database';
$files = glob($dir . '/migration_*.sql') ?: [];

// Sort: vN numerically first, then named
usort($files, function ($a, $b) {
    $aV = preg_match('/migration_v(\d+)\.sql$/', $a, $m) ? (int)$m[1] : null;
    $bV = preg_match('/migration_v(\d+)\.sql$/', $b, $m) ? (int)$m[1] : null;
    if ($aV !== null && $bV !== null) return $aV <=> $bV;
    if ($aV !== null) return -1;  // numbered come first
    if ($bV !== null) return  1;
    return strcmp($a, $b);
});

if (empty($files)) {
    echo "Brak plików migracji.\n";
    exit(0);
}

echo "Znaleziono " . count($files) . " plików migracji.\n\n";

if ($dryRun) {
    foreach ($files as $f) echo "  • " . basename($f) . "\n";
    echo "\n(dry-run — nic nie wykonano)\n";
    exit(0);
}

$db = App\Helpers\Database::pdo();
$applied = 0;
$skipped = 0;
$errors  = 0;

// MySQL error codes that mean "already applied" — safe to ignore
$idempotentCodes = [
    '42S21', // Duplicate column name
    '42S01', // Table already exists
    '42000', // Syntax/general (used for some duplicate-key cases too)
    '23000', // Integrity constraint (e.g. duplicate entry on UNIQUE seed)
];
// More specific error messages to recognize
$idempotentMsgs = [
    'already exists', 'duplicate column', 'duplicate key name',
    'duplicate entry', 'duplicate foreign key', 'already defined',
    'multiple primary key',
];

foreach ($files as $path) {
    $name = basename($path);
    echo str_pad($name, 40) . " ... ";
    $sql = file_get_contents($path);
    if ($sql === false) { echo "BŁĄD ODCZYTU\n"; $errors++; continue; }

    // Split into statements (simple splitter — handles ; at end of line)
    $stmts = array_filter(
        array_map('trim', preg_split('/;\s*[\r\n]/', $sql)),
        fn($s) => $s !== '' && !preg_match('/^\s*--/', $s)
    );

    $fileApplied = 0;
    $fileSkipped = 0;
    $fileFailed  = false;

    foreach ($stmts as $stmt) {
        if (preg_match('/^\s*$/', $stmt)) continue;
        try {
            $db->exec($stmt);
            $fileApplied++;
        } catch (\PDOException $e) {
            $msg = strtolower($e->getMessage());
            $isIdempotent = in_array($e->getCode(), $idempotentCodes, true)
                || array_reduce($idempotentMsgs, fn($c, $m) => $c || str_contains($msg, $m), false);
            if ($isIdempotent) {
                $fileSkipped++;
            } else {
                echo "\n  BŁĄD w statement:\n    " . substr($stmt, 0, 120) . "...\n  " . $e->getMessage() . "\n";
                $fileFailed = true;
                break;
            }
        }
    }

    if ($fileFailed) { echo "FAIL ❌\n"; $errors++; break; }
    if ($fileApplied > 0) { echo "zastosowano {$fileApplied} zmian"; $applied++; }
    else                  { echo "nic nowego"; $skipped++; }
    if ($fileSkipped > 0) echo " (" . $fileSkipped . " już wcześniej)";
    echo "\n";
}

echo "\n──────────────────────────────\n";
echo "Zastosowane pliki: {$applied}\n";
echo "Bez zmian:         {$skipped}\n";
echo "Błędy:             {$errors}\n";
exit($errors > 0 ? 1 : 0);
