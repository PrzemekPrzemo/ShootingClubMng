<?php
$weaponType = $entry['weapon_type'] ?? 'własna';
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">Konkurencje zawodnika</h2>
    <span class="badge bg-secondary ms-1"><?= e($entry['last_name'] . ' ' . $entry['first_name']) ?></span>
    <span class="badge bg-outline-secondary border text-muted ms-1"><?= e($competition['name']) ?></span>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">

<form method="post" action="<?= url('competitions/' . $competition['id'] . '/entries/' . $entry['id'] . '/events') ?>">
    <?= csrf_field() ?>

    <!-- Weapon type selector -->
    <div class="card mb-3">
        <div class="card-header"><strong><i class="bi bi-bullseye"></i> Typ broni</strong></div>
        <div class="card-body">
            <div class="d-flex gap-4">
                <div class="form-check form-check-inline">
                    <input class="form-check-input weapon-radio" type="radio" name="weapon_type"
                           id="wt_own" value="własna" <?= $weaponType === 'własna' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="wt_own">
                        <i class="bi bi-person"></i> Broń własna
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input weapon-radio" type="radio" name="weapon_type"
                           id="wt_club" value="klubowa" <?= $weaponType === 'klubowa' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="wt_club">
                        <i class="bi bi-building"></i> Broń klubowa
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Event selection -->
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong><i class="bi bi-trophy"></i> Wybierz konkurencje</strong>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAll(true)">Zaznacz wszystkie</button>
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAll(false)">Odznacz</button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0" id="eventsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px"></th>
                        <th>Konkurencja</th>
                        <th class="text-center">Strzały</th>
                        <th class="text-end price-own <?= $weaponType === 'własna' ? '' : 'd-none' ?>">Broń własna</th>
                        <th class="text-end price-club <?= $weaponType === 'klubowa' ? '' : 'd-none' ?>">Broń klubowa</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <?php
                    $checked  = in_array($ev['id'], $selectedEventIds);
                    $feeOwn   = isset($ev['fee_own_weapon'])  ? (float)$ev['fee_own_weapon']  : null;
                    $feeClub  = isset($ev['fee_club_weapon']) ? (float)$ev['fee_club_weapon'] : null;
                    ?>
                    <tr class="<?= $checked ? 'table-primary' : '' ?> event-row"
                        data-fee-own="<?= $feeOwn ?? 0 ?>"
                        data-fee-club="<?= $feeClub ?? 0 ?>">
                        <td class="text-center align-middle">
                            <input type="checkbox" name="event_ids[]" value="<?= $ev['id'] ?>"
                                   class="form-check-input event-cb" <?= $checked ? 'checked' : '' ?>>
                        </td>
                        <td class="align-middle">
                            <label class="form-check-label w-100" style="cursor:pointer">
                                <strong><?= e($ev['name']) ?></strong>
                            </label>
                        </td>
                        <td class="text-center align-middle text-muted small">
                            <?= $ev['shots_count'] ?? '—' ?>
                        </td>
                        <td class="text-end align-middle price-own <?= $weaponType === 'własna' ? '' : 'd-none' ?>">
                            <?= $feeOwn !== null ? '<span class="badge bg-info text-dark">' . format_money($feeOwn) . '</span>' : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-end align-middle price-club <?= $weaponType === 'klubowa' ? '' : 'd-none' ?>">
                            <?= $feeClub !== null ? '<span class="badge bg-secondary">' . format_money($feeClub) . '</span>' : '<span class="text-muted">—</span>' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Brak zdefiniowanych konkurencji.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fee summary -->
    <div class="card mb-3 border-success">
        <div class="card-body d-flex align-items-center justify-content-between py-2">
            <span class="text-muted">
                Łączna opłata startowa
                <?php if (isset($entry['discount']) && $entry['discount'] > 0): ?>
                    <span class="text-muted small">(rabat: <?= format_money($entry['discount']) ?>)</span>
                <?php endif; ?>
            </span>
            <span class="fs-4 fw-bold text-success" id="feeTotal">0,00 zł</span>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger flex-grow-1">
            <i class="bi bi-check-lg"></i> Zapisz wybór konkurencji
        </button>
        <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>" class="btn btn-outline-secondary">
            Anuluj
        </a>
    </div>
</form>

</div>
</div>

<script>
var discount = <?= isset($entry['discount']) ? (float)$entry['discount'] : 0 ?>;

function getWeapon() {
    return document.querySelector('.weapon-radio:checked')?.value || 'własna';
}

function toggleAll(on) {
    document.querySelectorAll('.event-cb').forEach(function(cb) { cb.checked = on; });
    updateFee();
}

function updateFee() {
    var weapon = getWeapon();
    var total  = 0;
    document.querySelectorAll('.event-row').forEach(function(row) {
        var cb = row.querySelector('.event-cb');
        if (cb && cb.checked) {
            total += weapon === 'klubowa'
                ? parseFloat(row.dataset.feeClub || 0)
                : parseFloat(row.dataset.feeOwn  || 0);
        }
        row.classList.toggle('table-primary', cb && cb.checked);
    });
    total = Math.max(0, total - discount);
    document.getElementById('feeTotal').textContent = total.toFixed(2).replace('.', ',') + ' zł';
    document.getElementById('feeTotal').className = 'fs-4 fw-bold ' + (total > 0 ? 'text-danger' : 'text-success');
}

function updateWeaponColumns() {
    var weapon = getWeapon();
    document.querySelectorAll('.price-own').forEach(function(el) {
        el.classList.toggle('d-none', weapon !== 'własna');
    });
    document.querySelectorAll('.price-club').forEach(function(el) {
        el.classList.toggle('d-none', weapon !== 'klubowa');
    });
    updateFee();
}

document.querySelectorAll('.weapon-radio').forEach(function(r) {
    r.addEventListener('change', updateWeaponColumns);
});
document.querySelectorAll('.event-cb').forEach(function(cb) {
    cb.addEventListener('change', updateFee);
});

// Make whole row clickable
document.querySelectorAll('.event-row').forEach(function(row) {
    row.addEventListener('click', function(e) {
        if (e.target.tagName === 'INPUT') return;
        var cb = row.querySelector('.event-cb');
        if (cb) { cb.checked = !cb.checked; updateFee(); }
    });
    row.style.cursor = 'pointer';
});

updateFee();
</script>
