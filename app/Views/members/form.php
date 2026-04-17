<div class="d-flex align-items-center mb-3 gap-2">
    <?php $backUrl = url('members' . (!empty($memberPage) && $memberPage > 1 ? '?page=' . $memberPage : '')); ?>
    <a href="<?= $backUrl ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<form method="post" enctype="multipart/form-data" id="memberForm"
      action="<?= $mode === 'create' ? url('members/create') : url('members/' . $member['id'] . '/edit') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="redirect_after" id="redirectAfter" value="">

    <div class="row g-4">
        <!-- Podstawowe dane -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><strong>Dane osobowe</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Imię <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?= e(old('first_name', $member['first_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nazwisko <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control"
                                   value="<?= e(old('last_name', $member['last_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PESEL</label>
                            <input type="text" name="pesel" class="form-control" maxlength="11"
                                   value="<?= e(old('pesel', $member['pesel'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data urodzenia</label>
                            <input type="date" name="birth_date" class="form-control"
                                   value="<?= e(old('birth_date', $member['birth_date'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Płeć</label>
                            <select name="gender" class="form-select">
                                <option value="">—</option>
                                <option value="M" <?= (old('gender', $member['gender'] ?? '')) === 'M' ? 'selected':'' ?>>Mężczyzna</option>
                                <option value="K" <?= (old('gender', $member['gender'] ?? '')) === 'K' ? 'selected':'' ?>>Kobieta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e(old('email', $member['email'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= e(old('phone', $member['phone'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Adres</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Ulica i numer</label>
                            <input type="text" name="address_street" class="form-control"
                                   value="<?= e(old('address_street', $member['address_street'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kod pocztowy</label>
                            <input type="text" name="address_postal" class="form-control" placeholder="00-000"
                                   value="<?= e(old('address_postal', $member['address_postal'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Miejscowość</label>
                            <input type="text" name="address_city" class="form-control"
                                   value="<?= e(old('address_city', $member['address_city'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dyscypliny -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Dyscypliny</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addDisciplineBtn">
                        <i class="bi bi-plus"></i> Dodaj
                    </button>
                </div>
                <div class="card-body" id="disciplinesContainer">
                    <?php
                    $existingDiscs = $memberDiscs ?? [];
                    foreach ($existingDiscs as $i => $md):
                    ?>
                    <div class="row g-2 mb-2 discipline-row">
                        <div class="col-md-4">
                            <select name="discipline_ids[]" class="form-select form-select-sm">
                                <option value="">— wybierz —</option>
                                <?php foreach ($disciplines as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= $d['id'] == $md['discipline_id'] ? 'selected':'' ?>>
                                        <?= e($d['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="discipline_classes[]" class="form-select form-select-sm">
                                <option value="">Klasa</option>
                                <?php foreach ($disciplineClasses as $cls): ?>
                                    <option value="<?= e($cls['name']) ?>" <?= $md['class'] === $cls['name'] ? 'selected':'' ?>><?= e($cls['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="discipline_instructors[]" class="form-select form-select-sm">
                                <option value="">Instruktor</option>
                                <?php foreach ($instructors as $ins): ?>
                                    <option value="<?= $ins['id'] ?>" <?= $md['instructor_id'] == $ins['id'] ? 'selected':'' ?>><?= e($ins['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="discipline_joined[]" class="form-control form-control-sm"
                                   value="<?= e($md['joined_at']) ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-discipline"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($existingDiscs)): ?>
                    <div class="row g-2 mb-2 discipline-row">
                        <div class="col-md-4">
                            <select name="discipline_ids[]" class="form-select form-select-sm">
                                <option value="">— wybierz —</option>
                                <?php foreach ($disciplines as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="discipline_classes[]" class="form-select form-select-sm">
                                <option value="">Klasa</option>
                                <?php foreach ($disciplineClasses as $cls): ?>
                                    <option value="<?= e($cls['name']) ?>"><?= e($cls['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="discipline_instructors[]" class="form-select form-select-sm">
                                <option value="">Instruktor</option>
                                <?php foreach ($instructors as $ins): ?>
                                    <option value="<?= $ins['id'] ?>"><?= e($ins['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="discipline_joined[]" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-discipline"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Firearm permit + notes -->
            <div class="card mb-3">
                <div class="card-header"><strong>Pozwolenie na broń / uwagi</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Numer pozwolenia na broń</label>
                        <input type="text" name="firearm_permit_number" class="form-control"
                               value="<?= e(old('firearm_permit_number', $member['firearm_permit_number'] ?? '')) ?>"
                               placeholder="Nr decyzji administracyjnej">
                        <div class="form-text">Główne pozwolenie wydane dla tego zawodnika.</div>
                    </div>
                    <label class="form-label">Uwagi</label>
                    <textarea name="notes" class="form-control" rows="3"><?= e(old('notes', $member['notes'] ?? '')) ?></textarea>
                </div>
            </div>

            <!-- Dowód osobisty (zaszyfrowane) -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2">
                    <strong>Dowód osobisty</strong>
                    <span class="badge bg-secondary ms-auto"><i class="bi bi-lock-fill"></i> Szyfrowane</span>
                </div>
                <div class="card-body">
                    <div class="form-text mb-3">Dane opcjonalne. Szyfrowane w bazie danych — widoczny tylko pierwszy i ostatni znak po zapisaniu.</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Numer i seria dowodu</label>
                            <input type="text" name="id_card_number" class="form-control"
                                   value="<?= e(old('id_card_number', $member['id_card_number'] ?? '')) ?>"
                                   placeholder="np. ABC 123456"
                                   autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data ważności dowodu</label>
                            <input type="date" name="id_card_expiry" class="form-control"
                                   value="<?= e(old('id_card_expiry', $member['id_card_expiry'] ?? '')) ?>">
                            <div class="form-text">W widoku zawodnika widoczny będzie tylko rok.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Photo upload -->
            <div class="card mb-3">
                <div class="card-header"><strong>Zdjęcie zawodnika</strong></div>
                <div class="card-body">
                    <?php if ($mode === 'edit' && !empty($member['photo_path'])): ?>
                    <div class="mb-2 text-center">
                        <img src="<?= url('members/' . (int)$member['id'] . '/photo') ?>"
                             alt="Aktualne zdjęcie" class="rounded-circle"
                             style="width:80px;height:80px;object-fit:cover">
                        <div class="form-text">Aktualne zdjęcie</div>
                    </div>
                    <?php endif; ?>
                    <label class="form-label">
                        <?= $mode === 'edit' && !empty($member['photo_path']) ? 'Zmień zdjęcie' : 'Dodaj zdjęcie' ?>
                    </label>
                    <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
                    <div class="form-text">JPG lub PNG, maks. 2 MB.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Przynależność do klubu</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Typ członkostwa <span class="text-danger">*</span></label>
                        <select name="member_type" class="form-select">
                            <?php $currentType = old('member_type', $member['member_type'] ?? ''); ?>
                            <?php foreach ($memberTypes as $mt): ?>
                            <option value="<?= e($mt['name']) ?>" <?= $currentType === $mt['name'] ? 'selected' : '' ?>><?= e($mt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategoria wiekowa</label>
                        <select name="age_category_id" class="form-select">
                            <option value="">— auto-detect —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (old('age_category_id', $member['age_category_id'] ?? '')) == $cat['id'] ? 'selected':'' ?>>
                                    <?= e($cat['name']) ?> (<?= $cat['age_from'] ?>–<?= $cat['age_to'] ?> lat)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dodatkowa klasa zawodnika</label>
                        <select name="member_class_id" class="form-select">
                            <option value="">— brak —</option>
                            <?php foreach ($memberClasses ?? [] as $mc): ?>
                                <option value="<?= $mc['id'] ?>"
                                    <?= (old('member_class_id', $member['member_class_id'] ?? '')) == $mc['id'] ? 'selected' : '' ?>>
                                    <?= e($mc['name']) ?> (<?= e($mc['short_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Klasa zdefiniowana w słowniku (<a href="<?= url('config/member-classes') ?>">Konfiguracja</a>)</div>
                    </div>
                    <?php if (in_array($authUser['role'] ?? '', ['admin','zarzad'], true)): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="isBoardLinked"
                                   name="is_board_linked" value="1"
                                   <?= !empty($member['is_board_linked']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isBoardLinked">
                                <strong>Powiązany z zarządem klubu</strong>
                                <span class="text-muted small d-block">
                                    Składka = 50% bazy. Inne zniżki (klasa, osiągnięcia) są pomijane.
                                </span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Numer karty dostępu</label>
                        <input type="text" name="card_number" class="form-control"
                               value="<?= e(old('card_number', $member['card_number'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data wstąpienia <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="form-control"
                               value="<?= e(old('join_date', $member['join_date'] ?? date('Y-m-d'))) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="aktywny"    <?= (old('status', $member['status'] ?? 'aktywny')) === 'aktywny'    ? 'selected':'' ?>>Aktywny</option>
                            <option value="zawieszony" <?= (old('status', $member['status'] ?? '')) === 'zawieszony' ? 'selected':'' ?>>Zawieszony</option>
                            <option value="wykreslony" <?= (old('status', $member['status'] ?? '')) === 'wykreslony' ? 'selected':'' ?>>Wykreślony</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Licencja zawodnicza -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-card-checklist me-1 text-primary"></i>Licencja zawodnicza</strong>
                    <?php if ($mode === 'edit'): ?>
                    <a href="<?= url('licenses/create?member_id=' . (int)$member['id']) ?>"
                       class="btn btn-sm btn-outline-primary py-0 save-first" title="Dodaj licencję zawodniczą">
                        <i class="bi bi-plus"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2">
                    <?php if ($mode === 'create'): ?>
                        <p class="text-muted small mb-0">Dostępne po zapisaniu zawodnika.</p>
                    <?php elseif (!empty($license)): ?>
                        <?php $daysLic = isset($license['valid_until']) ? (int)((strtotime($license['valid_until']) - time()) / 86400) : null; ?>
                        <p class="mb-1 small">Nr: <code><?= e($license['license_number']) ?></code></p>
                        <p class="mb-1 small">
                            Ważna do: <?= e($license['valid_until'] ?? '—') ?>
                            <span class="badge bg-<?= $daysLic === null ? 'secondary' : ($daysLic < 0 ? 'danger' : ($daysLic < 60 ? 'warning' : 'success')) ?>">
                                <?= $daysLic === null ? 'bezterminowa' : ($daysLic >= 0 ? "za {$daysLic} dni" : 'WYGASŁA') ?>
                            </span>
                        </p>
                        <a href="<?= url('licenses/create?member_id=' . (int)$member['id']) ?>" class="small save-first">
                            <i class="bi bi-plus-circle"></i> Dodaj kolejną
                        </a>
                    <?php else: ?>
                        <p class="text-muted small mb-1">Brak licencji zawodniczej.</p>
                        <a href="<?= url('licenses/create?member_id=' . (int)$member['id']) ?>" class="btn btn-sm btn-outline-primary w-100 save-first">
                            <i class="bi bi-plus"></i> Dodaj licencję
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Licencja sędziowska -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-person-badge me-1 text-info"></i>Licencja sędziowska</strong>
                    <?php if ($mode === 'edit'): ?>
                    <a href="<?= url('judges/create?member_id=' . (int)$member['id']) ?>"
                       class="btn btn-sm btn-outline-info py-0 save-first" title="Dodaj licencję sędziowską">
                        <i class="bi bi-plus"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2">
                    <?php if ($mode === 'create'): ?>
                        <p class="text-muted small mb-0">Dostępne po zapisaniu zawodnika.</p>
                    <?php elseif (!empty($judgeLicense)): ?>
                        <?php $daysJl = isset($judgeLicense['valid_until']) ? (int)((strtotime($judgeLicense['valid_until']) - time()) / 86400) : null; ?>
                        <p class="mb-1 small">
                            Klasa: <strong><?= e($judgeLicense['judge_class']) ?></strong>
                            <?php if (!empty($judgeLicense['license_number'])): ?>
                            &nbsp;| Nr: <code><?= e($judgeLicense['license_number']) ?></code>
                            <?php endif; ?>
                        </p>
                        <p class="mb-1 small">
                            Ważna do: <?= e($judgeLicense['valid_until'] ?? '—') ?>
                            <span class="badge bg-<?= $daysJl === null ? 'secondary' : ($daysJl < 0 ? 'danger' : ($daysJl < 60 ? 'warning' : 'success')) ?>">
                                <?= $daysJl === null ? 'bezterminowa' : ($daysJl >= 0 ? "za {$daysJl} dni" : 'WYGASŁA') ?>
                            </span>
                        </p>
                        <a href="<?= url('judges/' . (int)$judgeLicense['id'] . '/edit') ?>" class="small save-first">
                            <i class="bi bi-pencil"></i> Edytuj
                        </a>
                        &nbsp;|&nbsp;
                        <a href="<?= url('judges/create?member_id=' . (int)$member['id']) ?>" class="small save-first">
                            <i class="bi bi-plus-circle"></i> Dodaj kolejną
                        </a>
                    <?php else: ?>
                        <p class="text-muted small mb-1">Brak licencji sędziowskiej.</p>
                        <a href="<?= url('judges/create?member_id=' . (int)$member['id']) ?>" class="btn btn-sm btn-outline-info w-100 save-first">
                            <i class="bi bi-plus"></i> Dodaj licencję sędziowską
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check-lg"></i>
                    <?= $mode === 'create' ? 'Dodaj zawodnika' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </div>
    </div>
</form>

<?php if ($mode === 'edit' && in_array($authUser['role'] ?? '', ['admin','zarzad'], true)): ?>
<div class="d-flex justify-content-end mt-3" style="max-width:1200px">
    <form method="post" action="<?= url('members/' . $member['id'] . '/recalc-fee') ?>"
          onsubmit="return confirm('Niezapisane zmiany w formularzu nie zostaną uwzględnione. Najpierw zapisz formularz, potem przelicz. Kontynuować?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Przelicz składkę
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Discipline row template (hidden) -->
<template id="disciplineRowTemplate">
    <div class="row g-2 mb-2 discipline-row">
        <div class="col-md-4">
            <select name="discipline_ids[]" class="form-select form-select-sm">
                <option value="">— wybierz —</option>
                <?php foreach ($disciplines as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="discipline_classes[]" class="form-select form-select-sm">
                <option value="">Klasa</option>
                <?php foreach ($disciplineClasses as $cls): ?>
                    <option value="<?= e($cls['name']) ?>"><?= e($cls['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="discipline_instructors[]" class="form-select form-select-sm">
                <option value="">Instruktor</option>
                <?php foreach ($instructors as $ins): ?>
                    <option value="<?= $ins['id'] ?>"><?= e($ins['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="discipline_joined[]" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-discipline"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</template>

<script>
// Auto-save: clicking license/judge links saves the form first, then navigates
document.querySelectorAll('.save-first').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var form = document.getElementById('memberForm');
        if (!form) return;
        document.getElementById('redirectAfter').value = this.href;
        form.submit();
    });
});
</script>

<script>
document.getElementById('addDisciplineBtn').addEventListener('click', function() {
    const tpl = document.getElementById('disciplineRowTemplate');
    const clone = tpl.content.cloneNode(true);
    document.getElementById('disciplinesContainer').appendChild(clone);
});
document.getElementById('disciplinesContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-discipline')) {
        e.target.closest('.discipline-row').remove();
    }
});

// Warning when class or joined-at changes — affects fee discounts
(function () {
    var container = document.getElementById('disciplinesContainer');
    if (!container) return;
    var warn = document.createElement('div');
    warn.className = 'alert alert-warning small py-2 mt-2 mb-0';
    warn.style.display = 'none';
    warn.innerHTML = '<i class="bi bi-exclamation-triangle"></i> '
        + '<strong>Zmiana ma wpływ na zniżki.</strong> '
        + 'Po zapisaniu formularza kliknij "Przelicz składkę".';
    container.appendChild(warn);

    container.addEventListener('change', function (e) {
        var n = e.target && e.target.name ? e.target.name : '';
        if (n === 'discipline_classes[]' || n === 'discipline_joined[]') {
            warn.style.display = '';
        }
    });
})();

// PESEL → data urodzenia + płeć
(function () {
    const peselInput = document.querySelector('input[name="pesel"]');
    if (!peselInput) return;

    function parsePesel(pesel) {
        if (!/^\d{11}$/.test(pesel)) return null;

        let yy    = parseInt(pesel.substring(0, 2), 10);
        let month = parseInt(pesel.substring(2, 4), 10);
        let day   = parseInt(pesel.substring(4, 6), 10);
        const genderDigit = parseInt(pesel[9], 10);

        let year;
        if (month >= 81 && month <= 92)      { year = 1800 + yy; month -= 80; }
        else if (month >= 1 && month <= 12)  { year = 1900 + yy; }
        else if (month >= 21 && month <= 32) { year = 2000 + yy; month -= 20; }
        else if (month >= 41 && month <= 52) { year = 2100 + yy; month -= 40; }
        else if (month >= 61 && month <= 72) { year = 2200 + yy; month -= 60; }
        else return null;

        const mm = String(month).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        return {
            date:   `${year}-${mm}-${dd}`,
            gender: genderDigit % 2 === 1 ? 'M' : 'K'
        };
    }

    peselInput.addEventListener('input', function () {
        const result = parsePesel(this.value.trim());
        if (!result) return;

        const birthInput  = document.querySelector('input[name="birth_date"]');
        const genderSelect = document.querySelector('select[name="gender"]');

        if (birthInput && !birthInput.value) {
            birthInput.value = result.date;
        }
        if (genderSelect && !genderSelect.value) {
            genderSelect.value = result.gender;
        }
    });
}());
</script>
