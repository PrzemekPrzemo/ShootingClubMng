<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-list-ol"></i> Listy startowe</h2>
    <a href="<?= url('startlist/create') ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Nowy generator
    </a>
</div>

<?php if (empty($generators)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-list-ol" style="font-size:2.5rem"></i>
        <p class="mt-3 mb-2">Brak generatorów list startowych.</p>
        <a href="<?= url('startlist/create') ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-plus-lg"></i> Utwórz pierwszy generator
        </a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nazwa</th>
                        <th>Zawody</th>
                        <th>Data startu</th>
                        <th>Status</th>
                        <th>Utworzono</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($generators as $g): ?>
                    <?php
                    $statusBadge = match($g['status']) {
                        'generated'  => ['bg-success',   'Wygenerowany'],
                        'published'  => ['bg-primary',   'Opublikowany'],
                        default      => ['bg-secondary', 'Szkic'],
                    };
                    ?>
                    <tr>
                        <td>
                            <a href="<?= url('startlist/' . $g['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= e($g['name']) ?>
                            </a>
                        </td>
                        <td class="small text-muted"><?= $g['competition_name'] ? e($g['competition_name']) : '—' ?></td>
                        <td class="small"><?= e($g['start_date']) ?> <?= substr($g['start_time'], 0, 5) ?></td>
                        <td><span class="badge <?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span></td>
                        <td class="small text-muted"><?= format_date($g['created_at']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('startlist/' . $g['id']) ?>" class="btn btn-sm btn-outline-primary py-0" title="Otwórz wizard">
                                <i class="bi bi-sliders"></i>
                            </a>
                            <?php if ($g['status'] !== 'draft'): ?>
                            <a href="<?= url('startlist/' . $g['id'] . '/preview') ?>" class="btn btn-sm btn-outline-secondary py-0" title="Podgląd">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= url('startlist/' . $g['id'] . '/export.pdf') ?>" class="btn btn-sm btn-outline-secondary py-0" title="PDF">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                            <?php endif; ?>
                            <form method="post" action="<?= url('startlist/' . $g['id'] . '/delete') ?>"
                                  class="d-inline" onsubmit="return confirm('Usunąć generator <?= e(addslashes($g['name'])) ?>?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
