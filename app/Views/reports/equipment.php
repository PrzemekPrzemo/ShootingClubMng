<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
    </div>
    <a href="<?= url('reports/equipment?format=csv') ?>" class="btn btn-sm btn-outline-dark">
        <i class="bi bi-file-earmark-arrow-down"></i> Pobierz CSV
    </a>
</div>

<?php if (empty($weapons)): ?>
<div class="alert alert-info">
    Brak danych o broni. <a href="<?= url('equipment') ?>">Przejdź do modułu sprzętu</a>, aby dodać broń.
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Typ</th>
                    <th>Nr seryjny</th>
                    <th>Kaliber</th>
                    <th>Producent</th>
                    <th>Stan</th>
                    <th>Przypisany do</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $condColors = ['dobry'=>'success','wymaga_obslugi'=>'warning','uszkodzona'=>'danger','wycofana'=>'secondary'];
            foreach ($weapons as $w):
            ?>
            <tr>
                <td class="text-muted small">#<?= $w['id'] ?></td>
                <td><?= e($w['name']) ?></td>
                <td><span class="badge bg-secondary"><?= e($w['type']) ?></span></td>
                <td class="font-monospace small"><?= e($w['serial_number'] ?? '—') ?></td>
                <td class="small"><?= e($w['caliber'] ?? '—') ?></td>
                <td class="small"><?= e($w['manufacturer'] ?? '—') ?></td>
                <td>
                    <span class="badge bg-<?= $condColors[$w['condition']] ?? 'secondary' ?>">
                        <?= e($w['condition']) ?>
                    </span>
                </td>
                <td class="small"><?= e(trim(($w['assigned_to_last'] ?? '') . ' ' . ($w['assigned_to_first'] ?? '')) ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2 text-muted small">Łącznie: <?= count($weapons) ?> sztuk broni</div>
<?php endif; ?>
