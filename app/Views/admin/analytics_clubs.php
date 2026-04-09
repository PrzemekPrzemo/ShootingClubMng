<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/analytics') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-building"></i> Metryki klubów</h2>
</div>

<?php
$planColors = ['trial'=>'secondary','basic'=>'info','standard'=>'primary','premium'=>'warning'];
$statusColors = ['active'=>'success','expired'=>'danger','cancelled'=>'secondary'];
?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Klub</th>
                    <th>Plan</th>
                    <th>Sub. status</th>
                    <th>Ważny do</th>
                    <th class="text-center">Zawodnicy</th>
                    <th class="text-center">Zawody</th>
                    <th class="text-center">Treningi</th>
                    <th>Ostatnia aktywność</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clubs as $c): ?>
                <?php
                $isExpired  = $c['valid_until'] && strtotime($c['valid_until']) < time();
                $isExpiring = $c['valid_until'] && strtotime($c['valid_until']) - time() < 14*86400 && !$isExpired;
                ?>
                <tr class="<?= $isExpired ? 'table-danger' : ($isExpiring ? 'table-warning' : '') ?>">
                    <td><strong><?= e($c['name']) ?></strong></td>
                    <td><span class="badge bg-<?= $planColors[$c['plan']] ?? 'secondary' ?>"><?= e($c['plan'] ?? '—') ?></span></td>
                    <td><span class="badge bg-<?= $statusColors[$c['sub_status']] ?? 'secondary' ?>"><?= e($c['sub_status'] ?? '—') ?></span></td>
                    <td class="small"><?= $c['valid_until'] ? date('d.m.Y', strtotime($c['valid_until'])) : '∞' ?></td>
                    <td class="text-center"><?= (int)$c['active_members'] ?></td>
                    <td class="text-center"><?= (int)$c['competitions'] ?></td>
                    <td class="text-center"><?= (int)$c['trainings'] ?></td>
                    <td class="small text-muted"><?= $c['last_activity'] ? date('d.m.Y', strtotime($c['last_activity'])) : '—' ?></td>
                    <td>
                        <a href="<?= url('admin/switch-club/' . $c['id']) ?>" class="btn btn-xs btn-outline-primary py-0 px-1" title="Przełącz">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($clubs)): ?>
                <tr><td colspan="9" class="text-muted text-center py-3">Brak danych</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
