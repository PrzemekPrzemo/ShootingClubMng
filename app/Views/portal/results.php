<h2 class="h4 mb-4"><i class="bi bi-bar-chart"></i> Moje wyniki</h2>

<?php if (!empty($stats['summary']['total_starts'])): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-white shadow-sm">
            <div class="card-body py-3">
                <div class="fw-bold fs-4 text-danger"><?= (int)$stats['summary']['total_starts'] ?></div>
                <div class="small text-muted">Startów</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-white shadow-sm">
            <div class="card-body py-3">
                <div class="fw-bold fs-4 text-primary"><?= number_format((float)($stats['summary']['best_score'] ?? 0), 1) ?></div>
                <div class="small text-muted">Najlepszy wynik</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-white shadow-sm">
            <div class="card-body py-3">
                <div class="fw-bold fs-4 text-success"><?= number_format((float)($stats['summary']['avg_score'] ?? 0), 1) ?></div>
                <div class="small text-muted">Średnia</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 bg-white shadow-sm">
            <div class="card-body py-3">
                <div class="fw-bold fs-4 text-warning">
                    🥇<?= (int)($stats['summary']['gold'] ?? 0) ?>
                    🥈<?= (int)($stats['summary']['silver'] ?? 0) ?>
                    🥉<?= (int)($stats['summary']['bronze'] ?? 0) ?>
                </div>
                <div class="small text-muted">Medale</div>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($stats['labels'])): ?>
<div class="card mb-4">
    <div class="card-header small fw-semibold">Postęp wyników (ostatnie starty)</div>
    <div class="card-body">
        <canvas id="scoreChart" height="100"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('scoreChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($stats['labels'], JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'Wynik',
            data: <?= json_encode($stats['scores']) ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220,53,69,.1)',
            tension: 0.3,
            fill: true,
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { maxRotation: 45, font: { size: 10 } } },
            y: { beginAtZero: false }
        }
    }
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php if ($results): ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Zawody</th>
                    <th>Konkurencja</th>
                    <th>Data</th>
                    <th>Wynik</th>
                    <th>X</th>
                    <th>Miejsce</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td>
                        <div><?= e($r['competition_name']) ?></div>
                        <small class="text-muted"><?= e($r['location'] ?? '') ?></small>
                    </td>
                    <td class="small"><?= e($r['event_name']) ?></td>
                    <td class="small"><?= format_date($r['competition_date']) ?></td>
                    <td><strong><?= e($r['score'] ?? '—') ?></strong></td>
                    <td class="text-muted"><?= e($r['score_inner'] ?? '—') ?></td>
                    <td>
                        <?php if ($r['place']): ?>
                            <span class="badge bg-<?= $r['place'] <= 3 ? 'warning text-dark' : 'secondary' ?>">
                                <?= $r['place'] ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Brak wyników. Wyniki pojawią się po wprowadzeniu ich przez klub.</div>
<?php endif; ?>
