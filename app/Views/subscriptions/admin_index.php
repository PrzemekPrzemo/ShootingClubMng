<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-credit-card-2-front"></i> Subskrypcje klubów</h2>
    <a href="<?= url('admin/subscriptions/plans') ?>" class="btn btn-sm btn-outline-primary ms-auto">
        <i class="bi bi-calculator"></i> Cennik pakietów
    </a>
</div>

<?php
$planColors = [
    'trial'    => 'secondary',
    'basic'    => 'info',
    'standard' => 'primary',
    'premium'  => 'warning',
];
$statusColors = [
    'active'    => 'success',
    'expired'   => 'danger',
    'cancelled' => 'secondary',
];
?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Klub</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Ważny do</th>
                    <th>Zawodnicy</th>
                    <th>Max</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($subscriptions as $s): ?>
                <?php
                $isExpiring = $s['valid_until'] && strtotime($s['valid_until']) - time() < 14 * 86400 && strtotime($s['valid_until']) > time();
                $isExpired  = $s['valid_until'] && strtotime($s['valid_until']) < time();
                ?>
                <tr class="<?= $isExpired ? 'table-danger' : ($isExpiring ? 'table-warning' : '') ?>">
                    <td>
                        <strong><?= e($s['club_name']) ?></strong>
                        <?php if ($s['club_email']): ?>
                            <br><small class="text-muted"><?= e($s['club_email']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $planColors[$s['plan']] ?? 'secondary' ?>">
                            <?= e($plans[$s['plan']]['label'] ?? $s['plan']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusColors[$s['status']] ?? 'secondary' ?>">
                            <?= e($s['status']) ?>
                        </span>
                    </td>
                    <td class="small">
                        <?= $s['valid_until'] ? date('d.m.Y', strtotime($s['valid_until'])) : '∞' ?>
                    </td>
                    <td class="text-center"><?= (int)$s['active_members'] ?></td>
                    <td class="text-center text-muted small"><?= $s['max_members'] ?? '∞' ?></td>
                    <td>
                        <a href="<?= url('admin/subscriptions/' . $s['club_id'] . '/edit') ?>"
                           class="btn btn-sm btn-outline-primary py-0">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($subscriptions)): ?>
                <tr><td colspan="7" class="text-muted text-center py-3">Brak subskrypcji.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
