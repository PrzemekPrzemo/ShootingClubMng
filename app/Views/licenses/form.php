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
                <select name="member_id" class="form-select" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($members as $m): ?>
                        <?php $sel = ($license['member_id'] ?? $preselected['id'] ?? '') == $m['id']; ?>
                        <option value="<?= $m['id'] ?>" <?= $sel ? 'selected':'' ?>>
                            <?= e($m['full_name']) ?> [<?= e($m['member_number']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
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
