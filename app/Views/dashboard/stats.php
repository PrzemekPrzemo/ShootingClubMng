<?php
// Normalise variable names — the controller passes memberByStatus / compStats / licStats
$memberStats     = $memberByStatus  ?? $memberStats     ?? [];
$competitionStats = $compStats      ?? $competitionStats ?? [];
$licenseStats    = $licStats        ?? $licenseStats     ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-bar-chart-line"></i> Statystyki zarządu</h2>
    <a href="<?= url('dashboard') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="row g-4">

    <!-- Members by status — Pie chart -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong><i class="bi bi-people"></i> Zawodnicy wg statusu</strong></div>
            <div class="card-body">
                <canvas id="membersPieChart" height="220"></canvas>
            </div>
            <div class="card-footer">
                <div class="d-flex flex-wrap gap-3 justify-content-center small">
                    <?php foreach ($memberStats as $status => $count): ?>
                    <span>
                        <strong><?= $count ?></strong>
                        <span class="text-muted"><?= e(ucfirst($status)) ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Active licenses by type — Doughnut chart -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong><i class="bi bi-card-checklist"></i> Aktywne licencje wg typu</strong></div>
            <div class="card-body">
                <canvas id="licensesDoughnutChart" height="220"></canvas>
            </div>
            <div class="card-footer">
                <div class="d-flex flex-wrap gap-3 justify-content-center small">
                    <?php foreach ($licenseStats as $row): ?>
                    <span>
                        <strong><?= (int)($row['cnt'] ?? $row['count'] ?? 0) ?></strong>
                        <span class="text-muted"><?= e($row['name'] ?? $row['label'] ?? $row['type'] ?? '—') ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments by month — Bar chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <strong><i class="bi bi-cash-stack"></i> Wpłaty składek — <?= e($year ?? date('Y')) ?></strong>
            </div>
            <div class="card-body">
                <canvas id="paymentsBarChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Competitions by discipline — Horizontal bar -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <strong><i class="bi bi-trophy"></i> Zawody wg dyscypliny</strong>
            </div>
            <div class="card-body">
                <canvas id="competitionsHBarChart" height="250"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    // ── Color palette ────────────────────────────────────────
    var COLORS = [
        '#dc3545','#0d6efd','#198754','#ffc107',
        '#6f42c1','#fd7e14','#20c997','#0dcaf0',
        '#6c757d','#d63384',
    ];

    // ── 1. Members by status — Pie ───────────────────────────
    var memberLabels = <?= json_encode(array_keys($memberStats)) ?>;
    var memberData   = <?= json_encode(array_values($memberStats)) ?>;

    new Chart(document.getElementById('membersPieChart'), {
        type: 'pie',
        data: {
            labels: memberLabels,
            datasets: [{
                data: memberData,
                backgroundColor: COLORS.slice(0, memberLabels.length),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            var total = ctx.dataset.data.reduce(function(a,b){return a+b;}, 0);
                            var pct   = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
                            return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });

    // ── 2. Payments by month — Bar ───────────────────────────
    // Controller passes a 0-indexed array [0..11] already filled
    var polishMonths = ['Sty','Lut','Mar','Kwi','Maj','Cze','Lip','Sie','Wrz','Paź','Lis','Gru'];
    var payValues    = <?= json_encode(array_values($paymentsByMonth ?? [])) ?>;
    // Pad to 12 if needed
    while (payValues.length < 12) payValues.push(0);

    new Chart(document.getElementById('paymentsBarChart'), {
        type: 'bar',
        data: {
            labels: polishMonths,
            datasets: [{
                label: 'Wpłaty (PLN)',
                data: payValues,
                backgroundColor: 'rgba(25,135,84,.75)',
                borderColor: '#198754',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.parsed.y.toFixed(2) + ' PLN';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(val) { return val + ' PLN'; }
                    }
                }
            }
        }
    });

    // ── 3. Competitions by discipline — Horizontal bar ───────
    // Controller uses column names: discipline, cnt
    var rawComp    = <?= json_encode(array_values($competitionStats)) ?>;
    var compLabels = rawComp.map(function(r){ return r.discipline || r.discipline_name || r.label || '—'; });
    var compValues = rawComp.map(function(r){ return parseInt(r.cnt || r.count || 0); });

    new Chart(document.getElementById('competitionsHBarChart'), {
        type: 'bar',
        data: {
            labels: compLabels.length ? compLabels : ['Brak danych'],
            datasets: [{
                label: 'Zawody',
                data: compValues.length ? compValues : [0],
                backgroundColor: COLORS.slice(0, compLabels.length || 1),
                borderWidth: 0,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // ── 4. Licenses by type — Doughnut ───────────────────────
    // Controller uses column names: name, cnt
    var rawLic   = <?= json_encode(array_values($licenseStats)) ?>;
    var licLabels = rawLic.map(function(r){ return r.name || r.label || r.type || '—'; });
    var licValues = rawLic.map(function(r){ return parseInt(r.cnt || r.count || 0); });

    new Chart(document.getElementById('licensesDoughnutChart'), {
        type: 'doughnut',
        data: {
            labels: licLabels.length ? licLabels : ['Brak danych'],
            datasets: [{
                data: licValues.length ? licValues : [0],
                backgroundColor: COLORS.slice(0, licLabels.length || 1),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

})();
</script>
