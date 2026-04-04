<?php
$typeLabels = ['karabin'=>'Karabin','pistolet'=>'Pistolet','strzelba'=>'Strzelba','inne'=>'Inne'];
$conditionLabels = ['dobry'=>'Dobry','wymaga_obslugi'=>'Wymaga obsługi','uszkodzona'=>'Uszkodzona','wycofana'=>'Wycofana'];
$conditionColors = ['dobry'=>'success','wymaga_obslugi'=>'warning','uszkodzona'=>'danger','wycofana'=>'secondary'];
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-tools"></i> Sprzęt</h2>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('equipment/ammo') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-archive"></i> Amunicja
        </a>
        <a href="<?= url('equipment/weapons/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Dodaj broń
        </a>
    </div>
</div>

<!-- Ammo summary ribbon -->
<?php if (!empty($ammoSummary)): ?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($ammoSummary as $row): ?>
        <span class="badge bg-<?= (int)$row['balance'] <= 0 ? 'danger' : 'success' ?> fs-6 px-3 py-2">
            <i class="bi bi-archive me-1"></i>
            <?= e($row['caliber']) ?>: <strong><?= (int)$row['balance'] ?></strong> szt.
        </span>
    <?php endforeach; ?>
    <a href="<?= url('equipment/ammo') ?>" class="btn btn-sm btn-outline-secondary ms-1">
        <i class="bi bi-plus"></i> Ruch amunicji
    </a>
</div>
<?php endif; ?>

<!-- Filters -->
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Szukaj (nazwa, numer, kaliber)…"
               value="<?= e($filters['q']) ?>">
    </div>
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm">
            <option value="">Wszystkie typy</option>
            <?php foreach ($typeLabels as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $filters['type'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="condition" class="form-select form-select-sm">
            <option value="">Każdy stan</option>
            <?php foreach ($conditionLabels as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $filters['condition'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="is_active" class="form-select form-select-sm">
            <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Aktywna</option>
            <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Wycofana</option>
            <option value=""  <?= $filters['is_active'] === ''  ? 'selected' : '' ?>>Wszystkie</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filtruj</button>
        <a href="<?= url('equipment') ?>" class="btn btn-sm btn-link">Wyczyść</a>
    </div>
</form>

<!-- Weapons table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($result['data'])): ?>
            <p class="text-muted p-3 mb-0">Brak broni spełniającej kryteria.</p>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nazwa</th>
                    <th>Typ</th>
                    <th>Kaliber</th>
                    <th>Nr seryjny</th>
                    <th>Producent</th>
                    <th>Stan</th>
                    <th>Przypisana do</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['data'] as $w): ?>
                <tr>
                    <td>
                        <a href="<?= url('equipment/' . $w['id'] . '/edit') ?>">
                            <?= e($w['name']) ?>
                        </a>
                        <?php if (!$w['is_active']): ?>
                            <span class="badge bg-secondary ms-1 small">wycofana</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= $typeLabels[$w['type']] ?? $w['type'] ?></td>
                    <td class="small"><?= e($w['caliber'] ?? '—') ?></td>
                    <td class="small text-muted"><?= e($w['serial_number'] ?? '—') ?></td>
                    <td class="small text-muted"><?= e($w['manufacturer'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $conditionColors[$w['condition']] ?? 'secondary' ?>">
                            <?= $conditionLabels[$w['condition']] ?? $w['condition'] ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if ($w['assigned_to_last']): ?>
                            <i class="bi bi-person-fill text-primary me-1"></i>
                            <?= e($w['assigned_to_last']) ?> <?= e($w['assigned_to_first']) ?>
                            <span class="text-muted">(od <?= format_date($w['assigned_date']) ?>)</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end" style="white-space:nowrap">
                        <a href="<?= url('equipment/' . $w['id'] . '/edit') ?>"
                           class="btn btn-xs btn-outline-secondary py-0 px-2">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if ($result['last_page'] > 1): ?>
        <nav class="p-2">
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $result['last_page']; $p++): ?>
                <li class="page-item <?= $p === $result['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
