<?php
/**
 * @var array  $archives
 * @var int    $totalSize
 * @var string $backupDir
 */
$totalKb = round($totalSize / 1024);
?>
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-down me-2 text-warning"></i>Kopie zapasowe</h4>
        <p class="text-muted small mb-0 mt-1">Archiwum ZIP: zrzut bazy + pliki storage/. Katalog: <code><?= e($backupDir) ?></code></p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="<?= url('admin/backups/run') ?>">
            <?= csrf_field() ?>
            <div class="input-group">
                <button type="submit" class="btn btn-warning fw-semibold">
                    <i class="bi bi-play-circle me-1"></i>Utwórz teraz
                </button>
                <div class="input-group-text">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="notify" id="chkNotify" value="1">
                        <label class="form-check-label small" for="chkNotify">Powiadom e-mailem</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($flashError) ?></div>
<?php endif; ?>
<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= e($flashSuccess) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Archiwa</h5>
        <span class="badge bg-secondary"><?= count($archives) ?> pliki — <?= $totalKb ?> KB łącznie</span>
    </div>
    <?php if (empty($archives)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-inbox display-4 d-block mb-3"></i>
        <p>Brak archiwów. Kliknij <strong>Utwórz teraz</strong>, aby wygenerować pierwszą kopię zapasową.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Plik</th>
                    <th>Data</th>
                    <th>Rozmiar</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($archives as $arc): ?>
            <tr>
                <td>
                    <i class="bi bi-file-zip me-1 text-warning"></i>
                    <code class="small"><?= e($arc['name']) ?></code>
                </td>
                <td class="text-muted small">
                    <?= $arc['datetime'] ? e($arc['datetime']) : date('Y-m-d H:i:s', $arc['mtime']) ?>
                </td>
                <td class="text-muted small"><?= round($arc['size'] / 1024) ?> KB</td>
                <td class="text-end">
                    <a href="<?= url('admin/backups/download') ?>?file=<?= urlencode($arc['name']) ?>"
                       class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-download"></i>
                    </a>
                    <form method="post" action="<?= url('admin/backups/delete') ?>"
                          class="d-inline"
                          onsubmit="return confirm('Usunąć archiwum <?= e(addslashes($arc['name'])) ?>?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="file" value="<?= e($arc['name']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-terminal me-2"></i>Cron — automatyczne kopie</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-2">Dodaj do crontab (cPanel → Cron Jobs lub <code>crontab -e</code>):</p>
        <pre class="p-3 rounded small" style="background:rgba(0,0,0,.3);color:#94A3B8;user-select:all"># Kopia zapasowa codziennie o 02:00 z powiadomieniem e-mail
0 2 * * * <?= htmlspecialchars(PHP_BINARY ?: 'php') ?> <?= htmlspecialchars(ROOT_PATH . '/cli/backup.php') ?> --notify >> /var/log/shootero_backup.log 2>&amp;1</pre>
        <p class="text-muted small mb-0">Archiwa starsze niż 30 dni są usuwane automatycznie przez skrypt.</p>
    </div>
</div>
