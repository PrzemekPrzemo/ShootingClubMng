<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-bar-chart-line"></i> Analityka systemu</h2>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('admin/analytics/revenue') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-currency-dollar"></i> Przychody</a>
        <a href="<?= url('admin/analytics/clubs') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-building"></i> Kluby</a>
        <a href="<?= url('admin/analytics/activity') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clock-history"></i> Aktywność</a>
    </div>
</div>

<!-- KPI cards -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['label'=>'Aktywne kluby',       'val'=>$overview['total_clubs'] ?? 0,   'icon'=>'bi-building',         'color'=>'primary'],
        ['label'=>'Kluby trial',          'val'=>$overview['trial_clubs'] ?? 0,   'icon'=>'bi-hourglass-split',  'color'=>'secondary'],
        ['label'=>'Kluby płacące',        'val'=>$overview['paid_clubs'] ?? 0,    'icon'=>'bi-credit-card',      'color'=>'success'],
        ['label'=>'Aktywni zawodnicy',    'val'=>$overview['total_members'] ?? 0, 'icon'=>'bi-people-fill',      'color'=>'info'],
        ['label'=>'Zawody łącznie',       'val'=>$overview['total_comps'] ?? 0,   'icon'=>'bi-trophy',           'color'=>'warning'],
        ['label'=>'Przychód mies. (PLN)', 'val'=>number_format($overview['revenue_month'] ?? 0,2), 'icon'=>'bi-cash-stack', 'color'=>'success'],
        ['label'=>'Przychód rok (PLN)',   'val'=>number_format($overview['revenue_year'] ?? 0, 2), 'icon'=>'bi-graph-up',   'color'=>'success'],
        ['label'=>'Anulowane subskrypcje','val'=>$overview['churn_count'] ?? 0,   'icon'=>'bi-x-circle',         'color'=>'danger'],
    ];
    foreach ($kpis as $kpi): ?>
    <div class="col-6 col-md-3">
        <div class="card border-<?= e($kpi['color']) ?> h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi <?= e($kpi['icon']) ?> text-<?= e($kpi['color']) ?>"></i>
                    <span class="text-muted small"><?= e($kpi['label']) ?></span>
                </div>
                <div class="h4 fw-bold mb-0"><?= e($kpi['val']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Plan distribution -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Rozkład planów</h6></div>
            <div class="card-body">
                <?php
                $planColors = ['trial'=>'secondary','basic'=>'info','standard'=>'primary','premium'=>'warning'];
                $total      = array_sum(array_column($plans, 'cnt'));
                foreach ($plans as $p):
                    $pct = $total > 0 ? round($p['cnt'] / $total * 100) : 0;
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><span class="badge bg-<?= e($planColors[$p['plan']] ?? 'secondary') ?>"><?= e($p['plan']) ?></span></span>
                        <span><?= (int)$p['cnt'] ?> (<?= (int)$pct ?>%)</span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-<?= e($planColors[$p['plan']] ?? 'secondary') ?>" style="width:<?= (int)$pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($plans)): ?><p class="text-muted small">Brak danych</p><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top clubs -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Top 10 klubów (wg zawodników)</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Klub</th><th>Plan</th><th class="text-center">Zawodnicy</th><th class="text-center">Zawody</th></tr></thead>
                    <tbody>
                    <?php foreach ($topClubs as $c): ?>
                    <tr>
                        <td><?= e($c['name']) ?></td>
                        <td><span class="badge bg-<?= e($planColors[$c['plan']] ?? 'secondary') ?>"><?= e($c['plan'] ?? '—') ?></span></td>
                        <td class="text-center"><?= (int)$c['members'] ?></td>
                        <td class="text-center"><?= (int)$c['competitions'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topClubs)): ?><tr><td colspan="4" class="text-muted text-center py-3">Brak danych</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Monthly growth -->
<?php if (!empty($growth['clubs']) || !empty($growth['members'])): ?>
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Wzrost (ostatnie 6 miesięcy)</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <p class="small text-muted mb-1">Nowe kluby</p>
                <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($growth['clubs'] as $row): ?>
                    <div class="text-center">
                        <div class="small fw-bold"><?= (int)$row['clubs'] ?></div>
                        <div class="text-muted" style="font-size:.7rem"><?= e($row['month']) ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <p class="small text-muted mb-1">Nowi zawodnicy</p>
                <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($growth['members'] as $row): ?>
                    <div class="text-center">
                        <div class="small fw-bold"><?= (int)$row['members'] ?></div>
                        <div class="text-muted" style="font-size:.7rem"><?= e($row['month']) ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent activity -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h6 class="mb-0">Ostatnia aktywność</h6>
        <a href="<?= url('admin/analytics/activity') ?>" class="small">Pokaż więcej →</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Czas</th><th>Użytkownik</th><th>Klub</th><th>Akcja</th><th>Szczegóły</th></tr></thead>
            <tbody>
            <?php foreach ($activity as $row): ?>
            <tr>
                <td class="text-muted small" style="white-space:nowrap"><?= e(substr($row['created_at'] ?? '', 0, 16)) ?></td>
                <td class="small"><?= e($row['username'] ?? '—') ?></td>
                <td class="small"><?= e($row['club_name'] ?? '—') ?></td>
                <td><span class="badge bg-secondary"><?= e($row['action']) ?></span></td>
                <td class="small text-muted"><?= e(mb_substr($row['details'] ?? '', 0, 60)) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($activity)): ?><tr><td colspan="5" class="text-muted text-center py-3">Brak danych</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
