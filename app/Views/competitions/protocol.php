<?php
$statusLabels = ['planowane'=>'Planowane','otwarte'=>'Otwarte','zamkniete'=>'Zamknięte','zakonczone'=>'Zakończone'];
$stMap        = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Traf./Chyb.'];
$status       = $competition['status'] ?? 'planowane';

$roleLabels = [
    'glowny'         => 'Sędzia główny',
    'liniowy'        => 'Sędzia liniowy',
    'obliczeniowy'   => 'Obliczeniowy',
    'bezpieczenstwa' => 'Bezpieczeństwo',
    'protokolant'    => 'Protokolant',
];
?>
<div class="text-center mb-4">
    <h2><?= e($clubName) ?></h2>
    <h3>PROTOKÓŁ ZAWODÓW</h3>
    <h4><?= e($competition['name']) ?></h4>
    <p class="mb-1">
        Data: <strong><?= format_date($competition['competition_date']) ?></strong>
        <?php if ($competition['location']): ?>
            &nbsp;|&nbsp; Miejsce: <strong><?= e($competition['location']) ?></strong>
        <?php endif; ?>
        &nbsp;|&nbsp; Status: <strong><?= $statusLabels[$status] ?? $status ?></strong>
    </p>
</div>

<?php if (empty($rankings)): ?>
    <p class="text-muted">Brak konkurencji lub wyników.</p>
<?php else: ?>
    <?php foreach ($rankings as $block): ?>
        <?php $ev = $block['event']; $rows = $block['results']; ?>
        <div class="mb-4" style="page-break-inside:avoid;">
            <h5 class="border-bottom pb-1 mb-2">
                <?= e($ev['name']) ?>
                <?php if ($ev['shots_count']): ?>
                    — <?= $ev['shots_count'] ?> strzałów
                <?php endif; ?>
                <span class="text-muted" style="font-size:.85em">(<?= $stMap[$ev['scoring_type']] ?? $ev['scoring_type'] ?>)</span>
            </h5>

            <?php if (empty($rows)): ?>
                <p class="text-muted small">Brak wyników.</p>
            <?php else: ?>
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr class="table-light">
                        <th style="width:50px" class="text-center">Miejsce</th>
                        <th>Nazwisko i imię</th>
                        <th style="width:55px" class="text-center">Nr</th>
                        <th style="width:80px">Klasa</th>
                        <th style="width:85px" class="text-center">Wynik</th>
                        <?php if ($ev['scoring_type'] === 'decimal'): ?>
                        <th style="width:45px" class="text-center" title="X-count">X</th>
                        <?php endif; ?>
                        <th style="width:70px">Broń</th>
                        <th>Uwagi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= $r['calc_place'] ?>.</td>
                        <td><?= e($r['last_name']) ?> <?= e($r['first_name']) ?></td>
                        <td class="text-center text-muted small"><?= e($r['member_number']) ?></td>
                        <td class="small">
                            <?= e($r['entry_class'] ?? '') ?>
                            <?php if ($r['member_class_code']): ?>
                                <?= e($r['member_class_code']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold">
                            <?= $r['score'] !== null
                                ? number_format((float)$r['score'], $ev['scoring_type'] === 'decimal' ? 2 : 0, ',', '')
                                : '—' ?>
                        </td>
                        <?php if ($ev['scoring_type'] === 'decimal'): ?>
                        <td class="text-center text-muted small"><?= $r['score_inner'] ?? '—' ?></td>
                        <?php endif; ?>
                        <td class="small text-muted">
                            <?= $r['weapon_type'] === 'klubowa' ? 'Klub.' : ($r['weapon_type'] ? 'Własna' : '—') ?>
                        </td>
                        <td class="small"><?= e($r['notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($judges)): ?>
<div class="mt-5" style="page-break-inside:avoid;">
    <h5 class="border-bottom pb-1 mb-2">Sędziowie</h5>
    <table class="table table-sm table-bordered mb-4">
        <thead>
            <tr class="table-light">
                <th>Funkcja</th>
                <th>Nazwisko i imię</th>
                <th>Klasa sędziowska</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($judges as $j): ?>
            <tr>
                <td><?= e($roleLabels[$j['role']] ?? $j['role']) ?></td>
                <td><?= e($j['last_name']) ?> <?= e($j['first_name']) ?></td>
                <td><?= $j['judge_class'] ? 'kl. ' . e($j['judge_class']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="mt-5 row" style="page-break-inside:avoid;">
    <div class="col-6">
        <p class="mb-5">Sędzia główny:</p>
        <div style="border-top:1px solid #000; width:80%;">&nbsp;</div>
        <p class="small text-muted">podpis</p>
    </div>
    <div class="col-6">
        <p class="mb-5">Protokolant:</p>
        <div style="border-top:1px solid #000; width:80%;">&nbsp;</div>
        <p class="small text-muted">podpis</p>
    </div>
</div>

<p class="text-muted small text-end mt-3">
    Protokół wygenerowany: <?= date('d.m.Y H:i') ?>
</p>
