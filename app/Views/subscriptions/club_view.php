<h2 class="h4 mb-4"><i class="bi bi-credit-card-2-front"></i> Plan subskrypcji</h2>

<?php
$planColors = ['trial'=>'secondary','basic'=>'info','standard'=>'primary','premium'=>'warning text-dark'];
$plan = $sub ? ($plans[$sub['plan']] ?? null) : null;
$planKey = $sub['plan'] ?? 'trial';
?>

<?php if ($sub): ?>
<div class="row g-3" style="max-width:700px">
    <div class="col-md-6">
        <div class="card text-center border-<?= explode(' ', $planColors[$planKey] ?? 'secondary')[0] ?>">
            <div class="card-body py-4">
                <span class="badge bg-<?= $planColors[$planKey] ?? 'secondary' ?> fs-6 px-3 py-2 mb-3 d-block">
                    <?= e($plan['label'] ?? $planKey) ?>
                </span>
                <div class="fs-4 fw-bold text-muted">
                    <?php if ($plan['price_pln'] > 0): ?>
                        <?= $plan['price_pln'] ?> PLN<small class="fs-6">/mies.</small>
                    <?php else: ?>
                        Bezpłatny
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-6">Status</dt>
                    <dd class="col-6">
                        <span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : 'danger' ?>">
                            <?= e($sub['status']) ?>
                        </span>
                    </dd>
                    <dt class="col-6">Ważny do</dt>
                    <dd class="col-6">
                        <?php if ($sub['valid_until']): ?>
                            <?php $daysLeft = ceil((strtotime($sub['valid_until']) - time()) / 86400); ?>
                            <?= date('d.m.Y', strtotime($sub['valid_until'])) ?>
                            <?php if ($daysLeft <= 14 && $daysLeft >= 0): ?>
                                <span class="text-warning small">(za <?= $daysLeft ?> dni)</span>
                            <?php elseif ($daysLeft < 0): ?>
                                <span class="text-danger small">(wygasł)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            Bezterminowo
                        <?php endif; ?>
                    </dd>
                    <dt class="col-6">Max zawodników</dt>
                    <dd class="col-6"><?= $sub['max_members'] ? $sub['max_members'] : 'Nieograniczone' ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 alert alert-info" style="max-width:700px">
    <i class="bi bi-info-circle"></i>
    Aby zmienić plan lub przedłużyć subskrypcję, skontaktuj się z administratorem systemu.
</div>

<?php else: ?>
<div class="alert alert-warning">Brak informacji o subskrypcji. Skontaktuj się z administratorem.</div>
<?php endif; ?>
