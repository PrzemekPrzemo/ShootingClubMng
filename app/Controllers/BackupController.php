<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;

/**
 * Admin backup management panel.
 *   GET  /admin/backups          — list archives
 *   POST /admin/backups/run      — trigger backup now
 *   GET  /admin/backups/download — download an archive
 *   POST /admin/backups/delete   — remove an archive
 */
class BackupController extends BaseController
{
    private string $backupDir;

    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
        $this->backupDir = ROOT_PATH . '/storage/backups';
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $archives = $this->listArchives();
        $totalSize = array_sum(array_column($archives, 'size'));

        $this->render('admin/backups', [
            'title'     => 'Kopie zapasowe',
            'archives'  => $archives,
            'totalSize' => $totalSize,
            'backupDir' => $this->backupDir,
        ]);
    }

    // ── Run now ───────────────────────────────────────────────────────────────

    public function run(): void
    {
        Csrf::verify();

        $phpBin  = PHP_BINARY ?: 'php';
        $script  = ROOT_PATH . '/cli/backup.php';

        if (!file_exists($script)) {
            Session::flash('error', 'Skrypt backup.php nie istnieje.');
            $this->redirect('admin/backups');
        }

        $notify  = !empty($_POST['notify']) ? ' --notify' : '';
        $cmd     = escapeshellarg($phpBin) . ' ' . escapeshellarg($script) . $notify . ' 2>&1';
        $output  = '';
        $code    = 0;

        exec($cmd, $lines, $code);
        $output = implode("\n", $lines);

        if ($code === 0) {
            Session::flash('success', 'Kopia zapasowa została utworzona pomyślnie.' . ($output ? " ({$output})" : ''));
        } else {
            Session::flash('error', 'Błąd podczas tworzenia kopii zapasowej: ' . htmlspecialchars($output));
        }

        $this->redirect('admin/backups');
    }

    // ── Download ──────────────────────────────────────────────────────────────

    public function download(): void
    {
        $file = basename($_GET['file'] ?? '');
        if (!$file || !str_ends_with($file, '.zip')) {
            http_response_code(400);
            exit('Invalid file name.');
        }

        $path = $this->backupDir . '/' . $file;
        if (!file_exists($path) || !str_starts_with(realpath($path), realpath($this->backupDir))) {
            http_response_code(404);
            exit('File not found.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($path);
        exit;
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(): void
    {
        Csrf::verify();

        $file = basename($_POST['file'] ?? '');
        if (!$file || !str_ends_with($file, '.zip')) {
            Session::flash('error', 'Nieprawidłowa nazwa pliku.');
            $this->redirect('admin/backups');
        }

        $path = $this->backupDir . '/' . $file;
        if (file_exists($path) && str_starts_with(realpath($path), realpath($this->backupDir))) {
            unlink($path);
            Session::flash('success', "Usunięto archiwum: {$file}");
        } else {
            Session::flash('error', 'Plik nie istnieje.');
        }

        $this->redirect('admin/backups');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function listArchives(): array
    {
        if (!is_dir($this->backupDir)) return [];

        $files = glob($this->backupDir . '/backup_*.zip') ?: [];
        rsort($files); // newest first

        return array_map(function (string $path) {
            $name = basename($path);
            // Parse timestamp from filename: backup_2026-04-10_02-00-00.zip
            preg_match('/backup_(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})\.zip/', $name, $m);
            $datetime = '';
            if (!empty($m[1]) && !empty($m[2])) {
                $datetime = $m[1] . ' ' . str_replace('-', ':', $m[2]);
            }
            return [
                'name'     => $name,
                'path'     => $path,
                'size'     => filesize($path),
                'mtime'    => filemtime($path),
                'datetime' => $datetime,
            ];
        }, $files);
    }
}
