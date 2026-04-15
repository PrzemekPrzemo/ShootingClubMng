<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('judges') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('judges/create') : url('judges/' . $license['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Zawodnik <span class="text-danger">*</span></label>
                <?php
                    $preselId   = $license['member_id'] ?? '';
                    $preselName = '';
                    if ($preselId) {
                        foreach ($members as $m) {
                            if ($m['id'] == $preselId) {
                                $preselName = $m['full_name'] . ' [' . $m['member_number'] . ']';
                                break;
                            }
                        }
                    }
                ?>
                <input type="hidden" name="member_id" id="memberIdInput" value="<?= e($preselId) ?>">
                <div class="position-relative">
                    <input type="text" id="memberSearchInput" class="form-control"
                           placeholder="Wpisz min. 3 litery nazwiska lub PESEL..."
                           value="<?= e($preselName) ?>"
                           autocomplete="off" required>
                    <div id="memberSearchResults" class="list-group position-absolute w-100 shadow"
                         style="display:none; top:100%; left:0; right:0; z-index:1050; max-height:250px; overflow-y:auto;"></div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Klasa sędziowska <span class="text-danger">*</span></label>
                    <select name="judge_class" class="form-select" required>
                        <?php foreach (['III' => 'III (podstawowa)', 'II' => 'II', 'I' => 'I', 'P' => 'P (państwowy)'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($license['judge_class'] ?? 'III') === $val ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dyscyplina</label>
                    <select name="discipline_id" class="form-select">
                        <option value="">— wszystkie —</option>
                        <?php foreach ($disciplines as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($license['discipline_id'] ?? null) == $d['id'] ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Numer licencji</label>
                <input type="text" name="license_number" class="form-control"
                       value="<?= e($license['license_number'] ?? '') ?>"
                       placeholder="np. PomZSS/S/2024/001">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data wystawienia <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" class="form-control"
                           value="<?= e($license['issue_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ważna do <span class="text-danger">*</span></label>
                    <input type="date" name="valid_until" id="judgeValidUntil" class="form-control"
                           value="<?= e($license['valid_until'] ?? '') ?>" required>
                    <div id="judgeValidUntilWarning" class="alert alert-warning py-2 px-2 mt-2 small"
                         style="display:none">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Uwaga:</strong> ta data jest już <strong>przeszła</strong> — licencja sędziowska jest wygasła.
                        Możesz zapisać, ale zawodnik nie będzie widoczny jako aktywny sędzia.
                    </div>
                </div>
            </div>
            <script>
            (function () {
                var el = document.getElementById('judgeValidUntil');
                var w  = document.getElementById('judgeValidUntilWarning');
                if (!el || !w) return;
                function check() {
                    if (!el.value) { w.style.display = 'none'; return; }
                    var d = new Date(el.value + 'T00:00:00');
                    var t = new Date(); t.setHours(0,0,0,0);
                    w.style.display = d < t ? '' : 'none';
                }
                el.addEventListener('change', check);
                el.addEventListener('input', check);
                check();
            })();
            </script>

            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($license['notes'] ?? '') ?></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Dodaj licencję' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('judges') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
(function () {
    var memberIdInput = document.getElementById('memberIdInput');
    var searchInput   = document.getElementById('memberSearchInput');
    var resultsBox    = document.getElementById('memberSearchResults');
    var searchUrl     = <?= json_encode(url('api/member-search')) ?>;
    var searchTimer   = null;

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
                        memberIdInput.value = m.id;
                        searchInput.value   = m.full_name + ' [' + m.member_number + ']';
                        resultsBox.style.display = 'none';
                    });
                    resultsBox.appendChild(item);
                });
                resultsBox.style.display = '';
            })
            .catch(function() { resultsBox.style.display = 'none'; });
    }

    searchInput.addEventListener('input', function() {
        memberIdInput.value = '';
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
})();
</script>
