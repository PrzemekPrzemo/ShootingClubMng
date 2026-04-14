<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-shield-check"></i> Audyt bezpieczeństwa systemu</h2>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('admin/security/export.md') ?>" class="btn btn-sm btn-primary" title="Pobierz raport Markdown — wklej do rozmowy z AI">
            <i class="bi bi-robot"></i> Eksportuj dla AI (.md)
        </a>
        <a href="<?= url('admin/security/export.json') ?>" class="btn btn-sm btn-outline-success" title="Pobierz raport JSON">
            <i class="bi bi-filetype-json"></i> Eksportuj JSON
        </a>
        <a href="<?= url('admin/security/export.pdf') ?>" class="btn btn-sm btn-outline-danger" title="Pobierz raport PDF">
            <i class="bi bi-file-earmark-pdf"></i> Eksportuj PDF
        </a>
        <a href="<?= url('admin/security') ?>" class="btn btn-sm btn-outline-secondary" title="Odśwież skan">
            <i class="bi bi-arrow-clockwise"></i> Skanuj ponownie
        </a>
    </div>
</div>
<div class="alert alert-info alert-sm py-2 mb-3 small">
    <i class="bi bi-robot"></i>
    Aby zgłosić znalezione problemy do Claude: pobierz plik <strong>.md</strong> i wklej jego zawartość do rozmowy z asystentem AI. Plik zawiera pełną listę problemów z zaleceniami w formacie Markdown.
</div>

<?php
$levelColors = ['critical' => 'danger', 'warning' => 'warning', 'info' => 'info'];
$levelIcons  = ['critical' => 'bi-exclamation-octagon-fill text-danger', 'warning' => 'bi-exclamation-triangle-fill text-warning', 'info' => 'bi-info-circle text-info'];
?>

<!-- Score card -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center border-<?= $score['critical'] > 0 ? 'danger' : ($score['warnings'] > 0 ? 'warning' : 'success') ?>">
            <div class="card-body">
                <div class="display-4 fw-bold <?= $score['critical'] > 0 ? 'text-danger' : ($score['pct'] >= 80 ? 'text-success' : 'text-warning') ?>">
                    <?= $score['pct'] ?>%
                </div>
                <div class="text-muted small">Ogólny wynik</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-danger">
            <div class="card-body">
                <div class="h2 fw-bold text-danger"><?= $score['critical'] ?></div>
                <div class="text-muted small">Krytyczne</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-warning">
            <div class="card-body">
                <div class="h2 fw-bold text-warning"><?= $score['warnings'] ?></div>
                <div class="text-muted small">Ostrzeżenia</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <div class="h2 fw-bold text-success"><?= $score['passed'] ?>/<?= $score['total'] ?></div>
                <div class="text-muted small">Zaliczone</div>
            </div>
        </div>
    </div>
</div>

<?php foreach ($checks as $groupName => $items): ?>
<?php $groupFail = array_filter($items, fn($c) => !$c['pass']); ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <?php if (empty($groupFail)): ?>
                <i class="bi bi-check-circle-fill text-success me-1"></i>
            <?php elseif (array_filter($groupFail, fn($c) => $c['level'] === 'critical')): ?>
                <i class="bi bi-exclamation-octagon-fill text-danger me-1"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
            <?php endif; ?>
            <?= e($groupName) ?>
        </h6>
        <span class="badge bg-<?= empty($groupFail) ? 'success' : 'secondary' ?>">
            <?= count($items) - count($groupFail) ?>/<?= count($items) ?> OK
        </span>
    </div>
    <div class="list-group list-group-flush">
    <?php foreach ($items as $item): ?>
        <div class="list-group-item py-2 d-flex align-items-start gap-2">
            <?php if ($item['pass']): ?>
                <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                <div>
                    <span class="fw-medium"><?= e($item['name']) ?></span>
                </div>
            <?php else: ?>
                <i class="bi <?= $levelIcons[$item['level']] ?? 'bi-info-circle' ?> mt-1 flex-shrink-0"></i>
                <div>
                    <span class="fw-medium"><?= e($item['name']) ?></span>
                    <div class="text-muted small mt-1"><?= e($item['suggestion']) ?></div>
                </div>
                <span class="badge bg-<?= $levelColors[$item['level']] ?? 'secondary' ?> ms-auto flex-shrink-0">
                    <?= $item['level'] ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="alert alert-secondary mt-3 small">
    <i class="bi bi-clock"></i> Audyt wykonany: <?= date('Y-m-d H:i:s') ?> — analiza statyczna lokalna, bez połączeń zewnętrznych.
</div>
