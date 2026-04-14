<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('finances') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Zaległości składkowe <?= $year ?></h2>
    <form method="get" action="<?= url('finances/debts') ?>" class="ms-auto d-flex gap-2">
        <select name="year" class="form-select form-select-sm" style="width:auto">
            <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                <option value="<?= $y ?>" <?= $year == $y ? 'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Pokaż</button>
        <a href="<?= url('reports/finances?type=debts&year=' . $year) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-arrow-down"></i> CSV
        </a>
    </form>
</div>

<div class="alert alert-warning">
    <i class="bi bi-info-circle"></i>
    Lista aktywnych zawodników z zaległością w składce rocznej za <?= $year ?>.
    System uwzględnia <strong>datę wstąpienia</strong> — zawodnik płaci od miesiąca po wstąpieniu (pierwszy miesiąc gratis).
    Przykład: wstąpił w kwietniu ⇒ płaci za 8 miesięcy (maj–grudzień).
</div>

<?php
    $totalOutstanding = array_sum(array_column($debtors, 'outstanding'));
    $totalExpected    = array_sum(array_column($debtors, 'expected'));
    $totalPaid        = array_sum(array_column($debtors, 'paid_total'));
?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body py-2">
                <div class="text-muted small">Łącznie zaległości</div>
                <div class="h4 text-danger mb-0"><?= format_money($totalOutstanding) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-2">
                <div class="text-muted small">Oczekiwane (proporcjonalnie)</div>
                <div class="h5 mb-0"><?= format_money($totalExpected) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-2">
                <div class="text-muted small">Wpłacono do tej pory</div>
                <div class="h5 text-success mb-0"><?= format_money($totalPaid) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nr</th>
                    <th>Zawodnik</th>
                    <th>Wstąpił</th>
                    <th class="text-center">M-ce</th>
                    <th class="text-end">Oczekiwane</th>
                    <th class="text-end">Wpłacono</th>
                    <th class="text-end">Zaległość</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($debtors as $d): ?>
                <tr class="table-warning">
                    <td class="small text-muted"><?= e($d['member_number']) ?></td>
                    <td>
                        <a href="<?= url('members/' . $d['id']) ?>" class="fw-semibold text-decoration-none">
                            <?= e($d['last_name']) ?> <?= e($d['first_name']) ?>
                        </a>
                        <?php if (!empty($d['email'])): ?>
                        <div class="small text-muted"><?= e($d['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= e($d['join_date'] ?? '—') ?></td>
                    <td class="text-center small"><?= (int)($d['months_due'] ?? 0) ?>/12</td>
                    <td class="text-end small"><?= format_money($d['expected'] ?? 0) ?></td>
                    <td class="text-end small"><?= format_money($d['paid_total'] ?? 0) ?></td>
                    <td class="text-end fw-semibold text-danger"><?= format_money($d['outstanding'] ?? 0) ?></td>
                    <td class="text-end">
                        <a href="<?= url('finances/create?member_id=' . $d['id']) ?>" class="btn btn-sm btn-success py-0">
                            <i class="bi bi-plus"></i> Zarejestruj
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($debtors)): ?>
                <tr><td colspan="8" class="text-center text-success py-4">
                    <i class="bi bi-check-circle"></i> Brak zaległości — wszyscy aktywni zawodnicy mają składki opłacone!
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">Zalegających: <?= count($debtors) ?></p>
