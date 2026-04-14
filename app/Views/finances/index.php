<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-cash-stack"></i> Finanse</h2>
    <div class="d-flex gap-2">
        <a href="<?= url('finances/debts?year=' . $currentYear) ?>" class="btn btn-warning btn-sm">
            <i class="bi bi-exclamation-triangle"></i> Zaległości
        </a>
        <a href="<?= url('finances/create') ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-plus-lg"></i> Dodaj wpłatę
        </a>
    </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body py-2">
                <div class="text-muted small">Łącznie <?= $currentYear ?></div>
                <div class="h4 text-success mb-0"><?= format_money($totalYear) ?></div>
            </div>
        </div>
    </div>
    <?php foreach (array_slice($summaryByType, 0, 2) as $s): ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-2">
                <div class="text-muted small"><?= e($s['name']) ?></div>
                <div class="h5 mb-0"><?= format_money($s['total']) ?> <small class="text-muted">(<?= $s['count'] ?> wpłat)</small></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<form method="get" action="<?= url('finances') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Szukaj (nazwisko, nr ref.)"
                       value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2">
                <select name="payment_type_id" class="form-select form-select-sm">
                    <option value="">Wszystkie typy</option>
                    <?php foreach ($paymentTypes as $pt): ?>
                        <option value="<?= $pt['id'] ?>" <?= $filters['payment_type_id'] == $pt['id'] ? 'selected':'' ?>>
                            <?= e($pt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                        <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Szukaj</button>
                <a href="<?= url('finances') ?>" class="btn btn-outline-secondary btn-sm">Resetuj</a>
            </div>
            <div class="col-md-3 text-end">
                <a href="<?= url('reports/finances?year=' . $currentYear) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-arrow-down"></i> Eksport CSV
                </a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Zawodnik</th>
                        <th>Typ</th>
                        <th>Kwota</th>
                        <th>Metoda</th>
                        <th>Okres</th>
                        <th>Nr ref.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $p): ?>
                    <tr>
                        <td class="small"><?= format_date($p['payment_date']) ?></td>
                        <td>
                            <a href="<?= url('members/' . $p['member_id']) ?>" class="text-decoration-none">
                                <?= e($p['last_name']) ?> <?= e($p['first_name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= e($p['type_name']) ?></td>
                        <td class="fw-semibold text-success"><?= format_money($p['amount']) ?></td>
                        <td class="small"><?= e($p['method']) ?></td>
                        <td class="small"><?= $p['period_year'] ?><?= $p['period_month'] ? ('/' . str_pad($p['period_month'], 2, '0', STR_PAD_LEFT)) : '' ?></td>
                        <td class="small text-muted"><?= e($p['reference'] ?? '—') ?></td>
                        <td class="text-end">
                            <a href="<?= url('finances/' . $p['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm py-0">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                            <form method="post" action="<?= url('finances/' . $p['id'] . '/delete') ?>"
                                  class="d-inline" onsubmit="return confirm('Usunąć wpłatę?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm py-0"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak wpłat.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if ($result['last_page'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $result['last_page']; $p++): ?>
            <li class="page-item <?= $p === $result['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('finances?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<p class="text-muted small mt-2">Łącznie: <?= $result['total'] ?> wpłat</p>
