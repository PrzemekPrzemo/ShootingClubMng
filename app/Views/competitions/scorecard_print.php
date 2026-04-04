<?php
/**
 * A5 landscape per-competitor scorecards.
 * One card per member × event combination.
 * Layout: print_scorecard
 *
 * Vars: $competition, $clubName, $cards[]
 *   card: ['member'=>[...], 'event'=>[...], 'result'=>[...|null], 'license_number'=>'...']
 */
?>
<?php foreach ($cards as $card):
    $member        = $card['member'];
    $event         = $card['event'];
    $result        = $card['result'];
    $licenseNumber = $card['license_number'] ?? '';

    $shots     = (int)($event['shots_count'] ?? 0);
    $type      = $event['scoring_type'] ?? 'decimal';
    $isHM      = $type === 'hit_miss';
    $serieSize = $isHM ? 5 : 10;

    if ($shots > 0) {
        $serieCount = (int)ceil($shots / $serieSize);
    } else {
        $serieCount = 6;
        $shots      = $serieCount * $serieSize;
    }

    $score      = $result['score']       ?? null;
    $scoreInner = $result['score_inner'] ?? null;
    $place      = $result['place']       ?? null;
    $notes      = $result['notes']       ?? '';

    // Sport class from competition_entries
    $sportClass = $member['class'] ?? '';
?>
<div class="scorecard">

    <!-- ── Nagłówek: zawody + data/miejsce ──────────────────── -->
    <div class="sc-top">
        <div class="sc-competition"><?= e($competition['name']) ?></div>
        <div class="sc-date-loc">
            <?= format_date($competition['competition_date']) ?>
            <?php if (!empty($competition['location'])): ?>
                &nbsp;&bull;&nbsp;<?= e($competition['location']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Dane zawodnika ───────────────────────────────────── -->
    <div class="sc-member">

        <!-- Wiersz 1: imię/nazwisko + badge konkurencji -->
        <div class="sc-member-row1">
            <div class="sc-member-name">
                <?= e($member['last_name']) ?> <?= e($member['first_name']) ?>
            </div>
            <div class="sc-event-badge"><?= e($event['name']) ?></div>
        </div>

        <!-- Wiersz 2: klub + licencja + zawody + data -->
        <div class="sc-member-row2">
            <?php if ($clubName): ?>
            <span class="item"><span class="sc-lbl">Klub</span> <?= e($clubName) ?></span>
            <span class="sep">|</span>
            <?php endif; ?>
            <?php if ($licenseNumber): ?>
            <span class="item"><span class="sc-lbl">Nr licencji</span> <?= e($licenseNumber) ?></span>
            <span class="sep">|</span>
            <?php endif; ?>
            <span class="item"><span class="sc-lbl">Zawody</span> <?= format_date($competition['competition_date']) ?></span>
        </div>

        <!-- Wiersz 3: klasa sportowa, kategoria wiekowa, klasa zawodnika, grupa startowa -->
        <div class="sc-member-row3">
            <?php if ($member['member_number'] ?? ''): ?>
            <span><span class="sc-lbl">Nr leg.</span> <?= e($member['member_number']) ?></span>
            <?php endif; ?>
            <?php if ($sportClass): ?>
            <span><span class="sc-lbl">Klasa</span> <?= e($sportClass) ?></span>
            <?php endif; ?>
            <?php if (!empty($member['age_category_name'])): ?>
            <span><span class="sc-lbl">Kategoria</span> <?= e($member['age_category_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($member['member_class_name'])): ?>
            <span><span class="sc-lbl">Gr. zawodnicza</span> <?= e($member['member_class_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($member['group_name'])): ?>
            <span><span class="sc-lbl">Gr. startowa</span>
                <?= e($member['group_name']) ?>
                <?php if (!empty($member['group_start_time'])): ?>
                    <?= e(substr($member['group_start_time'], 0, 5)) ?>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Siatka strzałów ──────────────────────────────────── -->
    <div class="sc-shots-wrap">
    <table class="sc-shots-table">
        <thead>
            <tr>
                <th class="th-ser">Seria</th>
                <?php for ($c = 1; $c <= $serieSize; $c++): ?>
                <th><?= $c ?></th>
                <?php endfor; ?>
                <th class="th-sum">Suma</th>
                <?php if (!$isHM): ?><th class="th-x">X</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php for ($s = 1; $s <= $serieCount; $s++):
            $serieLabel = $isHM
                ? "S{$s}"
                : (($s - 1) * $serieSize + 1) . '–' . ($s * $serieSize);
        ?>
            <tr>
                <td class="td-ser"><?= $serieLabel ?></td>
                <?php for ($c = 1; $c <= $serieSize; $c++):
                    $globalShot = ($s - 1) * $serieSize + $c;
                    $active = ($globalShot <= $shots);
                ?>
                <td class="td-score <?= $active ? '' : 'disabled-cell' ?>">
                    <?= $active ? '' : '&ndash;' ?>
                </td>
                <?php endfor; ?>
                <td class="td-sum"></td>
                <?php if (!$isHM): ?><td class="td-x"></td><?php endif; ?>
            </tr>
        <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="tfoot-label" colspan="<?= $serieSize + 1 ?>">Wynik końcowy:</th>
                <td class="td-sum tfoot-total">
                    <?= $score !== null ? e((string)$score) : '' ?>
                </td>
                <?php if (!$isHM): ?>
                <td class="td-x tfoot-x">
                    <?= $scoreInner !== null ? e((string)$scoreInner) . 'X' : '' ?>
                </td>
                <?php endif; ?>
            </tr>
        </tfoot>
    </table>
    </div>

    <!-- ── Miejsce + uwagi ──────────────────────────────────── -->
    <div class="sc-bottom">
        <div class="sc-place-box">
            <div class="sc-lbl">Miejsce</div>
            <div class="sc-place-val"><?= $place ?? '' ?></div>
        </div>
        <div class="sc-notes-box">
            <div class="sc-lbl">Uwagi</div>
            <?= e($notes) ?>
        </div>
    </div>

    <!-- ── Podpisy ────────────────────────────────────────────── -->
    <div class="sc-signatures">
        <div class="sc-sig">
            <div class="sc-sig-line"></div>
            <div class="sc-sig-label">Podpis zawodnika</div>
        </div>
        <div class="sc-sig">
            <div class="sc-sig-line"></div>
            <div class="sc-sig-label">Podpis sędziego</div>
        </div>
        <div class="sc-sig">
            <div class="sc-sig-line"></div>
            <div class="sc-sig-label">Podpis obliczeniowego</div>
        </div>
    </div>

</div>
<?php endforeach; ?>
