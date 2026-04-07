<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('competitions') ?>">Zawody</a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id']) ?>"><?= e($competition['name']) ?></a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/series') ?>">Serie: <?= e($event['name']) ?></a></li>
        <li class="breadcrumb-item active"><?= e($member['first_name']) ?> <?= e($member['last_name']) ?></li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <h2 class="h4 mb-0">
        <i class="bi bi-grid-3x3"></i>
        Metryczka: <?= e($member['first_name']) ?> <?= e($member['last_name']) ?>
    </h2>
    <span class="badge bg-secondary"><?= e($member['member_number']) ?></span>
    <span class="badge bg-light text-dark border"><?= e($event['name']) ?></span>
    <?php $stMap = ['decimal' => 'Dziesiętna', 'integer' => 'Całkowita', 'hit_miss' => 'Traf/Chyb']; ?>
    <span class="badge bg-info text-dark"><?= $stMap[$type] ?? '' ?></span>
</div>

<?php if ($officialResult): ?>
<div class="alert alert-secondary py-2 d-flex align-items-center gap-3 mb-3" id="official-alert">
    <i class="bi bi-trophy fs-5"></i>
    <div>
        Oficjalny wynik: <strong id="official-score-display">
            <?= $type === 'decimal'
                ? number_format((float)$officialResult['score'], 1)
                : (int)$officialResult['score'] ?>
        </strong>
        <?php if ($type === 'decimal' && $officialResult['score_inner'] !== null): ?>
            <span class="text-muted">(X: <?= (int)$officialResult['score_inner'] ?>)</span>
        <?php endif; ?>
    </div>
    <div class="ms-auto" id="match-badge"></div>
</div>
<?php else: ?>
<div class="alert alert-secondary py-2 mb-3">
    <i class="bi bi-info-circle"></i>
    Brak oficjalnego wyniku — zostanie ustawiony po zapisaniu serii (gdy zaznaczono checkbox poniżej).
</div>
<?php endif; ?>

