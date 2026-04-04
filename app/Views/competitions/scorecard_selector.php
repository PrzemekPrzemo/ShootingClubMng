<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><i class="bi bi-file-person"></i> Generuj metryczki</h2>
    <span class="badge bg-secondary"><?= e($competition['name']) ?></span>
    <span class="text-muted small ms-1"><?= format_date($competition['competition_date']) ?></span>
</div>

<?php if (empty($entries)): ?>
<div class="alert alert-warning">
    Brak zgłoszonych zawodników. <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>">Dodaj zgłoszenia</a>.
</div>
<?php elseif (empty($events)): ?>
<div class="alert alert-warning">
    Brak zdefiniowanych konkurencji. <a href="<?= url('competitions/' . $competition['id'] . '/events') ?>">Dodaj konkurencje</a>.
</div>
<?php else: ?>

<form id="scorecardForm"
      action="<?= url('competitions/' . $competition['id'] . '/scorecards/print') ?>"
      method="get"
      target="_blank">

<div class="row g-4">

    <!-- ── Wybór zawodników ─────────────────────────────────────── -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <strong><i class="bi bi-people"></i> Zawodnicy</strong>
                <span class="badge bg-secondary ms-1" id="memberCount">0</span>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAll('m[]', true)">
                        Zaznacz wszystkich
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAll('m[]', false)">
                        Odznacz
                    </button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                    <?php foreach ($entries as $e): ?>
                    <tr>
                        <td style="width:36px" class="text-center">
                            <input type="checkbox" name="m[]" value="<?= $e['member_id'] ?>"
                                   class="form-check-input member-cb" id="m_<?= $e['member_id'] ?>">
                        </td>
                        <td>
                            <label class="form-check-label w-100" for="m_<?= $e['member_id'] ?>" style="cursor:pointer">
                                <strong><?= e($e['last_name']) ?> <?= e($e['first_name']) ?></strong>
                                <small class="text-muted ms-1"><?= e($e['member_number']) ?></small>
                            </label>
                        </td>
                        <td class="text-end small text-muted">
                            <?= e($e['class'] ?? '') ?>
                            <?php if (!empty($e['age_category_name'])): ?>
                                <span class="badge bg-light text-dark border"><?= e($e['age_category_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($e['group_name'])): ?>
                                <span class="badge bg-secondary"><?= e($e['group_name']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Wybór konkurencji ────────────────────────────────────── -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <strong><i class="bi bi-bullseye"></i> Konkurencje</strong>
                <span class="badge bg-secondary ms-1" id="eventCount">0</span>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAll('e[]', true)">
                        Zaznacz wszystkie
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleAll('e[]', false)">
                        Odznacz
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                    <?php
                    $stMap = ['decimal'=>'dziesiętna','integer'=>'całkowita','hit_miss'=>'traf./chyb.'];
                    ?>
                    <tr>
                        <td style="width:36px" class="text-center">
                            <input type="checkbox" name="e[]" value="<?= $ev['id'] ?>"
                                   class="form-check-input event-cb" id="e_<?= $ev['id'] ?>">
                        </td>
                        <td>
                            <label class="form-check-label w-100" for="e_<?= $ev['id'] ?>" style="cursor:pointer">
                                <strong><?= e($ev['name']) ?></strong>
                            </label>
                        </td>
                        <td class="text-end small text-muted">
                            <?php if ($ev['shots_count']): ?>
                                <span class="badge bg-dark"><?= $ev['shots_count'] ?> strzałów</span>
                            <?php endif; ?>
                            <span class="badge bg-light text-dark border">
                                <?= $stMap[$ev['scoring_type']] ?? $ev['scoring_type'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── Podsumowanie + przycisk ──────────────────────────────────── -->
<?php if (isset($entryFee) && $entryFee > 0): ?>
<div class="alert alert-info mt-3 mb-0 py-2">
    <i class="bi bi-cash-coin"></i>
    Opłata startowa: <strong><?= format_money($entryFee) ?></strong> / zawodnik.
    Przed drukiem metryczek system zapyta o potwierdzenie opłaty dla każdego niezapłaconego zawodnika.
</div>
<?php endif; ?>
<div class="card mt-3">
    <div class="card-body d-flex align-items-center gap-4">
        <div class="text-muted">
            Zostanie wygenerowanych:
            <strong id="totalCards" class="fs-5 text-danger">0</strong>
            metryczek
            <span class="text-muted small">
                (<span id="selMembers">0</span> zawodników
                &times; <span id="selEvents">0</span> konkurencji)
            </span>
        </div>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-danger" id="printBtn" disabled>
                <i class="bi bi-printer"></i> Otwórz podgląd wydruku
            </button>
        </div>
        <div class="small text-muted">
            <i class="bi bi-info-circle"></i> Druk A5 poziomo
        </div>
    </div>
</div>

<!-- Payment confirmation modal (shown per unpaid member) -->
<?php if (isset($entryFee) && $entryFee > 0): ?>
<div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Potwierdzenie opłaty startowej</h5>
            </div>
            <div class="modal-body fs-5 text-center py-4" id="payModalBody"></div>
            <div class="modal-footer justify-content-center gap-3">
                <button type="button" class="btn btn-success btn-lg px-5" id="payYes">
                    <i class="bi bi-check-circle"></i> Tak, opłacono
                </button>
                <button type="button" class="btn btn-outline-danger btn-lg px-4" id="payNo">
                    <i class="bi bi-x-circle"></i> Nie
                </button>
            </div>
        </div>
    </div>
</div>
<div class="alert alert-danger mt-3 d-none" id="payBlockAlert">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Dokonaj opłaty i spróbuj ponownie.</strong>
    Metryczka nie może być wydrukowana bez uiszczonej opłaty startowej.
</div>
<?php endif; ?>

</form>
<?php endif; ?>

<script>
// Payment status map: member_id → {entryId, paid, name, amount}
var feeData = <?php
    $feeMap = [];
    if (isset($entryFee) && $entryFee > 0) {
        foreach ($entries as $e) {
            $disc   = isset($e['discount']) && $e['discount'] > 0 ? (float)$e['discount'] : 0;
            $amount = max(0, $entryFee - $disc);
            $feeMap[$e['member_id']] = [
                'entryId' => $e['id'],
                'paid'    => !empty($e['start_fee_paid']),
                'name'    => $e['last_name'] . ' ' . $e['first_name'],
                'amount'  => $amount,
                'csrf'    => csrf_token(),
            ];
        }
    }
    echo json_encode($feeMap);
?>;

// Pre-selection from URL query params (?e[]=ID&m[]=ID)
(function () {
    var preEvents  = <?= json_encode(array_map('intval', (array)($_GET['e'] ?? []))) ?>;
    var preMembers = <?= json_encode(array_map('intval', (array)($_GET['m'] ?? []))) ?>;
    if (preEvents.length) {
        preEvents.forEach(function (id) {
            var cb = document.getElementById('e_' + id);
            if (cb) cb.checked = true;
        });
        if (preMembers.length === 0) {
            document.querySelectorAll('.member-cb').forEach(function (cb) { cb.checked = true; });
        }
    }
    if (preMembers.length) {
        preMembers.forEach(function (id) {
            var cb = document.getElementById('m_' + id);
            if (cb) cb.checked = true;
        });
    }
})();

function toggleAll(name, checked) {
    document.querySelectorAll('input[name="' + name + '"]').forEach(cb => { cb.checked = checked; });
    updateCount();
}

function updateCount() {
    const members = document.querySelectorAll('.member-cb:checked').length;
    const events  = document.querySelectorAll('.event-cb:checked').length;
    const total   = members * events;
    document.getElementById('memberCount').textContent = members;
    document.getElementById('eventCount').textContent  = events;
    document.getElementById('selMembers').textContent  = members;
    document.getElementById('selEvents').textContent   = events;
    document.getElementById('totalCards').textContent  = total;
    document.getElementById('printBtn').disabled       = total === 0;
}

document.querySelectorAll('.member-cb, .event-cb').forEach(cb => { cb.addEventListener('change', updateCount); });
updateCount();

// ── Payment confirmation before print ──────────────────────────
document.getElementById('printBtn').addEventListener('click', function () {
    var alert = document.getElementById('payBlockAlert');
    if (alert) alert.classList.add('d-none');

    // Collect unpaid selected members (only if fee is set)
    var unpaid = [];
    if (Object.keys(feeData).length > 0) {
        document.querySelectorAll('.member-cb:checked').forEach(function (cb) {
            var mid = parseInt(cb.value);
            if (feeData[mid] && !feeData[mid].paid) {
                unpaid.push(mid);
            }
        });
    }

    if (unpaid.length === 0) {
        // All paid (or no fee) — submit print form
        document.getElementById('scorecardForm').submit();
        return;
    }

    // Process unpaid members one by one
    processNext(unpaid, 0);
});

function formatPLN(n) {
    return parseFloat(n).toFixed(2).replace('.', ',') + ' zł';
}

function processNext(list, idx) {
    if (idx >= list.length) {
        // All confirmed → print
        document.getElementById('scorecardForm').submit();
        return;
    }

    var mid  = list[idx];
    var info = feeData[mid];
    var modal = bootstrap.Modal.getOrCreate(document.getElementById('payModal'));
    document.getElementById('payModalBody').innerHTML =
        'Czy zawodnik <strong>' + info.name + '</strong><br>uiścił opłatę startową w wysokości<br>' +
        '<span class="text-danger fw-bold fs-3">' + formatPLN(info.amount) + '</span>?';

    document.getElementById('payYes').onclick = function () {
        modal.hide();
        // AJAX confirm payment
        fetch('<?= url('competitions/entries') ?>/' + info.entryId + '/confirm-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent(info.csrf)
        })
        .then(function (r) { return r.json(); })
        .then(function () {
            feeData[mid].paid = true;
            processNext(list, idx + 1);
        })
        .catch(function () {
            feeData[mid].paid = true;
            processNext(list, idx + 1);
        });
    };

    document.getElementById('payNo').onclick = function () {
        modal.hide();
        var alert = document.getElementById('payBlockAlert');
        if (alert) alert.classList.remove('d-none');
        // Abort print — do not proceed
    };

    modal.show();
}
</script>
