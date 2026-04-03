<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm">
                <?php for ($y = date('Y')+1; $y >= date('Y')-3; $y--): ?>
                    <option value="<?= $y ?>" <?= $year==$y ? 'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filtruj</button>
        </form>
        <a href="?format=csv&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-arrow-down"></i> CSV
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr><th>Data</th><th>Nazwa</th><th>Dyscyplina</th><th>Miejsce</th><th>Status</th><th>Zgłoszeń</th></tr>
            </thead>
            <tbody>
            <?php foreach ($data as $c): ?>
                <tr>
                    <td class="small"><?= format_date($c['competition_date']) ?></td>
                    <td><a href="<?= url('competitions/' . $c['id']) ?>"><?= e($c['name']) ?></a></td>
                    <td class="small"><?= e($c['discipline_name'] ?? '—') ?></td>
                    <td class="small"><?= e($c['location'] ?? '—') ?></td>
                    <td><?php $sc = match($c['status']) { 'zakonczone'=>'dark','otwarte'=>'success','zamkniete'=>'warning',default=>'secondary' }; ?><span class="badge bg-<?= $sc ?>"><?= e($c['status']) ?></span></td>
                    <td><?= $c['entry_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak zawodów.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Rekordów: <?= count($data) ?></p>
