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
                <select name="member_id" id="memberSelect" class="form-select" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($members as $m): ?>
                        <?php $sel = ($payment['member_id'] ?? $preselected['id'] ?? '') == $m['id']; ?>
                        <option value="<?= $m['id'] ?>"
                                data-class-id="<?= e($m['member_class_id'] ?? '') ?>"
                                <?= $sel ? 'selected':'' ?>>
                            <?= e($m['full_name']) ?> [<?= e($m['member_number']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Typ opłaty <span class="text-danger">*</span></label>
                <select name="payment_type_id" class="form-select" id="paymentTypeSelect" required>
                    <option value="">— wybierz —</option>
                    <?php
                    $lastCat = null;
                    $catLabels = ['skladka' => 'Składki członkowskie', 'pzss' => 'PZSS', 'pomzss' => 'PomZSS', 'inne' => 'Inne'];
                    foreach ($paymentTypes as $pt):
                        if (($pt['category'] ?? 'inne') !== $lastCat):
                            $lastCat = $pt['category'] ?? 'inne';
                            if ($lastCat !== 'inne' || $pt === reset($paymentTypes)):
                    ?>
                        <optgroup label="<?= e($catLabels[$lastCat] ?? $lastCat) ?>">
                    <?php
                            endif;
                        endif;
                    ?>
                        <option value="<?= $pt['id'] ?>"
                                data-amount="<?= $pt['amount'] ?>"
                                <?= ($payment['payment_type_id'] ?? '') == $pt['id'] ? 'selected':'' ?>>
                            <?= e($pt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sugerowana kwota -->
            <div class="mb-3">
                <label class="form-label">Kwota (zł) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="amount" id="amountField" step="0.01" min="0.01" class="form-control"
                           value="<?= e($payment['amount'] ?? '') ?>" required>
                    <span class="input-group-text">PLN</span>
                </div>
                <div id="suggestedAmountHint" class="form-text text-success" style="display:none">
                    <i class="bi bi-lightbulb"></i>
                    Sugerowana kwota: <strong id="suggestedAmountVal"></strong>
                    <a href="#" id="applySuggested" class="ms-2">Zastosuj</a>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Metoda płatności</label>
                    <select name="method" class="form-select">
                        <?php foreach (['gotówka','przelew','karta','inny'] as $mth): ?>
                            <option value="<?= $mth ?>" <?= ($payment['method'] ?? 'gotówka') === $mth ? 'selected':'' ?>><?= $mth ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data wpłaty <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="<?= e($payment['payment_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Rok okresu <span class="text-danger">*</span></label>
                    <input type="number" name="period_year" id="periodYear" class="form-control" min="2000" max="2100"
                           value="<?= e($payment['period_year'] ?? date('Y')) ?>" required>
                </div>
                <div class="col-md-6">
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
(function () {
    const memberSel  = document.getElementById('memberSelect');
    const typeSel    = document.getElementById('paymentTypeSelect');
    const yearField  = document.getElementById('periodYear');
    const amountFld  = document.getElementById('amountField');
    const hint       = document.getElementById('suggestedAmountHint');
    const hintVal    = document.getElementById('suggestedAmountVal');
    const applyBtn   = document.getElementById('applySuggested');

    let lastSuggested = null;

    function fetchSuggested() {
        const typeId  = typeSel.value;
        const year    = yearField.value || '<?= date('Y') ?>';

        if (!typeId) { hint.style.display = 'none'; return; }

        // Get member_class_id from selected member option
        const memberOpt = memberSel.options[memberSel.selectedIndex];
        const classId   = memberOpt ? (memberOpt.dataset.classId || '') : '';

        const url = '<?= url('api/fee-rate') ?>?type_id=' + encodeURIComponent(typeId)
                  + '&class_id=' + encodeURIComponent(classId)
                  + '&year='    + encodeURIComponent(year);

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.amount > 0) {
                    lastSuggested = parseFloat(data.amount).toFixed(2);
                    hintVal.textContent = parseFloat(data.amount).toFixed(2).replace('.', ',') + ' PLN';
                    hint.style.display = '';

                    // Auto-fill if amount field is empty
                    if (!amountFld.value || amountFld.value === '0') {
                        amountFld.value = lastSuggested;
                        hint.style.display = 'none';
                    }
                } else {
                    hint.style.display = 'none';
                }
            })
            .catch(() => hint.style.display = 'none');
    }

    applyBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (lastSuggested) {
            amountFld.value = lastSuggested;
            hint.style.display = 'none';
        }
    });

    typeSel.addEventListener('change',  fetchSuggested);
    memberSel.addEventListener('change', fetchSuggested);
    yearField.addEventListener('change', fetchSuggested);

    // Trigger on load if editing
    if (typeSel.value) fetchSuggested();
})();
</script>
