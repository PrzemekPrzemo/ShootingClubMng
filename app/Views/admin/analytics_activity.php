<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/analytics') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-clock-history"></i> Log aktywności — strona <?= (int)$page ?></h2>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:130px">Czas</th>
                    <th>Użytkownik</th>
                    <th>Klub</th>
                    <th>Akcja</th>
                    <th>Encja</th>
                    <th>Szczegóły</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="text-muted small"><?= e(substr($row['created_at'] ?? '', 0, 16)) ?></td>
                    <td class="small"><?= e($row['username'] ?? '—') ?></td>
                    <td class="small"><?= e($row['club_name'] ?? '—') ?></td>
                    <td><span class="badge bg-secondary"><?= e($row['action']) ?></span></td>
                    <td class="small text-muted"><?= e($row['entity'] ?? '') ?></td>
                    <td class="small"><?= e(mb_substr($row['details'] ?? '', 0, 80)) ?></td>
                    <td class="small text-muted"><?= e($row['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-muted text-center py-3">Brak wpisów</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <?php if ($page > 1): ?>
        <a href="<?= url('admin/analytics/activity?page=' . ($page - 1)) ?>" class="btn btn-sm btn-outline-secondary">← Poprzednia</a>
    <?php endif; ?>
    <?php if (count($rows) === 50): ?>
        <a href="<?= url('admin/analytics/activity?page=' . ($page + 1)) ?>" class="btn btn-sm btn-outline-secondary">Następna →</a>
    <?php endif; ?>
</div>
