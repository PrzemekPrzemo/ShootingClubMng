<?php
$stMap = ['decimal' => 'dziesiętna', 'integer' => 'całkowita', 'hit_miss' => 'trafiony/chybiony'];
$shots = (int)($event['shots_count'] ?? 0);
// Split shots into series of 10 for the grid
$seriesSize = 10;
$series = $shots > 0 ? (int)ceil($shots / $seriesSize) : 1;

// Pre-filled results map: member_id => result
$rmap = [];
foreach ($resultsMap as $memberId => $r) {
    $rmap[$memberId] = $r;
}
?>

<?php foreach ($entries as $idx => $entry):
    $result = $rmap[$entry['member_id']] ?? null;
?>
<div class="scorecard">

    <!-- Nagłówek zawodów -->
    <div class="sc-header">
        <div class="sc-competition">
            <span class="sc-label">Zawody:</span>
            <strong><?= e($competition['name']) ?></strong>
        </div>
        <div class="sc-meta-row">
            <span><span class="sc-label">Data:</span> <?= format_date($competition['competition_date']) ?></span>
            <span><span class="sc-label">Miejsce:</span> <?= e($competition['location'] ?? '—') ?></span>
            <?php if ($competition['discipline_name']): ?>
            <span><span class="sc-label">Dyscyplina:</span> <?= e($competition['discipline_name']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nagłówek konkurencji -->
    <div class="sc-event">
        <span class="sc-label">Konkurencja:</span>
        <strong><?= e($event['name']) ?></strong>
        <?php if ($shots): ?>
        — <?= $shots ?> strzałów
        <?php endif; ?>
        <span class="sc-scoring">(punktacja <?= $stMap[$event['scoring_type']] ?? $event['scoring_type'] ?>)</span>
    </div>

    <!-- Dane zawodnika -->
    <div class="sc-competitor">
        <div class="sc-name">
            <span class="sc-label">Zawodnik:</span>
            <strong><?= e($entry['last_name']) ?> <?= e($entry['first_name']) ?></strong>
        </div>
        <div class="sc-details">
            <span><span class="sc-label">Nr leg.:</span> <?= e($entry['member_number']) ?></span>
            <span><span class="sc-label">Klasa:</span> <?= e($entry['class'] ?? ($entry['member_class_code'] ?? '—')) ?></span>
            <span><span class="sc-label">Kategoria:</span> <?= e($entry['age_category_name'] ?? '—') ?></span>
            <?php if (!empty($entry['group_name'])): ?>
            <span><span class="sc-label">Grupa:</span> <?= e($entry['group_name']) ?>
                <?php if (!empty($entry['group_start_time'])): ?>
                  <?= substr($entry['group_start_time'], 11, 5) ?>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabela punktów per strzał -->
    <?php if ($shots > 0): ?>
    <div class="sc-shots-section">
        <table class="sc-shots-table">
            <thead>
                <tr>
                    <th class="th-series">Seria</th>
                    <?php for ($s = 1; $s <= $seriesSize; $s++): ?>
                    <th><?= $s ?></th>
                    <?php endfor; ?>
                    <th class="th-sum">Suma</th>
                    <?php if ($event['scoring_type'] === 'decimal'): ?>
                    <th class="th-x">X</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php for ($ser = 0; $ser < $series; $ser++):
                $firstShot = $ser * $seriesSize + 1;
                $lastShot  = min($shots, ($ser + 1) * $seriesSize);
            ?>
                <tr>
                    <td class="td-series"><?= $firstShot ?>–<?= $lastShot ?></td>
                    <?php for ($s = 1; $s <= $seriesSize; $s++):
                        $shotNum = $ser * $seriesSize + $s;
                    ?>
                    <td class="td-score"><?= ($shotNum <= $shots) ? '' : '<span class="disabled">—</span>' ?></td>
                    <?php endfor; ?>
                    <td class="td-sum"></td>
                    <?php if ($event['scoring_type'] === 'decimal'): ?>
                    <td class="td-x"></td>
                    <?php endif; ?>
                </tr>
            <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="<?= $seriesSize + 1 ?>" class="total-label">WYNIK CAŁKOWITY</th>
                    <td class="td-total"><?= ($result && $result['score'] !== null) ? e($result['score']) : '' ?></td>
                    <?php if ($event['scoring_type'] === 'decimal'): ?>
                    <td class="td-x-total"><?= ($result && $result['score_inner'] !== null) ? e($result['score_inner']) : '' ?></td>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <!-- Brak zdefiniowanej liczby strzałów — puste pole wyniku -->
    <div class="sc-result-plain">
        <table class="sc-plain-table">
            <tr>
                <th>Wynik</th>
                <td><?= ($result && $result['score'] !== null) ? e($result['score']) : '' ?></td>
                <?php if ($event['scoring_type'] === 'decimal'): ?>
                <th>X-count</th>
                <td><?= ($result && $result['score_inner'] !== null) ? e($result['score_inner']) : '' ?></td>
                <?php endif; ?>
                <th>Miejsce</th>
                <td><?= ($result && $result['place'] !== null) ? e($result['place']) : '' ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Miejsce i uwagi -->
    <div class="sc-footer-row">
        <div class="sc-place-box">
            <div class="sc-label">Miejsce w konkurencji</div>
            <div class="sc-place-value"><?= ($result && $result['place'] !== null) ? e($result['place']) : '' ?></div>
        </div>
        <div class="sc-notes-box">
            <div class="sc-label">Uwagi</div>
            <div class="sc-notes-value"><?= ($result && !empty($result['notes'])) ? e($result['notes']) : '' ?></div>
        </div>
    </div>

    <!-- Podpisy -->
    <div class="sc-signatures">
        <div class="sc-sig-field">
            <div class="sc-sig-line"></div>
            <div class="sc-sig-label">Podpis zawodnika</div>
        </div>
        <div class="sc-sig-field">
            <div class="sc-sig-line"></div>
            <div class="sc-sig-label">Podpis sędziego</div>
        </div>
        <div class="sc-sig-field">
            <div class="sc-sig-line"></div>
            <div class="sc-sig-label">Pieczęć / podpis weryfikującego</div>
        </div>
    </div>

</div><!-- /.scorecard -->
<?php endforeach; ?>

<?php if (empty($entries)): ?>
<div style="text-align:center;padding:20mm;font-size:12pt;color:#666">
    Brak zgłoszonych zawodników w tych zawodach.
</div>
<?php endif; ?>
