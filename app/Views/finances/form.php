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
                <?php
                    $preselId   = $payment['member_id'] ?? $preselected['id'] ?? '';
                    $preselName = '';
                    $preselClassId = '';
                    if ($preselId) {
                        foreach ($members as $m) {
                            if ($m['id'] == $preselId) {
                                $preselName = $m['full_name'] . ' [' . $m['member_number'] . ']';
                                $preselClassId = $m['member_class_id'] ?? '';
                                break;
                            }
                        }
                    }
                ?>
                <input type="hidden" name="member_id" id="memberIdInput" value="<?= e($preselId) ?>">
                <input type="hidden" id="memberClassId" value="<?= e($preselClassId) ?>">
                <div class="position-relative">
                    <input type="text" id="memberSearchInput" class="form-control"
                           placeholder="Wpisz min. 3 litery nazwiska lub PESEL..."
                           value="<?= e($preselName) ?>"
                           autocomplete="off" required>
                    <div id="memberSearchResults" class="list-group position-absolute w-100 shadow"
                         style="display:none; top:100%; left:0; right:0; z-index:1050; max-height:250px; overflow-y:auto;"></div>
                </div>
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
    var memberIdInput   = document.getElementById('memberIdInput');
    var memberClassId   = document.getElementById('memberClassId');
    var searchInput     = document.getElementById('memberSearchInput');
    var resultsBox      = document.getElementById('memberSearchResults');
    var typeSel         = document.getElementById('paymentTypeSelect');
    var yearField       = document.getElementById('periodYear');
    var amountFld       = document.getElementById('amountField');
    var hint            = document.getElementById('suggestedAmountHint');
    var hintVal         = document.getElementById('suggestedAmountVal');
    var applyBtn        = document.getElementById('applySuggested');
    var searchUrl       = <?= json_encode(url('api/member-search')) ?>;

    var lastSuggested = null;
    var searchTimer   = null;

    /* ── Member search ──────────────────────────────────── */

    function doSearch() {
        var q = searchInput.value.trim();
        if (q.length < 3) { resultsBox.style.display = 'none'; return; }

        fetch(searchUrl + '?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                resultsBox.innerHTML = '';
                if (!data.members || data.members.length === 0) {
                    resultsBox.innerHTML = '<div class="list-group-item text-muted small">Brak wyników</div>';
                    resultsBox.style.display = '';
                    return;
                }
                data.members.forEach(function(m) {
                    var item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action py-1 px-2 small';
                    item.textContent = m.full_name + ' [' + m.member_number + ']';
                    item.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectMember(m);
                    });
                    resultsBox.appendChild(item);
                });
                resultsBox.style.display = '';
            })
            .catch(function() { resultsBox.style.display = 'none'; });
    }

    function selectMember(m) {
        memberIdInput.value = m.id;
        memberClassId.value = m.member_class_id || '';
        searchInput.value   = m.full_name + ' [' + m.member_number + ']';
        resultsBox.style.display = 'none';
        fetchSuggested();
    }

    searchInput.addEventListener('input', function() {
        memberIdInput.value = '';
        memberClassId.value = '';
        clearTimeout(searchTimer);
        searchTimer = setTimeout(doSearch, 300);
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(function() { resultsBox.style.display = 'none'; }, 200);
    });

    searchInput.addEventListener('focus', function() {
        if (searchInput.value.trim().length >= 3 && !memberIdInput.value) {
            doSearch();
        }
    });

    /* ── Suggested fee amount ───────────────────────────── */

    function fetchSuggested() {
        var typeId  = typeSel.value;
        var year    = yearField.value || '<?= date('Y') ?>';

        if (!typeId) { hint.style.display = 'none'; return; }

        var classId = memberClassId.value || '';
        var url = '<?= url('api/fee-rate') ?>?type_id=' + encodeURIComponent(typeId)
                + '&class_id=' + encodeURIComponent(classId)
                + '&year='    + encodeURIComponent(year);

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.amount > 0) {
                    lastSuggested = parseFloat(data.amount).toFixed(2);
                    hintVal.textContent = parseFloat(data.amount).toFixed(2).replace('.', ',') + ' PLN';
                    hint.style.display = '';
                    if (!amountFld.value || amountFld.value === '0') {
                        amountFld.value = lastSuggested;
                        hint.style.display = 'none';
                    }
                } else {
                    hint.style.display = 'none';
                }
            })
            .catch(function() { hint.style.display = 'none'; });
    }

    applyBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (lastSuggested) {
            amountFld.value = lastSuggested;
            hint.style.display = 'none';
        }
    });

    typeSel.addEventListener('change',  fetchSuggested);
    yearField.addEventListener('change', fetchSuggested);

    // Prevent submit without selecting a member
    searchInput.closest('form').addEventListener('submit', function(e) {
        if (!memberIdInput.value) {
            e.preventDefault();
            searchInput.focus();
            searchInput.classList.add('is-invalid');
        }
    });
    searchInput.addEventListener('input', function() {
        searchInput.classList.remove('is-invalid');
    });

    // Trigger on load if editing
    if (typeSel.value && memberIdInput.value) fetchSuggested();
})();
</script>
