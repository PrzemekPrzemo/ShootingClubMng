<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-bullseye"></i> Treningi</h2>
    <?php if (in_array($authUser['role'] ?? '', ['admin', 'zarzad'])): ?>
    <a href="<?= url('trainings/create') ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Nowy trening
    </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= url('trainings') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">Miesiąc</label>
                <input type="month" name="month" class="form-control form-control-sm"
                       value="<?= e($filters['month']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Wszystkie statusy</option>
                    <option value="planowany"   <?= $filters['status'] === 'planowany'   ? 'selected' : '' ?>>Planowany</option>
                    <option value="odbyl_sie"   <?= $filters['status'] === 'odbyl_sie'   ? 'selected' : '' ?>>Odbył się</option>
                    <option value="odwolany"    <?= $filters['status'] === 'odwolany'    ? 'selected' : '' ?>>Odwołany</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filtruj</button>
                <a href="<?= url('trainings') ?>" class="btn btn-outline-secondary btn-sm">Resetuj</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Tytuł</th>
                        <th>Godz.</th>
                        <th>Stanowisko</th>
                        <th>Instruktor</th>
                        <th>Uczestników</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($trainings as $t):
                    $sc = match($t['status']) {
                        'planowany'  => 'info',
                        'odbyl_sie'  => 'success',
                        'odwolany'   => 'secondary',
                        default      => 'secondary',
                    };
                ?>
                    <tr>
                        <td class="small"><?= format_date($t['training_date']) ?></td>
                        <td>
                            <a href="<?= url('trainings/' . $t['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= e($t['title']) ?>
                            </a>
                        </td>
                        <td class="small text-muted">
                            <?php if ($t['time_start']): ?>
                                <?= e(substr($t['time_start'], 0, 5)) ?>
                                <?php if ($t['time_end']): ?>
                                    &ndash; <?= e(substr($t['time_end'], 0, 5)) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e($t['lane'] ?? '—') ?></td>
                        <td class="small"><?= e($t['instructor_name'] ?? '—') ?></td>
                        <td class="small text-center">
                            <span class="badge bg-light text-dark border"><?= (int)($t['attendee_count'] ?? 0) ?></span>
                            <?php if ($t['max_participants']): ?>
                                <span class="text-muted">/ <?= (int)$t['max_participants'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $sc ?>"><?= e($t['status']) ?></span></td>
                        <td class="text-end">
                            <a href="<?= url('trainings/' . $t['id'] . '/attendance') ?>"
                               class="btn btn-outline-primary btn-sm py-0" title="Obecność">
                                <i class="bi bi-person-check"></i>
                            </a>
                            <a href="<?= url('trainings/' . $t['id'] . '/edit') ?>"
                               class="btn btn-outline-secondary btn-sm py-0" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($trainings)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak treningów.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">Łącznie: <?= count($trainings) ?> treningów</p>
