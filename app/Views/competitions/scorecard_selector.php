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
<div class="card mt-4">
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
            <button type="submit" class="btn btn-danger" id="printBtn" disabled>
                <i class="bi bi-printer"></i> Otwórz podgląd wydruku
            </button>
        </div>
        <div class="small text-muted">
            <i class="bi bi-info-circle"></i> Druk A5 poziomo
        </div>
    </div>
</div>

</form>
<?php endif; ?>

<script>
// Pre-selection from URL query params (?e[]=ID&m[]=ID)
(function () {
    var preEvents   = <?= json_encode(array_map('intval', (array)($_GET['e'] ?? []))) ?>;
    var preMembers  = <?= json_encode(array_map('intval', (array)($_GET['m'] ?? []))) ?>;

    if (preEvents.length) {
        preEvents.forEach(function (id) {
            var cb = document.getElementById('e_' + id);
            if (cb) cb.checked = true;
        });
        // When coming from a specific event, auto-select all members for convenience
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
    document.querySelectorAll('input[name="' + name + '"]').forEach(cb => {
        cb.checked = checked;
    });
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

document.querySelectorAll('.member-cb, .event-cb').forEach(cb => {
    cb.addEventListener('change', updateCount);
});

updateCount();
</script>
