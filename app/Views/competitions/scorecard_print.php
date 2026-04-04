<?php
/**
 * A5 landscape per-competitor scorecards.
 * One card per member × event combination.
 * Layout: print_scorecard (A5 landscape, @page { size: A5 landscape; margin: 0; })
 *
 * Available vars:
 *   $competition  — array  competition row
 *   $cards        — array  of [ 'member' => [...], 'event' => [...], 'result' => [...|null] ]
 */
?>
<?php foreach ($cards as $card):
    $member  = $card['member'];
    $event   = $card['event'];
    $result  = $card['result'];

    $shots   = (int)($event['shots_count'] ?? 0);
    $type    = $event['scoring_type'] ?? 'decimal';
    $isHM    = $type === 'hit_miss';

    /* ── Series layout ─────────────────────────────────────────────
     * Up to 60 shots: 6 series × 10, or shots_count / 10 series
     * For hit/miss use 5-shot series, max 5 cols wide
     */
    $serieSize = $isHM ? 5 : 10;
    if ($shots > 0) {
        $serieCount = (int)ceil($shots / $serieSize);
    } else {
        $serieCount = 6;   // blank card
        $shots      = $serieCount * $serieSize;
    }
    $colsPerRow = min($serieCount, 6);   // max 6 series across

    /* Pre-fill existing scores if available */
    $score      = $result['score']       ?? null;
    $scoreInner = $result['score_inner'] ?? null;
    $place      = $result['place']       ?? null;
    $notes      = $result['notes']       ?? '';
?>
<div class="scorecard">

    <!-- ── Top header: competition name + date/location ─────────── -->
    <div class="sc-top">
        <div class="sc-competition"><?= e($competition['name']) ?></div>
        <div class="sc-date-loc">
            <?= format_date($competition['competition_date']) ?>
            <?php if (!empty($competition['location'])): ?>
                &nbsp;&bull;&nbsp;<?= e($competition['location']) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Competitor info strip ────────────────────────────────── -->
    <div class="sc-member">
        <div class="sc-member-name">
            <?= e($member['last_name']) ?> <?= e($member['first_name']) ?>
            <small class="text-muted" style="font-size:8pt;font-weight:normal">
                &nbsp;<?= e($member['member_number'] ?? '') ?>
            </small>
        </div>
        <div class="sc-member-meta">
            <?php if (!empty($member['class'])): ?>
            <span><span class="sc-lbl">klasa</span><br><?= e($member['class']) ?></span>
            <?php endif; ?>
            <?php if (!empty($member['age_category_name'])): ?>
            <span><span class="sc-lbl">kat.</span><br><?= e($member['age_category_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($member['member_class_name'])): ?>
            <span><span class="sc-lbl">gr.</span><br><?= e($member['member_class_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($member['group_name'])): ?>
            <span><span class="sc-lbl">gr. start.</span><br><?= e($member['group_name']) ?></span>
            <?php endif; ?>
        </div>
        <div class="sc-event-badge"><?= e($event['name']) ?></div>
    </div>

    <!-- ── Score grid ───────────────────────────────────────────── -->
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
        <?php
        $shotsRendered = 0;
        for ($s = 1; $s <= $serieCount; $s++):
            $serieLabel = $isHM ? "S{$s}" : (($s - 1) * $serieSize + 1) . '–' . ($s * $serieSize);
        ?>
            <tr>
                <td class="td-ser"><?= $serieLabel ?></td>
                <?php for ($c = 1; $c <= $serieSize; $c++):
                    $globalShot = ($s - 1) * $serieSize + $c;
                    $active = ($globalShot <= $shots || $shots === 0);
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
                <th class="td-ser tfoot-label" colspan="<?= $serieSize + 1 ?>">Wynik końcowy:</th>
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

    <!-- ── Bottom: place + notes ────────────────────────────────── -->
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

    <!-- ── Signatures ────────────────────────────────────────────── -->
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
