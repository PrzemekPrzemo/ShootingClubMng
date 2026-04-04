<h2 class="h4 mb-4"><i class="bi bi-speedometer2"></i> Witaj, <?= e($memberUser['full_name']) ?></h2>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <i class="bi bi-card-checklist text-success" style="font-size:1.75rem"></i>
                <div class="display-6 fw-bold text-success mt-1"><?= count($licenses) ?></div>
                <div class="text-muted small">Aktywne licencje</div>
                <a href="<?= url('portal/profile') ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <i class="bi bi-trophy text-primary" style="font-size:1.75rem"></i>
                <div class="display-6 fw-bold text-primary mt-1"><?= count($openCompetitions) ?></div>
                <div class="text-muted small">Otwarte zapisy</div>
                <a href="<?= url('portal/competitions') ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <?php $hasFees = !empty($pendingFees); ?>
        <div class="card border-<?= $hasFees ? 'warning' : 'success' ?> h-100">
            <div class="card-body text-center">
                <i class="bi bi-cash text-<?= $hasFees ? 'warning' : 'success' ?>" style="font-size:1.75rem"></i>
                <div class="h5 fw-bold mt-1"><?= $hasFees ? 'Sprawdź' : 'OK' ?></div>
                <div class="text-muted small">Opłaty <?= date('Y') ?></div>
                <a href="<?= url('portal/fees') ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <i class="bi bi-bar-chart text-info" style="font-size:1.75rem"></i>
                <div class="display-6 fw-bold text-info mt-1"><?= count($recentResults) ?></div>
                <div class="text-muted small">Ostatnie wyniki</div>
                <a href="<?= url('portal/results') ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php if ($openCompetitions): ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-trophy"></i> Otwarte zapisy na zawody</strong>
                <a href="<?= url('portal/competitions') ?>" class="btn btn-sm btn-outline-primary">Wszystkie</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                    <?php foreach (array_slice($openCompetitions, 0, 5) as $c): ?>
                        <tr>
                            <td>
                                <div><?= e($c['name']) ?></div>
                                <small class="text-muted"><?= format_date($c['competition_date']) ?> &mdash; <?= e($c['location'] ?? '') ?></small>
                            </td>
                            <td class="text-end align-middle">
                                <?php if ($c['entry_id']): ?>
                                    <span class="badge bg-success">Zapisany/a</span>
                                <?php else: ?>
                                    <a href="<?= url('portal/competitions/' . $c['id'] . '/register') ?>" class="btn btn-sm btn-outline-danger">Zapisz się</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($recentResults): ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-bar-chart"></i> Ostatnie wyniki</strong>
                <a href="<?= url('portal/results') ?>" class="btn btn-sm btn-outline-info">Wszystkie</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                    <?php foreach ($recentResults as $r): ?>
                        <tr>
                            <td>
                                <div><?= e($r['competition_name']) ?></div>
                                <small class="text-muted"><?= e($r['event_name']) ?></small>
                            </td>
                            <td class="text-end align-middle">
                                <strong><?= e($r['score']) ?></strong>
                                <?php if ($r['place']): ?><br><span class="badge bg-secondary"><?= $r['place'] ?>. miejsce</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($licenses): ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-card-checklist"></i> Aktywne licencje</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($licenses as $l): ?>
                        <?php $days = days_until($l['valid_until']); ?>
                        <tr>
                            <td>
                                <code><?= e($l['license_number']) ?></code>
                                <small class="text-muted d-block"><?= e($l['discipline_name'] ?? '—') ?></small>
                            </td>
                            <td class="text-end align-middle">
                                <span class="badge bg-<?= alert_class($days, 60) ?>">do <?= format_date($l['valid_until']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