<form method="post"
      action="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/series/' . $member['id']) ?>"
      id="seriesForm">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Dane z metryczki — <?= $serieCount ?> serii × <?= $serieSize ?> strzałów</strong>
            <small class="text-muted">Wpisuj wartości strzałów z lewej do prawej. Suma serii obliczana na bieżąco.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" id="seriesTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:60px">Seria</th>
                            <?php for ($sh = 1; $sh <= $serieSize; $sh++): ?>
                                <th class="text-center" style="min-width:52px"><?= $sh ?></th>
                            <?php endfor; ?>
                            <th class="text-center" style="min-width:80px">
                                Suma<br><small class="text-muted fw-normal">z papieru</small>
                            </th>
                            <th class="text-center" style="min-width:80px">
                                Obliczona<br><small class="text-muted fw-normal">przez system</small>
                            </th>
                            <?php if ($type === 'decimal'): ?>
                            <th class="text-center" style="width:60px">X</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php for ($s = 1; $s <= $serieCount; $s++):
                        $row = $seriesMap[$s] ?? null;
                        $existingShots = [];
                        if ($row) {
                            $existingShots = json_decode($row['shots'], true) ?? [];
                        }
                    ?>
                        <tr data-series="<?= $s ?>">
                            <td class="text-center fw-bold align-middle"><?= $s ?></td>
                            <?php for ($sh = 0; $sh < $serieSize; $sh++):
                                $val = $existingShots[$sh] ?? null;
                            ?>
                            <td class="p-1">
                                <?php if ($type === 'hit_miss'): ?>
                                    <select name="shots[<?= $s ?>][]"
                                            class="form-select form-select-sm shot-input text-center"
                                            style="width:52px;padding:2px 4px">
                                        <option value="">—</option>
                                        <option value="1" <?= $val === 1 ? 'selected' : '' ?>>T</option>
                                        <option value="0" <?= $val === 0 ? 'selected' : '' ?>>C</option>
                                    </select>
                                <?php else: ?>
                                    <input type="number"
                                           name="shots[<?= $s ?>][]"
                                           class="form-control form-control-sm shot-input text-center"
                                           style="width:52px;padding:2px 4px"
                                           step="<?= $type === 'decimal' ? '0.1' : '1' ?>"
                                           min="0"
                                           max="<?= $type === 'decimal' ? '10.9' : '10' ?>"
                                           value="<?= $val !== null ? e((string)$val) : '' ?>">
                                <?php endif; ?>
                            </td>
                            <?php endfor; ?>

                            <!-- Suma z papieru (ręcznie wpisana przez sędziego) -->
                            <td class="p-1">
                                <input type="number"
                                       name="series_total[<?= $s ?>]"
                                       id="total_paper_<?= $s ?>"
                                       class="form-control form-control-sm text-center series-total-input"
                                       style="width:78px;padding:2px 4px"
                                       step="<?= $type === 'decimal' ? '0.1' : '1' ?>"
                                       min="0"
                                       value="<?= $row ? e($row['series_total']) : '' ?>">
                            </td>

                            <!-- Obliczona przez system -->
                            <td class="p-1 text-center align-middle" id="computed_<?= $s ?>">
                                <span class="fw-semibold text-muted">—</span>
                            </td>

                            <?php if ($type === 'decimal'): ?>
                            <td class="p-1">
                                <input type="number"
                                       name="x_count[<?= $s ?>]"
                                       class="form-control form-control-sm text-center"
                                       style="width:52px;padding:2px 4px"
                                       min="0"
                                       max="<?= $serieSize ?>"
                                       value="<?= $row ? (int)$row['x_count'] : '' ?>">
                            </td>
                            <?php else: ?>
                                <input type="hidden" name="x_count[<?= $s ?>]" value="0">
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td class="text-center">RAZEM</td>
                            <td colspan="<?= $serieSize ?>"></td>
                            <td class="text-center" id="grand_paper">—</td>
                            <td class="text-center" id="grand_computed">—</td>
                            <?php if ($type === 'decimal'): ?>
                            <td class="text-center" id="grand_x">—</td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="update_official" value="1"
                       id="updateOfficial" checked>
                <label class="form-check-label" for="updateOfficial">
                    <strong>Zaktualizuj oficjalny wynik</strong>
                    <span class="text-muted fw-normal">
                        — ustaw wynik zawodnika na sumę wpisanych serii
                        <?= $type === 'decimal' ? ' (i X na sumę X z serii)' : '' ?>
                    </span>
                </label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-floppy"></i> Zapisz serie
                </button>
                <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/series') ?>"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Anuluj
                </a>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var isHM      = <?= $isHM ? 'true' : 'false' ?>;
    var isDecimal = <?= $type === 'decimal' ? 'true' : 'false' ?>;
    var serieCount = <?= $serieCount ?>;
    var officialScore = <?= $officialResult ? (float)$officialResult['score'] : 'null' ?>;

    function sumShots(row) {
        var inputs = row.querySelectorAll('.shot-input');
        var sum = 0;
        var allEmpty = true;
        inputs.forEach(function (inp) {
            var v = inp.tagName === 'SELECT' ? inp.value : inp.value.trim();
            if (v !== '' && v !== null) {
                allEmpty = false;
                sum += parseFloat(v) || 0;
            }
        });
        return allEmpty ? null : sum;
    }

    function round2(v) {
        return Math.round(v * 100) / 100;
    }

    function recalcRow(s) {
        var row = document.querySelector('tr[data-series="' + s + '"]');
        if (!row) return null;

        var computed = sumShots(row);
        var paperInput = document.getElementById('total_paper_' + s);
        var computedCell = document.getElementById('computed_' + s);

        if (computed === null) {
            computedCell.innerHTML = '<span class="text-muted">—</span>';
            return null;
        }

        var rounded = round2(computed);
        var paperVal = paperInput.value.trim();
        var html = '<span class="fw-semibold">' + (isDecimal ? rounded.toFixed(1) : rounded.toFixed(0)) + '</span>';

        if (paperVal !== '') {
            var paper = parseFloat(paperVal);
            if (Math.abs(paper - rounded) > 0.05) {
                html += ' <i class="bi bi-exclamation-triangle-fill text-warning" title="Niezgodność z sumą z papieru!"></i>';
                row.classList.add('table-warning');
                row.classList.remove('table-success');
            } else {
                html += ' <i class="bi bi-check-circle-fill text-success"></i>';
                row.classList.remove('table-warning');
                row.classList.add('table-success');
            }
        } else {
            row.classList.remove('table-warning', 'table-success');
        }

        computedCell.innerHTML = html;
        return rounded;
    }

    function recalcGrand() {
        var grandPaper    = 0;
        var grandComputed = 0;
        var grandX        = 0;
        var anyPaper      = false;
        var anyComputed   = false;

        for (var s = 1; s <= serieCount; s++) {
            var paperInput = document.getElementById('total_paper_' + s);
            if (paperInput && paperInput.value.trim() !== '') {
                grandPaper += parseFloat(paperInput.value) || 0;
                anyPaper = true;
            }

            var computed = recalcRow(s);
            if (computed !== null) {
                grandComputed += computed;
                anyComputed = true;
            }

            // X count
            var xInput = document.querySelector('input[name="x_count[' + s + ']"]');
            if (xInput) grandX += parseInt(xInput.value) || 0;
        }

        var gpEl = document.getElementById('grand_paper');
        var gcEl = document.getElementById('grand_computed');
        var gxEl = document.getElementById('grand_x');

        if (gpEl) gpEl.textContent = anyPaper    ? (isDecimal ? grandPaper.toFixed(1)    : Math.round(grandPaper).toString())    : '—';
        if (gcEl) gcEl.textContent = anyComputed ? (isDecimal ? grandComputed.toFixed(1) : Math.round(grandComputed).toString()) : '—';
        if (gxEl) gxEl.textContent = grandX > 0 ? grandX : '—';

        // Compare computed total vs official score
        var matchBadge = document.getElementById('match-badge');
        if (matchBadge && officialScore !== null && anyComputed) {
            var diff = Math.abs(grandComputed - officialScore);
            if (diff < 0.05) {
                matchBadge.innerHTML = '<span class="badge bg-success fs-6"><i class="bi bi-check-lg"></i> Suma zgodna z oficjalnym wynikiem</span>';
            } else {
                matchBadge.innerHTML = '<span class="badge bg-danger fs-6"><i class="bi bi-exclamation-triangle-fill"></i> Rozbieżność: obliczone ' +
                    (isDecimal ? grandComputed.toFixed(1) : Math.round(grandComputed)) +
                    ' ≠ oficjalne ' + (isDecimal ? officialScore.toFixed(1) : Math.round(officialScore)) + '</span>';
            }
        }
    }

    // Attach listeners
    document.querySelectorAll('.shot-input, .series-total-input').forEach(function (inp) {
        inp.addEventListener('input', function () { recalcGrand(); });
        inp.addEventListener('change', function () { recalcGrand(); });
    });
    document.querySelectorAll('input[name^="x_count"]').forEach(function (inp) {
        inp.addEventListener('input', function () { recalcGrand(); });
    });

    // Initial calculation on page load
    recalcGrand();
})();
</script>
