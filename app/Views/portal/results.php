<h2 class="h4 mb-4"><i class="bi bi-bar-chart"></i> Moje wyniki</h2>

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
