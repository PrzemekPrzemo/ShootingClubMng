<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('finances') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('finances/create') : url('finances/' . $payment['id'] . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Zawodnik <span class="text-danger">*</span></label>
                <select name="member_id" class="form-select" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($members as $m): ?>
                        <?php $sel = ($payment['member_id'] ?? $preselected['id'] ?? '') == $m['id']; ?>
                        <option value="<?= $m['id'] ?>" <?= $sel ? 'selected':'' ?>>
                            <?= e($m['full_name']) ?> [<?= e($m['member_number']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Typ opłaty <span class="text-danger">*</span></label>
                <select name="payment_type_id" class="form-select" id="paymentTypeSelect" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($paymentTypes as $pt): ?>
                        <option value="<?= $pt['id'] ?>"
                                data-amount="<?= $pt['amount'] ?>"
                                <?= ($payment['payment_type_id'] ?? '') == $pt['id'] ? 'selected':'' ?>>
                            <?= e($pt['name']) ?> (<?= format_money($pt['amount']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Kwota (zł) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="amountField" step="0.01" min="0.01" class="form-control"
                           value="<?= e($payment['amount'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Metoda płatności</label>
                    <select name="method" class="form-select">
                        <?php foreach (['gotówka','przelew','karta','inny'] as $m): ?>
                            <option value="<?= $m ?>" <?= ($payment['method'] ?? 'gotówka') === $m ? 'selected':'' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data wpłaty <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="<?= e($payment['payment_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rok okresu <span class="text-danger">*</span></label>
                    <input type="number" name="period_year" class="form-control" min="2000" max="2100"
                           value="<?= e($payment['period_year'] ?? date('Y')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Miesiąc (opcja)</label>
                    <select name="period_month" class="form-select">
                        <option value="">— roczna —</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= ($payment['period_month'] ?? '') == $i ? 'selected':'' ?>>
                                <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Nr referencyjny / pokwitowania</label>
                <input type="text" name="reference" class="form-control"
                       value="<?= e($payment['reference'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($payment['notes'] ?? '') ?></textarea>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Zarejestruj wpłatę' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('finances') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
// Auto-fill amount from payment type
document.getElementById('paymentTypeSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const amount = opt.dataset.amount;
    if (amount && amount > 0) {
        document.getElementById('amountField').value = parseFloat(amount).toFixed(2);
    }
});
</script>
