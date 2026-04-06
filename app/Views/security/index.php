<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-shield-exclamation"></i> Bezpieczeństwo systemu</h2>
    <a href="<?= url('security') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-clockwise"></i> Skanuj ponownie
    </a>
</div>

<!-- Score card -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-<?= $score['color'] ?> h-100">
            <div class="card-body text-center">
                <div style="font-size:3rem;font-weight:800;color:var(--bs-<?= $score['color'] ?>)">
                    <?= $score['grade'] ?>
                </div>
                <div class="text-muted small">Ocena ogólna</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-<?= $score['color'] ?>"><?= $score['pct'] ?>%</div>
                <div class="text-muted small">Testów zaliczonych</div>
                <div class="progress mt-2" style="height:6px">
                    <div class="progress-bar bg-<?= $score['color'] ?>" style="width:<?= $score['pct'] ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-danger"><?= $score['critical'] ?></div>
                <div class="text-muted small">Problemy krytyczne</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-warning"><?= $score['warnings'] ?></div>
                <div class="text-muted small">Ostrzeżenia</div>
            </div>
        </div>
    </div>
</div>

<!-- Check groups -->
<?php foreach ($checks as $groupName => $items): ?>
<?php
    $groupFailed   = array_filter($items, fn($c) => !$c['pass']);
    $groupCritical = array_filter($groupFailed, fn($c) => $c['severity'] === 'critical');
    $groupColor    = !empty($groupCritical) ? 'danger' : (!empty($groupFailed) ? 'warning' : 'success');
?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
            <i class="bi bi-<?= $groupColor === 'success' ? 'check-circle-fill text-success' : ($groupColor === 'danger' ? 'x-circle-fill text-danger' : 'exclamation-triangle-fill text-warning') ?>"></i>
            <?= e($groupName) ?>
        </span>
        <span class="badge bg-<?= $groupColor ?>">
            <?= count($items) - count($groupFailed) ?> / <?= count($items) ?>
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <tbody>
                <?php foreach ($items as $check): ?>
                <tr class="<?= $check['pass'] ? '' : ($check['severity'] === 'critical' ? 'table-danger' : ($check['severity'] === 'warning' ? 'table-warning' : 'table-light')) ?>">
                    <td style="width:2rem" class="text-center">
                        <?php if ($check['pass']): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <?php elseif ($check['severity'] === 'critical'): ?>
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        <?php elseif ($check['severity'] === 'warning'): ?>
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                        <?php else: ?>
                        <i class="bi bi-info-circle text-secondary"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="<?= $check['pass'] ? 'text-success' : '' ?> fw-semibold">
                            <?= e($check['label']) ?>
                        </span>
                        <?php if (!$check['pass']): ?>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-lightbulb"></i>
                            <?= $check['recommendation'] ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="width:7rem" class="text-end">
                        <?php if ($check['pass']): ?>
                        <span class="badge bg-success">OK</span>
                        <?php elseif ($check['severity'] === 'critical'): ?>
                        <span class="badge bg-danger">Krytyczny</span>
                        <?php elseif ($check['severity'] === 'warning'): ?>
                        <span class="badge bg-warning text-dark">Ostrzeżenie</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Info</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<div class="alert alert-secondary small mt-3">
    <i class="bi bi-shield-check"></i>
    <strong>Uwaga:</strong> Skany bezpieczeństwa wykonują wyłącznie lokalne sprawdzenia konfiguracji serwera, plików i bazy danych.
    Żadne dane nie są przesyłane na zewnątrz. Wyniki odzwierciedlają stan w chwili skanowania.
    <br>Zalecane jest regularne uruchamianie skanu (np. raz w tygodniu) i usuwanie wykrytych problemów.
</div>
