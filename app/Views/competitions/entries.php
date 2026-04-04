<?php
// entryFees = [entry_id => float] — computed from selected events × weapon type
$entryFees = $entryFees ?? [];
$hasFees   = array_sum($entryFees) > 0 || !empty($entryFees);
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Zgłoszenia — <?= e($competition['name']) ?></h2>
    <span class="badge bg-secondary ms-1"><?= format_date($competition['competition_date']) ?></span>
    <a href="<?= url('competitions/' . $competition['id'] . '/events') ?>" class="btn btn-sm btn-outline-info ms-auto">
        <i class="bi bi-bullseye"></i> Zarządzaj konkurencjami
    </a>
</div>

<!-- Payment confirmation modal -->
<div class="modal fade" id="feeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Potwierdzenie opłaty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="feeModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="feeModalYes">Tak, opłacono</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Zawodnik</th>
                            <th>Broń</th>
                            <th>Klasa</th>
                            <th>Status</th>
                            <th title="Obliczone z wybranych konkurencji">Do zapłaty</th>
                            <th>Opłata</th>
                            <th>Rabat</th>
                            <th>Konkurencje</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entries as $i => $e): ?>
                        <?php
                        $sc        = match($e['status']) { 'potwierdzony'=>'success','wycofany'=>'secondary','zdyskwalifikowany'=>'danger',default=>'warning' };
                        $amountDue = $entryFees[$e['id']] ?? 0;
                        $paid      = !empty($e['start_fee_paid']);
                        $discount  = isset($e['discount']) && $e['discount'] > 0 ? (float)$e['discount'] : 0.0;
                        $weapon    = $e['weapon_type'] ?? 'własna';
                        ?>
                        <tr>
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td>
                                <a href="<?= url('members/' . $e['member_id']) ?>"><?= e($e['last_name']) ?> <?= e($e['first_name']) ?></a><br>
                                <small class="text-muted"><?= e($e['member_number']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $weapon === 'klubowa' ? 'secondary' : 'light border text-dark' ?>">
                                    <?= $weapon === 'klubowa' ? 'Klub.' : 'Własna' ?>
                                </span>
                            </td>
                            <td><?= e($e['class'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $sc ?>"><?= e($e['status']) ?></span></td>

                            <!-- Amount due from events -->
                            <td class="fw-bold <?= $paid ? 'text-success' : ($amountDue > 0 ? 'text-danger' : 'text-muted') ?>">
                                <?= $amountDue > 0 ? format_money($amountDue) : '—' ?>
                            </td>

                            <!-- Payment toggle / confirm -->
                            <td>
                                <?php if (isset($e['start_fee_paid'])): ?>
                                    <?php if (!$paid && $amountDue > 0): ?>
                                        <button type="button"
                                                class="btn btn-sm py-0 btn-outline-secondary btn-confirm-fee"
                                                data-entry-id="<?= $e['id'] ?>"
                                                data-member-name="<?= e($e['last_name'] . ' ' . $e['first_name']) ?>"
                                                data-amount="<?= format_money($amountDue) ?>"
                                                data-csrf="<?= csrf_token() ?>"
                                                title="Potwierdź opłatę">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/fee') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm py-0 btn-<?= $paid ? 'success' : 'outline-secondary' ?>"
                                                    title="<?= $paid ? 'Opłacono — kliknij by cofnąć' : 'Brak opłaty' ?>">
                                                <i class="bi bi-cash<?= $paid ? '' : '-coin' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <!-- Discount -->
                            <td style="min-width:100px">
                                <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/discount') ?>"
                                      class="d-flex gap-1 align-items-center">
                                    <?= csrf_field() ?>
                                    <input type="number" name="discount" class="form-control form-control-sm py-0"
                                           style="width:65px" min="0" step="0.01"
                                           value="<?= e($discount > 0 ? number_format($discount, 2, '.', '') : '') ?>"
                                           placeholder="0.00">
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-1" title="Zapisz rabat">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </form>
                            </td>

                            <!-- Event selection link -->
                            <td>
                                <a href="<?= url('competitions/' . $competition['id'] . '/entries/' . $e['id'] . '/events') ?>"
                                   class="btn btn-sm py-0 btn-outline-primary" title="Wybierz konkurencje">
                                    <i class="bi bi-bullseye"></i>
                                </a>
                            </td>

                            <!-- Actions -->
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($e['status'] === 'zgloszony'): ?>
                                    <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/approve') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-success py-0" title="Zatwierdź"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/reject') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-warning py-0" title="Odrzuć"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/entries/' . $e['id'] . '/remove') ?>"
                                      class="d-inline" onsubmit="return confirm('Usunąć zgłoszenie?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-3">Brak zgłoszeń.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($entries && array_sum($entryFees) > 0): ?>
        <?php
        $totalDue  = array_sum($entryFees);
        $totalPaid = 0;
        foreach ($entries as $e) {
            if (!empty($e['start_fee_paid'])) $totalPaid += $entryFees[$e['id']] ?? 0;
        }
        ?>
        <div class="d-flex gap-4 mt-2 px-1 small text-muted">
            <span>Łącznie do zapłaty: <strong class="text-danger"><?= format_money($totalDue) ?></strong></span>
            <span>Zapłacono: <strong class="text-success"><?= format_money($totalPaid) ?></strong></span>
            <span>Pozostało: <strong class="<?= ($totalDue - $totalPaid) > 0 ? 'text-danger' : 'text-success' ?>"><?= format_money(max(0, $totalDue - $totalPaid)) ?></strong></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-3">
        <?php if ($competition['status'] === 'otwarte'): ?>
        <div class="card">
            <div class="card-header"><strong>Dodaj zgłoszenie</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/entries/add') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select form-select-sm" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['full_name'] ?? $m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Klasa</label>
                        <select name="class" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php foreach (['Master','A','B','C','D'] as $cls): ?>
                                <option value="<?= $cls ?>"><?= $cls ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($groups): ?>
                    <div class="mb-2">
                        <label class="form-label">Grupa startowa</label>
                        <select name="group_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success btn-sm w-100">Zgłoś zawodnika</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Zapisy są zamknięte (status: <?= e($competition['status']) ?>).</div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-body small text-muted">
                <i class="bi bi-info-circle"></i>
                Kliknij <i class="bi bi-bullseye text-primary"></i> przy zawodniku, by wybrać w jakich konkurencjach startuje.
                Opłata jest automatycznie wyliczana z cen przypisanych do konkurencji.
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal   = new bootstrap.Modal(document.getElementById('feeModal'));
    var body    = document.getElementById('feeModalBody');
    var yesBtn  = document.getElementById('feeModalYes');
    var current = null;

    document.querySelectorAll('.btn-confirm-fee').forEach(function (btn) {
        btn.addEventListener('click', function () {
            current = btn;
            body.innerHTML = 'Czy zawodnik <strong>' + btn.dataset.memberName + '</strong> uiścił opłatę startową w wysokości <strong>' + btn.dataset.amount + '</strong>?';
            yesBtn.onclick = function () {
                modal.hide();
                fetch('<?= url('competitions/entries') ?>/' + current.dataset.entryId + '/confirm-payment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_csrf=' + encodeURIComponent(current.dataset.csrf)
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) { location.reload(); }
                    else { alert('Błąd: ' + (data.error || 'Nieznany błąd')); }
                })
                .catch(function () { location.reload(); });
            };
            modal.show();
        });
    });
})();
</script>
