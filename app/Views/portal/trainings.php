<h2 class="h4 mb-4"><i class="bi bi-calendar-check"></i> Treningi</h2>

<?php if (empty($trainings)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Brak nadchodzących treningów.
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Data</th>
                    <th>Godzina</th>
                    <th>Miejsce</th>
                    <th>Instruktor</th>
                    <th>Opis</th>
                    <th class="text-center">Zapisany/a</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($trainings as $t): ?>
                <tr>
                    <td class="fw-semibold"><?= format_date($t['training_date']) ?></td>
                    <td class="small text-muted">
                        <?= e(substr($t['time_start'] ?? '', 0, 5)) ?>
                        <?php if (!empty($t['time_end'])): ?>
                            – <?= e(substr($t['time_end'], 0, 5)) ?>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($t['location'] ?? '—') ?></td>
                    <td class="small"><?= e($t['instructor_name'] ?? '—') ?></td>
                    <td class="small text-muted"><?= e($t['notes'] ?? '') ?></td>
                    <td class="text-center">
                        <?php if ($t['enrolled_id']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Tak</span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['enrolled_id'] && !$t['attended']): ?>
                        <form method="post" action="<?= url('portal/trainings/' . (int)$t['id'] . '/unenroll') ?>"
                              onsubmit="return confirm('Wypisać się z treningu?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Wypisz
                            </button>
                        </form>
                        <?php elseif (!$t['enrolled_id']): ?>
                        <form method="post" action="<?= url('portal/trainings/' . (int)$t['id'] . '/enroll') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-success">
                                <i class="bi bi-calendar-plus"></i> Zapisz się
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">
    <i class="bi bi-info-circle"></i>
    Możesz wypisać się z treningu tylko jeśli jeszcze nie odbyło się potwierdzenie obecności.
</p>
<?php endif; ?>
