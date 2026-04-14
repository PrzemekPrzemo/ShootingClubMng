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
            <?php
                $preselMember = null;
                if (!empty($payment['member_id'])) {
                    foreach ($members as $m) {
                        if ($m['id'] == $payment['member_id']) { $preselMember = $m; break; }
                    }
                } elseif (!empty($preselected)) {
                    $preselMember = $preselected;
                }
            ?>
            <input type="hidden" name="member_id" id="memberIdHidden"
                   value="<?= e($preselMember['id'] ?? '') ?>" required>
            <div class="mb-3">
                <label class="form-label">Zawodnik <span class="text-danger">*</span></label>

                <?php if ($preselMember): ?>
                <div id="memberBadge" class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary fs-6 fw-normal px-3 py-2">
                        <i class="bi bi-person-fill me-1"></i>
                        <?= e($preselMember['last_name'] . ' ' . $preselMember['first_name']) ?>
                        [<?= e($preselMember['member_number']) ?>]
                    </span>
                    <?php if ($mode === 'create'): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearMemberBtn" title="Zmień zawodnika">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div id="memberSearchArea" style="display:none">
                <?php else: ?>
                <div id="memberBadge" class="d-flex align-items-center gap-2 mb-2" style="display:none!important"></div>
                <div id="memberSearchArea">
                <?php endif; ?>
                    <input type="text" id="memberSearchInput" class="form-control"
                           placeholder="Wpisz nazwisko (min. 3 litery) lub cyfry PESEL…"
                           autocomplete="off">
                    <div id="memberSearchResults" class="list-group mt-1" style="display:none; max-height:220px; overflow-y:auto; position:relative; z-index:10;"></div>
                    <div class="form-text text-muted">Wyszukaj zawodnika po nazwisku lub numerze PESEL.</div>
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
    // ── Member live-search ────────────────────────────────────────────────────
    const memberIdHidden   = document.getElementById('memberIdHidden');
    const memberSearchInput= document.getElementById('memberSearchInput');
    const memberSearchRes  = document.getElementById('memberSearchResults');
    const memberBadge      = document.getElementById('memberBadge');
    const memberSearchArea = document.getElementById('memberSearchArea');
    const clearMemberBtn   = document.getElementById('clearMemberBtn');

    let selectedClassId = '<?= e($preselMember['member_class_id'] ?? '') ?>';
    let searchTimer = null;

    function showBadge(id, label, classId) {
        memberIdHidden.value = id;
        selectedClassId = classId || '';
        memberBadge.style.removeProperty('display');
        memberBadge.innerHTML =
            '<span class="badge bg-primary fs-6 fw-normal px-3 py-2">'
          + '<i class="bi bi-person-fill me-1"></i>' + label + '</span>'
          + '<button type="button" class="btn btn-sm btn-outline-secondary" id="clearMemberBtn2" title="Zmień zawodnika">'
          + '<i class="bi bi-x-lg"></i></button>';
        document.getElementById('clearMemberBtn2').addEventListener('click', clearMember);
        memberSearchArea.style.display = 'none';
        memberSearchRes.style.display  = 'none';
        memberSearchInput.value = '';
        fetchSuggested();
    }

    function clearMember() {
        memberIdHidden.value = '';
        selectedClassId = '';
        memberBadge.style.display = 'none';
        memberSearchArea.style.display = '';
        memberSearchInput.focus();
        hint.style.display = 'none';
    }

    if (clearMemberBtn) {
        clearMemberBtn.addEventListener('click', clearMember);
    }

    if (memberSearchInput) {
        memberSearchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const q = this.value.trim();
            if (q.length < 2) {
                memberSearchRes.style.display = 'none';
                return;
            }
            searchTimer = setTimeout(function () {
                fetch('<?= url('finances/member-search') ?>?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        memberSearchRes.innerHTML = '';
                        if (!data.results || data.results.length === 0) {
                            memberSearchRes.innerHTML = '<div class="list-group-item text-muted small">Brak wyników</div>';
                            memberSearchRes.style.display = '';
                            return;
                        }
                        data.results.forEach(function (m) {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = '<strong>' + m.full_name + '</strong>'
                                           + ' <span class="text-muted small">[' + m.member_number + ']</span>';
                            item.addEventListener('click', function () {
                                showBadge(m.id,
                                    m.full_name + ' [' + m.member_number + ']',
                                    m.member_class_id);
                            });
                            memberSearchRes.appendChild(item);
                        });
                        memberSearchRes.style.display = '';
                    })
                    .catch(() => { memberSearchRes.style.display = 'none'; });
            }, 250);
        });

        // Close dropdown on outside click
        document.addEventListener('click', function (e) {
            if (!memberSearchArea.contains(e.target)) {
                memberSearchRes.style.display = 'none';
            }
        });
    }

    // ── Suggested amount ─────────────────────────────────────────────────────
    const typeSel    = document.getElementById('paymentTypeSelect');
    const yearField  = document.getElementById('periodYear');
    const amountFld  = document.getElementById('amountField');
    const hint       = document.getElementById('suggestedAmountHint');
    const hintVal    = document.getElementById('suggestedAmountVal');
    const applyBtn   = document.getElementById('applySuggested');

    let lastSuggested = null;

    function fetchSuggested() {
        const typeId = typeSel.value;
        const year   = yearField.value || '<?= date('Y') ?>';

        if (!typeId || !memberIdHidden.value) { hint.style.display = 'none'; return; }

        const url = '<?= url('api/fee-rate') ?>?type_id=' + encodeURIComponent(typeId)
                  + '&class_id=' + encodeURIComponent(selectedClassId)
                  + '&year='    + encodeURIComponent(year);

        fetch(url)
            .then(r => r.json())
            .then(data => {
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
    yearField.addEventListener('change', fetchSuggested);

    // Trigger on load if editing (member already selected)
    if (typeSel.value && memberIdHidden.value) fetchSuggested();
})();
</script>
