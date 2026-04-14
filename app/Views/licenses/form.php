<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('licenses') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('licenses/create') : url('licenses/' . $license['id'] . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Zawodnik <span class="text-danger">*</span></label>
                <?php
                    $preselId   = $license['member_id'] ?? $preselected['id'] ?? '';
                    $preselName = '';
                    $preselBirth = '';
                    if ($preselId) {
                        foreach ($members as $m) {
                            if ($m['id'] == $preselId) {
                                $preselName = $m['full_name'] . ' [' . $m['member_number'] . ']';
                                break;
                            }
                        }
                        if (!empty($preselected['birth_date'])) {
                            $preselBirth = $preselected['birth_date'];
                        }
                    }
                ?>
                <input type="hidden" name="member_id" id="memberIdInput" value="<?= e($preselId) ?>">
                <input type="hidden" id="memberBirthDate" value="<?= e($preselBirth) ?>">
                <div class="position-relative">
                    <input type="text" id="memberSearchInput" class="form-control"
                           placeholder="Wpisz min. 3 litery nazwiska lub PESEL..."
                           value="<?= e($preselName) ?>"
                           autocomplete="off" required>
                    <div id="memberSearchResults" class="list-group position-absolute w-100 shadow"
                         style="display:none; z-index:1050; max-height:250px; overflow-y:auto;"></div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Typ licencji <span class="text-danger">*</span></label>
                    <select name="license_type_id" id="licenseTypeSelect" class="form-select" required>
                        <option value="">— wybierz —</option>
                        <?php foreach ($licenseTypes ?? [] as $lt): ?>
                            <?php $sel = isset($license['license_type_id'])
                                ? $license['license_type_id'] == $lt['id']
                                : ($license['license_type'] ?? '') === $lt['short_code']; ?>
                            <option value="<?= $lt['id'] ?>"
                                    data-months="<?= e($lt['validity_months'] ?? '') ?>"
                                    data-no-expiry="<?= $lt['validity_months'] === null ? '1' : '0' ?>"
                                    <?= $sel ? 'selected' : '' ?>>
                                <?= e($lt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <a href="<?= url('config/license-types') ?>" class="small">
                            <i class="bi bi-gear"></i> Zarządzaj typami
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Numer licencji <span class="text-danger">*</span></label>
                    <input type="text" name="license_number" class="form-control"
                           value="<?= e($license['license_number'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Dyscypliny</label>
                <?php $selDisciplines = $selectedDisciplines ?? []; ?>
                <div class="row g-1">
                    <?php foreach ($disciplines as $d): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="discipline_ids[]"
                                   value="<?= (int)$d['id'] ?>"
                                   id="disc_<?= (int)$d['id'] ?>"
                                   <?= in_array((int)$d['id'], array_map('intval', $selDisciplines)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="disc_<?= (int)$d['id'] ?>">
                                <?= e($d['name']) ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text">Można wybrać więcej niż jedną dyscyplinę.</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data wydania <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" id="issueDate" class="form-control"
                           value="<?= e($license['issue_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6" id="validUntilWrap">
                    <label class="form-label" id="validUntilLabel">Ważna do <span class="text-danger">*</span></label>
                    <input type="date" name="valid_until" id="validUntil" class="form-control"
                           value="<?= e($license['valid_until'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Link/QR PZSS 2026</label>
                <input type="url" name="pzss_qr_code" class="form-control" placeholder="https://system.pzss.pl/…"
                       value="<?= e($license['pzss_qr_code'] ?? '') ?>">
                <div class="form-text">URL do weryfikacji licencji w systemie PZSS.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['aktywna','wygasla','zawieszona'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($license['status'] ?? 'aktywna') === $s ? 'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($license['notes'] ?? '') ?></textarea>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Dodaj licencję' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('licenses') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
<script>
(function () {
    var memberIdInput  = document.getElementById('memberIdInput');
    var memberBirthEl  = document.getElementById('memberBirthDate');
    var searchInput    = document.getElementById('memberSearchInput');
    var resultsBox     = document.getElementById('memberSearchResults');
    var validUntil     = document.getElementById('validUntil');
    var isCreate       = <?= $mode === 'create' ? 'true' : 'false' ?>;
    var searchUrl      = <?= json_encode(url('api/member-search')) ?>;
    var searchTimer    = null;

    function calcValidUntil(birthDate) {
        if (!isCreate || !validUntil) return;
        var now = new Date();
        var year = now.getFullYear();

        if (birthDate) {
            var bd = new Date(birthDate);
            var age = year - bd.getFullYear();
            if (now < new Date(bd.getFullYear() + age, bd.getMonth(), bd.getDate())) {
                age--;
            }
            if (age < 18) {
                year = bd.getFullYear() + 18;
            }
        }

        validUntil.value = year + '-12-31';
    }

    function selectMember(m) {
        memberIdInput.value = m.id;
        memberBirthEl.value = m.birth_date || '';
        searchInput.value   = m.full_name + ' [' + m.member_number + ']';
        resultsBox.style.display = 'none';
        calcValidUntil(m.birth_date);
    }

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

    searchInput.addEventListener('input', function() {
        memberIdInput.value = '';
        memberBirthEl.value = '';
        clearTimeout(searchTimer);
        searchTimer = setTimeout(doSearch, 300);
    });
    searchInput.addEventListener('blur', function() {
        setTimeout(function() { resultsBox.style.display = 'none'; }, 200);
    });
    searchInput.addEventListener('focus', function() {
        if (searchInput.value.trim().length >= 3 && !memberIdInput.value) doSearch();
    });
    searchInput.closest('form').addEventListener('submit', function(e) {
        if (!memberIdInput.value) {
            e.preventDefault();
            searchInput.focus();
            searchInput.classList.add('is-invalid');
        }
    });
    searchInput.addEventListener('input', function() { searchInput.classList.remove('is-invalid'); });

    // Auto-calculate on load if member is preselected in create mode
    if (isCreate && memberIdInput.value && memberBirthEl.value && !validUntil.value) {
        calcValidUntil(memberBirthEl.value);
    }
})();
</script>

<script>
(function () {
    var typeSelect      = document.getElementById('licenseTypeSelect');
    var issueDate       = document.getElementById('issueDate');
    var validUntil      = document.getElementById('validUntil');
    var validUntilWrap  = document.getElementById('validUntilWrap');
    var validUntilLabel = document.getElementById('validUntilLabel');

    function recalc() {
        var opt      = typeSelect.options[typeSelect.selectedIndex];
        var noExpiry = opt && opt.dataset.noExpiry === '1';
        var months   = parseInt(opt ? opt.dataset.months : '', 10);

        if (noExpiry) {
            validUntilWrap.style.display = 'none';
            validUntil.removeAttribute('required');
            validUntil.value = '';
        } else {
            validUntilWrap.style.display = '';
            validUntil.setAttribute('required', 'required');
            if (months && issueDate.value) {
                var d = new Date(issueDate.value);
                d.setMonth(d.getMonth() + months);
                d.setDate(d.getDate() - 1);
                validUntil.value = d.toISOString().slice(0, 10);
            }
        }
    }

    typeSelect.addEventListener('change', recalc);
    issueDate.addEventListener('change', function () {
        if (typeSelect.value) recalc();
    });

    // Run on load in case patent is pre-selected (edit mode)
    if (typeSelect.value) recalc();
})();
</script>
    </div>
</div>
</div>
</div>
