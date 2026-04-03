<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="get">
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <div class="input-group input-group-sm">
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtruj</button>
            </div>
        </form>
        <a href="?format=csv&type=<?= e($type) ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-arrow-down"></i> CSV
        </a>
    </div>
</div>

<?php if ($type === 'payments' && !empty($summary)): ?>
<div class="row g-2 mb-3">
    <?php foreach ($summary as $s): ?>
    <div class="col-auto">
        <div class="card py-2 px-3">
            <div class="small text-muted"><?= e($s['name']) ?></div>
            <strong><?= format_money($s['total']) ?></strong>
            <small class="text-muted"> <?= $s['count'] ?> wpłat</small>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="col-auto">
        <div class="card py-2 px-3 border-success">
            <div class="small text-muted">RAZEM</div>
            <strong class="text-success"><?= format_money($total ?? 0) ?></strong>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                <?php if ($type === 'payments'): ?>
                    <tr><th>Data</th><th>Zawodnik</th><th>Typ</th><th>Kwota</th><th>Metoda</th><th>Okres</th></tr>
                <?php else: ?>
                    <tr><th>Nr</th><th>Zawodnik</th><th>E-mail</th><th>Telefon</th><th></th></tr>
                <?php endif; ?>
                </thead>
                <tbody>
                <?php if ($type === 'payments'): ?>
                    <?php foreach ($data as $p): ?>
                    <tr>
                        <td class="small"><?= format_date($p['payment_date']) ?></td>
                        <td><a href="<?= url('members/' . $p['member_id']) ?>"><?= e($p['last_name']) ?> <?= e($p['first_name']) ?></a></td>
                        <td class="small"><?= e($p['type_name']) ?></td>
                        <td class="fw-semibold text-success"><?= format_money($p['amount']) ?></td>
                        <td class="small"><?= e($p['method']) ?></td>
                        <td class="small"><?= $p['period_year'] ?><?= $p['period_month'] ? '/' . str_pad($p['period_month'],2,'0',STR_PAD_LEFT) : '' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($data as $d): ?>
                    <tr class="table-warning">
                        <td class="small"><?= e($d['member_number']) ?></td>
                        <td><a href="<?= url('members/' . $d['id']) ?>"><?= e($d['last_name']) ?> <?= e($d['first_name']) ?></a></td>
                        <td class="small"><?= e($d['email'] ?? '—') ?></td>
                        <td class="small"><?= e($d['phone'] ?? '—') ?></td>
                        <td><a href="<?= url('finances/create?member_id=' . $d['id']) ?>" class="btn btn-sm btn-success py-0">+ wpłata</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($data)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak danych.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">Rekordów: <?= count($data) ?></p>
