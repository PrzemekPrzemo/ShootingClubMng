#!/usr/bin/env php
<?php
/**
 * Database + storage backup script.
 * Creates a ZIP archive in storage/backups/ and removes archives older than 30 days.
 *
 * Usage (cron — daily at 02:00):
 *   0 2 * * * php /var/www/shootero/cli/backup.php >> /var/log/shootero_backup.log 2>&1
 *
 * Manual run:
 *   php cli/backup.php [--club-id=1]   # per-club SQL dump (future)
 *   php cli/backup.php --notify         # send e-mail summary after backup
 */

define('ROOT_PATH', dirname(__DIR__));
define('STDIN_CLI', true);

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/autoload.php';
require ROOT_PATH . '/app/Helpers/helpers.php';

use App\Helpers\Database;
use App\Helpers\Mailer;

// ── CLI arguments ─────────────────────────────────────────────────────────────
$notify  = in_array('--notify', $argv ?? [], true);
$retDays = 30; // retention period

// ── Paths ─────────────────────────────────────────────────────────────────────
$backupDir = ROOT_PATH . '/storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true)) {
    fwrite(STDERR, "Cannot create backup directory: {$backupDir}\n");
    exit(1);
}

$ts        = date('Y-m-d_H-i-s');
$zipPath   = "{$backupDir}/backup_{$ts}.zip";

// ── DB config ─────────────────────────────────────────────────────────────────
$localCfg = ROOT_PATH . '/config/database.local.php';
$dbCfg    = file_exists($localCfg)
    ? require $localCfg
    : require ROOT_PATH . '/config/database.php';

// ── 1. SQL dump ───────────────────────────────────────────────────────────────
$sqlFile = "{$backupDir}/dump_{$ts}.sql";
$host    = escapeshellarg($dbCfg['host']);
$port    = (int)($dbCfg['port'] ?? 3306);
$user    = escapeshellarg($dbCfg['username']);
$pass    = $dbCfg['password'] ?? '';
$dbname  = escapeshellarg($dbCfg['dbname']);

// Build options string (password passed via env to avoid shell history exposure)
$envPass  = !empty($pass) ? "MYSQL_PWD=" . escapeshellarg($pass) . " " : '';
$dumpCmd  = "{$envPass}mysqldump --single-transaction --quick "
          . "-h {$host} -P {$port} -u {$user} {$dbname} "
          . "> " . escapeshellarg($sqlFile);

exec($dumpCmd, $dumpOut, $dumpCode);
if ($dumpCode !== 0 || !file_exists($sqlFile)) {
    fwrite(STDERR, "[{$ts}] mysqldump failed (exit {$dumpCode})\n");
    exit(1);
}

$sqlSize = filesize($sqlFile);

// ── 2. ZIP it up ─────────────────────────────────────────────────────────────
if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "[{$ts}] ZipArchive not available — install php-zip\n");
    @unlink($sqlFile);
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "[{$ts}] Cannot create ZIP: {$zipPath}\n");
    @unlink($sqlFile);
    exit(1);
}

// Add SQL dump
$zip->addFile($sqlFile, "dump_{$ts}.sql");

// Add storage/ (excluding backups/ itself to avoid recursion)
$storageDir = ROOT_PATH . '/storage';
$skip       = realpath($backupDir);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $real = $file->getRealPath();
    if ($real === false || str_starts_with($real, $skip . DIRECTORY_SEPARATOR)) continue;
    $rel  = substr($real, strlen(realpath($storageDir)) + 1);
    $zip->addFile($real, "storage/{$rel}");
}

$zip->close();
@unlink($sqlFile); // remove plain SQL after zipping

$zipSize = file_exists($zipPath) ? filesize($zipPath) : 0;

// ── 3. Retention — remove archives older than $retDays days ──────────────────
$removed = 0;
foreach (glob("{$backupDir}/backup_*.zip") as $f) {
    if (filemtime($f) < time() - ($retDays * 86400)) {
        @unlink($f);
        $removed++;
    }
}

// ── 4. Log ────────────────────────────────────────────────────────────────────
$sqKb   = round($sqlSize / 1024);
$zipKb  = round($zipSize / 1024);
$nowStr = date('Y-m-d H:i:s');

echo "[{$nowStr}] Backup OK: {$zipPath} (SQL {$sqKb} KB → ZIP {$zipKb} KB), removed {$removed} old archive(s)\n";

// ── 5. Optional e-mail notification ──────────────────────────────────────────
if ($notify) {
    try {
        $sm    = new \App\Models\SettingModel();
        $name  = $sm->get('system_name', 'Shootero');
        $email = $sm->get('admin_email', '');
        if ($email) {
            $subject = "[{$name}] Kopia zapasowa — {$nowStr}";
            $body    = "<p>Kopia zapasowa zakończona pomyślnie.</p>"
                     . "<ul><li>Plik: <code>" . basename($zipPath) . "</code></li>"
                     . "<li>Rozmiar: {$zipKb} KB</li>"
                     . "<li>Usunięto starych: {$removed}</li></ul>"
                     . "<p style='color:#666;font-size:.85em'>{$name} — automatyczne powiadomienie</p>";
            Mailer::send($email, 'Administrator', $subject, $body);
            echo "[{$nowStr}] Notification sent to {$email}\n";
        }
    } catch (\Throwable $e) {
        echo "[{$nowStr}] Notification failed: {$e->getMessage()}\n";
    }
}
