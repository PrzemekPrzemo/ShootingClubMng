<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . $member['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Badania lekarskie — <?= e($member['last_name']) ?> <?= e($member['first_name']) ?></h2>
    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
    <a href="<?= url('members/' . $member['id'] . '/exams/create') ?>" class="btn btn-sm btn-danger ms-auto">
        <i class="bi bi-plus-lg"></i> Dodaj badanie
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($examMatrix)): ?>
<!-- Matryca badań: status per typ -->
<div class="card mb-3">
    <div class="card-header"><strong><i class="bi bi-heart-pulse"></i> Status badań (per typ)</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>Typ badania</th><th>Ostatnie badanie</th><th>Ważne do</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($examMatrix as $row): ?>
                <?php
                $statusMap = [
                    'ok'      => ['success',   'Ważne'],
                    'warn'    => ['warning',   'Wygasa wkrótce'],
                    'expired' => ['danger',    'Wygasłe'],
                    'missing' => ['secondary', 'Brak'],
                ];
                [$cls, $label] = $statusMap[$row['status']] ?? ['secondary', '—'];
                ?>
                <tr>
                    <td><?= e($row['type_name']) ?></td>
                    <td><?= $row['exam_date'] ? format_date($row['exam_date']) : '—' ?></td>
                    <td>
                        <?= $row['valid_until'] ? format_date($row['valid_until']) : '—' ?>
                        <?php if ($row['valid_until'] && $row['days_left'] !== null): ?>
                            <small class="text-muted">
                                (<?= $row['days_left'] >= 0 ? 'za ' . $row['days_left'] . ' dni' : abs($row['days_left']) . ' dni temu' ?>)
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $cls ?>"><?= $label ?></span>
                        <?php if (!empty($row['file_path'])): ?>
                            <a href="<?= url('members/' . $member['id'] . '/exams/file/' . $row['type_id']) ?>"
                               class="btn btn-xs btn-outline-secondary py-0 px-1 ms-1" target="_blank"
                               title="Pobierz zaświadczenie">
                                <i class="bi bi-paperclip"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Historia wszystkich badań -->
<div class="card">
    <div class="card-header"><strong>Historia badań</strong></div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Typ</th>
                    <th>Data badania</th>
                    <th>Ważne do</th>
                    <th>Status</th>
                    <th>Plik</th>
                    <th>Uwagi</th>
                    <th>Dodał</th>
                    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                    <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($exams as $exam): ?>
                <?php $days = days_until($exam['valid_until']); ?>
                <tr>
                    <td class="small">
                        <?= $exam['exam_type_name'] ? e($exam['exam_type_name']) : '<span class="text-muted">ogólne</span>' ?>
                    </td>
                    <td><?= format_date($exam['exam_date']) ?></td>
                    <td><?= format_date($exam['valid_until']) ?></td>
                    <td>
                        <span class="badge bg-<?= alert_class($days, 30) ?>">
                            <?php if ($days === null): ?>bezterminowa
                            <?php elseif ($days < 0): ?>Wygasłe (<?= abs($days) ?> dni temu)
                            <?php elseif ($days === 0): ?>Dziś wygasa
                            <?php else: ?>za <?= $days ?> dni<?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($exam['file_path'])): ?>
                        <a href="<?= url('members/' . $member['id'] . '/exams/' . $exam['id'] . '/file') ?>"
                           target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-1">
                            <i class="bi bi-paperclip"></i>
                        </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e($exam['notes'] ?? '—') ?></td>
                    <td class="small"><?= e($exam['created_by_name']) ?></td>
                    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                    <td class="text-end" style="white-space:nowrap">
                        <a href="<?= url('members/' . $member['id'] . '/exams/' . $exam['id'] . '/edit') ?>"
                           class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('members/' . $member['id'] . '/exams/' . $exam['id'] . '/delete') ?>"
                              class="d-inline" onsubmit="return confirm('Usunąć badanie?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($exams)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak badań lekarskich.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
