<?php
$statusBadge = match($generator['status']) {
    'generated' => ['bg-success',   'Wygenerowany'],
    'published' => ['bg-primary',   'Opublikowany'],
    default     => ['bg-secondary', 'Szkic'],
};
$canGenerate = $disciplineCount > 0 && $competitorCount > 0;
$hasSchedule = $generator['status'] !== 'draft' && $relayCount > 0;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('startlist') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
        <span class="badge <?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span>
    </div>
    <div class="d-flex gap-2">
        <?php if ($hasSchedule): ?>
        <a href="<?= url('startlist/' . $generator['id'] . '/export.pdf') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-pdf"></i> PDF
        </a>
        <a href="<?= url('startlist/' . $generator['id'] . '/preview') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i> Podgląd
        </a>
        <?php endif; ?>
        <a href="<?= url('startlist/' . $generator['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i> Edytuj
        </a>
    </div>
</div>

<!-- Generator info card -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">Data startu</div>
                <div class="fw-semibold"><?= e($generator['start_date']) ?> <?= substr($generator['start_time'], 0, 5) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Przerwa między zmianami</div>
                <div class="fw-semibold"><?= (int)$generator['break_minutes'] ?> min</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Zawody</div>
                <div class="fw-semibold"><?= $generator['competition_name'] ? e($generator['competition_name']) : '—' ?></div>
            </div>
            <?php if ($hasSchedule): ?>
            <div class="col-md-3">
                <div class="text-muted small">Wygenerowane zmiany</div>
                <div class="fw-semibold text-success"><?= $relayCount ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Wizard steps -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-list-check me-1"></i> Kroki konfiguracji</h6></div>
            <div class="list-group list-group-flush">

                <!-- Step 1 -->
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary me-2">1</span>
                        <strong>Podstawowe dane</strong>
                        <small class="text-muted ms-2">data, godzina, przerwa</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <a href="<?= url('startlist/' . $generator['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary py-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary me-2">2</span>
                        <strong>Dyscypliny</strong>
                        <?php if ($disciplineCount > 0): ?>
                            <span class="badge bg-success ms-2"><?= $disciplineCount ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark ms-2">Brak</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('startlist/' . $generator['id'] . '/disciplines') ?>" class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-pencil"></i> Zarządzaj
                    </a>
                </div>

                <!-- Step 3 -->
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary me-2">3</span>
                        <strong>Kombinacje dyscyplin</strong>
                        <small class="text-muted ms-2">opcjonalne</small>
                        <?php if ($comboCount > 0): ?>
                            <span class="badge bg-info ms-2"><?= $comboCount ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('startlist/' . $generator['id'] . '/combos') ?>" class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-pencil"></i> Zarządzaj
                    </a>
                </div>

                <!-- Step 4 -->
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary me-2">4</span>
                        <strong>Kategorie wiekowe</strong>
                        <small class="text-muted ms-2">opcjonalne</small>
                        <?php if ($catCount > 0): ?>
                            <span class="badge bg-info ms-2"><?= $catCount ?></span>
                        <?php else: ?>
                            <span class="text-muted small ms-2">— start open / bez podziału</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('startlist/' . $generator['id'] . '/age-categories') ?>" class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-pencil"></i> Zarządzaj
                    </a>
                </div>

                <!-- Step 5 -->
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary me-2">5</span>
                        <strong>Zawodnicy</strong>
                        <?php if ($competitorCount > 0): ?>
                            <span class="badge bg-success ms-2"><?= $competitorCount ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark ms-2">Brak</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('startlist/' . $generator['id'] . '/import') ?>" class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-upload"></i> Import CSV
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Generate panel -->
    <div class="col-lg-5">
        <div class="card border-<?= $canGenerate ? 'danger' : 'secondary' ?>">
            <div class="card-header bg-<?= $canGenerate ? 'danger' : 'secondary' ?> text-white">
                <h6 class="mb-0"><i class="bi bi-lightning-charge me-1"></i> Krok 6 — Generuj harmonogram</h6>
            </div>
            <div class="card-body">
                <?php if (!$canGenerate): ?>
                <p class="text-muted small mb-3">Uzupełnij dyscypliny (krok 2) i importuj zawodników (krok 5), aby odblokować generowanie.</p>
                <?php else: ?>
                <p class="text-muted small mb-3">
                    Algorytm przydzieli <strong><?= $competitorCount ?></strong> zawodników
                    do zmian w <strong><?= $disciplineCount ?></strong> dyscyplin.
                    <?php if ($hasSchedule): ?>
                    <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Poprzedni harmonogram zostanie nadpisany.</span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <form method="post" action="<?= url('startlist/' . $generator['id'] . '/generate') ?>"
                      onsubmit="return confirm('Wygenerować harmonogram? Poprzedni zostanie usunięty.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger w-100" <?= !$canGenerate ? 'disabled' : '' ?>>
                        <i class="bi bi-lightning-charge"></i> Generuj listę startową
                    </button>
                </form>

                <?php if ($hasSchedule): ?>
                <div class="mt-3 pt-3 border-top">
                    <a href="<?= url('startlist/' . $generator['id'] . '/preview') ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-eye"></i> Podgląd harmonogramu
                    </a>
                    <a href="<?= url('startlist/' . $generator['id'] . '/export.pdf') ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-file-pdf"></i> Pobierz PDF
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
