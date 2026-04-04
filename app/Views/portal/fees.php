<h2 class="h4 mb-4"><i class="bi bi-cash"></i> Opłaty i składki</h2>

<div class="d-flex gap-2 mb-3">
    <?php foreach (range(date('Y') - 1, date('Y') + 1) as $y): ?>
        <a href="<?= url('portal/fees?year=' . $y) ?>"
           class="btn btn-sm <?= $year === $y ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= $y ?></a>
    <?php endforeach; ?>
</div>

<?php if ($payments): ?>
<div class="card mb-3">
    <div class="card-header"><strong>Wpłaty w <?= $year ?> roku</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Typ</th>
                    <th>Kwota</th>
                    <th>Data</th>
                    <th>Uwagi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['type_name'] ?? '—') ?></td>
                    <td><strong><?= format_money($p['amount']) ?></strong></td>
                    <td class="small"><?= format_date($p['payment_date']) ?></td>
                    <td class="small text-muted"><?= e($p['notes'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <th>Suma</th>
                    <th><?= format_money(array_sum(array_column($payments, 'amount'))) ?></th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Brak wpłat w <?= $year ?> roku.</div>
<?php endif; ?>

<div class="alert alert-secondary small">
    <i class="bi bi-info-circle"></i>
    W razie pytań dotyczących opłat prosimy o kontakt z biurem klubu.
</div>
