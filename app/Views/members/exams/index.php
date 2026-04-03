<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . $member['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Badania sportowe — <?= e($member['last_name']) ?> <?= e($member['first_name']) ?></h2>
    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
    <a href="<?= url('members/' . $member['id'] . '/exams/create') ?>" class="btn btn-sm btn-danger ms-auto">
        <i class="bi bi-plus-lg"></i> Dodaj badanie
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Data badania</th>
                    <th>Ważne do</th>
                    <th>Status</th>
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
                    <td><?= format_date($exam['exam_date']) ?></td>
                    <td><?= format_date($exam['valid_until']) ?></td>
                    <td>
                        <span class="badge bg-<?= alert_class($days, 30) ?>">
                            <?php if ($days < 0): ?>Wygasłe (<?= abs($days) ?> dni temu)
                            <?php elseif ($days === 0): ?>Dziś wygasa
                            <?php else: ?>za <?= $days ?> dni<?php endif; ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= e($exam['notes'] ?? '—') ?></td>
                    <td class="small"><?= e($exam['created_by_name']) ?></td>
                    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                    <td class="text-end">
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
                <tr><td colspan="6" class="text-center text-muted py-4">Brak badań sportowych.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
