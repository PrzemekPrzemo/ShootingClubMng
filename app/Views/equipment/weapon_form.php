<?php
$typeLabels = ['karabin'=>'Karabin','pistolet'=>'Pistolet','strzelba'=>'Strzelba','inne'=>'Inne'];
$conditionLabels = ['dobry'=>'Dobry','wymaga_obslugi'=>'Wymaga obsługi','uszkodzona'=>'Uszkodzona','wycofana'=>'Wycofana'];
$conditionColors = ['dobry'=>'success','wymaga_obslugi'=>'warning','uszkodzona'=>'danger','wycofana'=>'secondary'];
$isEdit = !empty($weapon['id']);
$action = $isEdit
    ? url('equipment/' . $weapon['id'] . '/edit')
    : url('equipment/weapons/create');
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('equipment') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= $isEdit ? 'Edytuj broń' : 'Dodaj broń' ?></h2>
    <?php if ($isEdit): ?>
        <span class="badge bg-<?= $conditionColors[$weapon['condition']] ?? 'secondary' ?> ms-2">
            <?= $conditionLabels[$weapon['condition']] ?? $weapon['condition'] ?>
        </span>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <!-- Main form -->
        <div class="card mb-3">
            <div class="card-header"><strong>Dane broni</strong></div>
            <div class="card-body">
                <form method="post" action="<?= $action ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= e($weapon['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Typ</label>
                            <select name="type" class="form-select">
                                <?php foreach ($typeLabels as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($weapon['type'] ?? 'inne') === $val ? 'selected' : '' ?>>
                                    <?= $lbl ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kaliber</label>
                            <input type="text" name="caliber" class="form-control"
                                   value="<?= e($weapon['caliber'] ?? '') ?>" placeholder="np. 9mm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Numer seryjny</label>
                            <input type="text" name="serial_number" class="form-control"
                                   value="<?= e($weapon['serial_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Producent</label>
                            <input type="text" name="manufacturer" class="form-control"
                                   value="<?= e($weapon['manufacturer'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data zakupu</label>
                            <input type="date" name="purchase_date" class="form-control"
                                   value="<?= e($weapon['purchase_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stan techniczny</label>
                            <select name="condition" class="form-select">
                                <?php foreach ($conditionLabels as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($weapon['condition'] ?? 'dobry') === $val ? 'selected' : '' ?>>
                                    <?= $lbl ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="is_active" value="1"
                                       <?= ($weapon['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Aktywna (w ewidencji)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"><?= e($weapon['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Zapisz
                        </button>
                        <a href="<?= url('equipment') ?>" class="btn btn-outline-secondary">Anuluj</a>
                        <?php if ($isEdit): ?>
                        <form method="post" action="<?= url('equipment/' . $weapon['id'] . '/delete') ?>"
                              class="ms-auto" onsubmit="return confirm('Wycofać broń z ewidencji?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-archive"></i> Wycofaj
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (!$isEdit): ?>
    <!-- ── Dodaj broń zawodnika ──────────────────────────────────── -->
    <div class="col-md-5">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <strong><i class="bi bi-person-bounding-box"></i> Dodaj broń zawodnika</strong>
                <div class="small fw-normal opacity-75 mt-1">Wyszukaj zawodnika po PESEL, a broń pojawi się w jego portalu.</div>
            </div>
            <div class="card-body">

                <!-- PESEL search -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">PESEL zawodnika</label>
                    <div class="input-group">
                        <input type="text" id="peselInput" class="form-control"
                               placeholder="Wpisz PESEL…" maxlength="11" inputmode="numeric"
                               autocomplete="off">
                        <button type="button" class="btn btn-outline-primary" id="peselSearchBtn">
                            <i class="bi bi-search"></i> Szukaj
                        </button>
                    </div>
                </div>

                <!-- Member result -->
                <div id="memberResult" class="mb-3" style="display:none">
                    <div class="alert alert-success py-2 d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-person-check fs-5"></i>
                        <div>
                            <strong id="memberName"></strong>
                            <span class="text-muted small ms-1" id="memberNumber"></span>
                            <span id="memberStatusBadge" class="badge ms-1"></span>
                        </div>
                    </div>
                </div>
                <div id="memberError" class="alert alert-warning py-2 mb-3" style="display:none"></div>

                <!-- Weapon form — shown after member found -->
                <form method="post" action="<?= url('equipment/member-weapons') ?>" id="memberWeaponForm" style="display:none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="member_id" id="memberIdInput" value="">

                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label">Nazwa / model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mw_name" required
                                   placeholder="np. Walther P99, CZ 75">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Typ</label>
                            <select class="form-select" name="mw_type">
                                <?php foreach (\App\Models\MemberWeaponModel::$TYPES as $k => $v): ?>
                                <option value="<?= e($k) ?>"><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kaliber</label>
                            <input type="text" class="form-control" name="mw_caliber"
                                   placeholder="np. 9mm, .22 LR">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Producent</label>
                            <input type="text" class="form-control" name="mw_manufacturer"
                                   placeholder="np. Glock, CZ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Numer seryjny</label>
                            <input type="text" class="form-control" name="mw_serial_number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nr pozwolenia</label>
                            <input type="text" class="form-control" name="mw_permit_number"
                                   id="mwPermitNumber" placeholder="Nr decyzji">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <input type="text" class="form-control" name="mw_notes"
                                   placeholder="Opcjonalnie">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Dodaj broń zawodnika
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isEdit): ?>
    <div class="col-md-5">
        <!-- Current assignment -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Przypisanie</strong>
            </div>
            <div class="card-body">
                <?php if (!empty($assignment)): ?>
                    <p class="mb-2">
                        <i class="bi bi-person-fill text-primary me-1"></i>
                        <strong><?= e($assignment['last_name']) ?> <?= e($assignment['first_name']) ?></strong>
                        <span class="text-muted small ms-1">(<?= e($assignment['member_number']) ?>)</span>
                    </p>
                    <p class="text-muted small mb-2">Od: <?= format_date($assignment['assigned_date']) ?></p>
                    <?php if ($assignment['notes']): ?>
                        <p class="small text-muted"><?= e($assignment['notes']) ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= url('equipment/assignments/' . $assignment['id'] . '/return') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <label class="form-label form-label-sm mb-1">Data zwrotu</label>
                                <input type="date" name="returned_date" class="form-control form-control-sm"
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-arrow-return-left"></i> Zwrot
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted small mb-3">Broń nie jest aktualnie przypisana.</p>
                <?php endif; ?>

                <!-- Assign form -->
                <?php if (!empty($members)): ?>
                <hr>
                <form method="post" action="<?= url('equipment/' . $weapon['id'] . '/assign') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-12">
                            <select name="member_id" class="form-select form-select-sm" required>
                                <option value="">Przypisz do zawodnika…</option>
                                <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['last_name']) ?> <?= e($m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="date" name="assigned_date" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-person-plus"></i> Przypisz
                            </button>
                        </div>
                        <div class="col-12">
                            <input type="text" name="notes" class="form-control form-control-sm"
                                   placeholder="Uwagi (opcjonalnie)">
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignment history -->
        <?php if (!empty($history)): ?>
        <div class="card">
            <div class="card-header"><strong>Historia przypisań</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Zawodnik</th>
                            <th>Od</th>
                            <th>Do</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="small"><?= e($h['last_name']) ?> <?= e($h['first_name']) ?></td>
                            <td class="small"><?= format_date($h['assigned_date']) ?></td>
                            <td class="small <?= $h['returned_date'] ? 'text-muted' : 'text-success fw-bold' ?>">
                                <?= $h['returned_date'] ? format_date($h['returned_date']) : 'aktualnie' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!$isEdit): ?>
<script>
(function () {
    var peselInput   = document.getElementById('peselInput');
    var searchBtn    = document.getElementById('peselSearchBtn');
    var memberResult = document.getElementById('memberResult');
    var memberError  = document.getElementById('memberError');
    var memberForm   = document.getElementById('memberWeaponForm');
    var memberIdIn   = document.getElementById('memberIdInput');
    var memberName   = document.getElementById('memberName');
    var memberNumber = document.getElementById('memberNumber');
    var memberStatus = document.getElementById('memberStatusBadge');
    var permitField  = document.getElementById('mwPermitNumber');
    var searchUrl    = <?= json_encode(url('equipment/member-search')) ?>;

    function reset() {
        memberResult.style.display = 'none';
        memberError.style.display  = 'none';
        memberForm.style.display   = 'none';
        memberIdIn.value = '';
    }

    function doSearch() {
        var pesel = peselInput.value.trim();
        if (pesel.length < 5) return;
        reset();
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(searchUrl + '?pesel=' + encodeURIComponent(pesel))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<i class="bi bi-search"></i> Szukaj';
                if (data.error || !data.member) {
                    memberError.textContent = data.error || 'Nie znaleziono zawodnika.';
                    memberError.style.display = '';
                    return;
                }
                var m = data.member;
                memberIdIn.value = m.id;
                memberName.textContent   = m.full_name;
                memberNumber.textContent = '[' + m.member_number + ']';
                if (m.status !== 'aktywny') {
                    memberStatus.textContent = m.status;
                    memberStatus.className   = 'badge bg-warning ms-1';
                } else {
                    memberStatus.textContent = '';
                }
                if (m.permit_number && permitField) {
                    permitField.value = m.permit_number;
                }
                memberResult.style.display = '';
                memberForm.style.display   = '';
            })
            .catch(function() {
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<i class="bi bi-search"></i> Szukaj';
                memberError.textContent = 'Błąd połączenia. Spróbuj ponownie.';
                memberError.style.display = '';
            });
    }

    searchBtn.addEventListener('click', doSearch);
    peselInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
    });
})();
</script>
<?php endif; ?>
