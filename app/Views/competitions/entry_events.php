<?php
$selectedEventWeapons = $selectedEventWeapons ?? [];
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
<div class="col-lg-8">

<form method="post" action="<?= url('competitions/' . $competition['id'] . '/entries/' . $entry['id'] . '/events') ?>">
    <?= csrf_field() ?>

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
                        <th class="text-center" style="width:60px">Strzały</th>
                        <th style="width:200px">Typ broni</th>
                        <th class="text-end" style="width:90px">Cena</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <?php
                    $selected = array_key_exists($ev['id'], $selectedEventWeapons);
                    $wt       = $selectedEventWeapons[$ev['id']] ?? 'własna';
                    $feeOwn   = isset($ev['fee_own_weapon'])  ? (float)$ev['fee_own_weapon']  : null;
                    $feeClub  = isset($ev['fee_club_weapon']) ? (float)$ev['fee_club_weapon'] : null;
                    ?>
                    <tr class="<?= $selected ? 'table-primary' : '' ?> event-row"
                        data-fee-own="<?= $feeOwn ?? 0 ?>"
                        data-fee-club="<?= $feeClub ?? 0 ?>">
                        <td class="text-center align-middle">
                            <input type="checkbox" name="event_ids[]" value="<?= $ev['id'] ?>"
                                   class="form-check-input event-cb" <?= $selected ? 'checked' : '' ?>>
                        </td>
                        <td class="align-middle fw-semibold"><?= e($ev['name']) ?></td>
                        <td class="text-center align-middle text-muted small">
                            <?= $ev['shots_count'] ?? '—' ?>
                        </td>
                        <td class="align-middle">
                            <div class="btn-group btn-group-sm event-weapon-toggle <?= $selected ? '' : 'd-none' ?>">
                                <input type="radio" class="btn-check event-weapon"
                                       name="event_weapon[<?= $ev['id'] ?>]"
                                       id="wt_own_<?= $ev['id'] ?>"
                                       value="własna" <?= $wt === 'własna' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary" for="wt_own_<?= $ev['id'] ?>">
                                    <i class="bi bi-person"></i> Własna
                                </label>
                                <input type="radio" class="btn-check event-weapon"
                                       name="event_weapon[<?= $ev['id'] ?>]"
                                       id="wt_club_<?= $ev['id'] ?>"
                                       value="klubowa" <?= $wt === 'klubowa' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="wt_club_<?= $ev['id'] ?>">
                                    <i class="bi bi-building"></i> Klub.
                                </label>
                            </div>
                        </td>
                        <td class="text-end align-middle">
                            <span class="event-fee-display text-muted">—</span>
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

function getRowWeapon(row) {
    return row.querySelector('.event-weapon[value="klubowa"]:checked') ? 'klubowa' : 'własna';
}

function updateRowFee(row) {
    var cb      = row.querySelector('.event-cb');
    var feeSpan = row.querySelector('.event-fee-display');
    if (!cb || !feeSpan) return;
    if (!cb.checked) {
        feeSpan.textContent = '—';
        feeSpan.className = 'event-fee-display text-muted';
        return;
    }
    var wt  = getRowWeapon(row);
    var fee = wt === 'klubowa'
        ? parseFloat(row.dataset.feeClub || 0)
        : parseFloat(row.dataset.feeOwn  || 0);
    if (fee > 0) {
        feeSpan.textContent = fee.toFixed(2).replace('.', ',') + ' zł';
        feeSpan.className = 'event-fee-display badge bg-info text-dark';
    } else {
        feeSpan.textContent = '—';
        feeSpan.className = 'event-fee-display text-muted';
    }
}

function updateFee() {
    var total = 0;
    document.querySelectorAll('.event-row').forEach(function(row) {
        var cb     = row.querySelector('.event-cb');
        var toggle = row.querySelector('.event-weapon-toggle');
        var on     = cb && cb.checked;
        row.classList.toggle('table-primary', on);
        if (toggle) toggle.classList.toggle('d-none', !on);
        updateRowFee(row);
        if (on) {
            var wt = getRowWeapon(row);
            total += wt === 'klubowa'
                ? parseFloat(row.dataset.feeClub || 0)
                : parseFloat(row.dataset.feeOwn  || 0);
        }
    });
    total = Math.max(0, total - discount);
    document.getElementById('feeTotal').textContent = total.toFixed(2).replace('.', ',') + ' zł';
    document.getElementById('feeTotal').className = 'fs-4 fw-bold ' + (total > 0 ? 'text-danger' : 'text-success');
}

function toggleAll(on) {
    document.querySelectorAll('.event-cb').forEach(function(cb) { cb.checked = on; });
    updateFee();
}

document.querySelectorAll('.event-cb').forEach(function(cb) {
    cb.addEventListener('change', updateFee);
});
document.querySelectorAll('.event-weapon').forEach(function(r) {
    r.addEventListener('change', updateFee);
});

// Make whole row clickable except weapon toggle area
document.querySelectorAll('.event-row').forEach(function(row) {
    row.addEventListener('click', function(e) {
        if (e.target.closest('.event-weapon-toggle') || e.target.tagName === 'INPUT') return;
        var cb = row.querySelector('.event-cb');
        if (cb) { cb.checked = !cb.checked; updateFee(); }
    });
    row.style.cursor = 'pointer';
});

updateFee();
</script>
