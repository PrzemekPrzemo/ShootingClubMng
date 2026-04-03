<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
    </div>
    <a href="?format=csv&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-arrow-down"></i> CSV
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr><th>Nr licencji</th><th>Typ</th><th>Zawodnik</th><th>Dyscyplina</th><th>Wydana</th><th>Ważna do</th><th>Termin</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($data as $l): ?>
                <?php $days = days_until($l['valid_until']); ?>
                <tr>
                    <td><code><?= e($l['license_number']) ?></code></td>
                    <td><span class="badge bg-secondary"><?= e($l['license_type']) ?></span></td>
                    <td><a href="<?= url('members/' . $l['member_id']) ?>"><?= e($l['last_name']) ?> <?= e($l['first_name']) ?></a></td>
                    <td class="small"><?= e($l['discipline_name'] ?? '—') ?></td>
                    <td class="small"><?= format_date($l['issue_date']) ?></td>
                    <td class="small"><?= format_date($l['valid_until']) ?></td>
                    <td><span class="badge bg-<?= alert_class($days, 60) ?>"><?= $days >= 0 ? "za {$days} dni" : abs($days).' dni temu' ?></span></td>
                    <td><?php $sc = match($l['status']) { 'aktywna'=>'success','wygasla'=>'danger',default=>'warning' }; ?><span class="badge bg-<?= $sc ?>"><?= e($l['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak licencji.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Rekordów: <?= count($data) ?></p>
