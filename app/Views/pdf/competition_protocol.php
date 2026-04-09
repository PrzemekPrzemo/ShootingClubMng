<?php
$statusLabels = ['planowane'=>'Planowane','otwarte'=>'Otwarte','zamkniete'=>'Zamknięte','zakonczone'=>'Zakończone'];
$stMap        = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Traf./Chyb.'];
$status       = $competition['status'] ?? 'planowane';
$roleLabels   = [
    'glowny'         => 'Sędzia główny',
    'liniowy'        => 'Sędzia liniowy',
    'obliczeniowy'   => 'Obliczeniowy',
    'bezpieczenstwa' => 'Bezpieczeństwo',
    'protokolant'    => 'Protokolant',
];
?>
<div style="text-align:center;margin-bottom:12pt">
    <div style="font-size:13pt;font-weight:bold"><?= htmlspecialchars($clubName) ?></div>
    <div style="font-size:16pt;font-weight:bold;margin:4pt 0">PROTOKÓŁ ZAWODÓW</div>
    <div style="font-size:12pt;font-weight:bold"><?= htmlspecialchars($competition['name']) ?></div>
    <div style="font-size:9pt;color:#555;margin-top:4pt">
        Data: <strong><?= htmlspecialchars(date('d.m.Y', strtotime($competition['competition_date']))) ?></strong>
        <?php if ($competition['location']): ?>
            &nbsp;|&nbsp; Miejsce: <strong><?= htmlspecialchars($competition['location']) ?></strong>
        <?php endif; ?>
        &nbsp;|&nbsp; Status: <strong><?= $statusLabels[$status] ?? $status ?></strong>
    </div>
    <div style="border-bottom:1pt solid #333;margin-top:8pt"></div>
</div>

<?php if (empty($rankings)): ?>
    <p style="color:#666">Brak konkurencji lub wyników.</p>
<?php else: ?>
    <?php foreach ($rankings as $block): ?>
        <?php $ev = $block['event']; $rows = $block['results']; ?>
        <div style="margin-bottom:14pt;page-break-inside:avoid">
            <h5>
                <?= htmlspecialchars($ev['name']) ?>
                <?php if ($ev['shots_count']): ?>
                    — <?= (int)$ev['shots_count'] ?> strzałów
                <?php endif; ?>
                <span style="font-weight:normal;font-size:8pt;color:#666">(<?= $stMap[$ev['scoring_type']] ?? $ev['scoring_type'] ?>)</span>
            </h5>

            <?php if (empty($rows)): ?>
                <p style="color:#666;font-size:8pt">Brak wyników.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:8%;text-align:center">Msc.</th>
                        <th style="width:28%">Nazwisko i imię</th>
                        <th style="width:10%;text-align:center">Nr</th>
                        <th style="width:10%;text-align:center">Klasa</th>
                        <th style="width:12%;text-align:center">Wynik</th>
                        <?php if ($ev['scoring_type'] === 'decimal'): ?>
                        <th style="width:8%;text-align:center" title="X-count">X</th>
                        <?php endif; ?>
                        <th style="width:10%">Broń</th>
                        <th>Uwagi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td style="text-align:center;font-weight:bold"><?= (int)$r['calc_place'] ?>.</td>
                        <td><?= htmlspecialchars($r['last_name'] . ' ' . $r['first_name']) ?></td>
                        <td style="text-align:center;color:#666;font-size:8pt"><?= htmlspecialchars($r['member_number'] ?? '') ?></td>
                        <td style="text-align:center;font-size:8pt">
                            <?= htmlspecialchars(($r['entry_class'] ?? '') . ($r['member_class_code'] ? ' ' . $r['member_class_code'] : '')) ?>
                        </td>
                        <td style="text-align:center;font-weight:bold">
                            <?= $r['score'] !== null
                                ? number_format((float)$r['score'], $ev['scoring_type'] === 'decimal' ? 2 : 0, ',', '')
                                : '—' ?>
                        </td>
                        <?php if ($ev['scoring_type'] === 'decimal'): ?>
                        <td style="text-align:center;color:#666;font-size:8pt"><?= $r['score_inner'] ?? '—' ?></td>
                        <?php endif; ?>
                        <td style="font-size:8pt;color:#666">
                            <?= $r['weapon_type'] === 'klubowa' ? 'Klub.' : ($r['weapon_type'] ? 'Własna' : '—') ?>
                        </td>
                        <td style="font-size:8pt"><?= htmlspecialchars($r['notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($judges)): ?>
<div style="margin-top:18pt;page-break-inside:avoid">
    <h5>Sędziowie</h5>
    <table>
        <thead>
            <tr>
                <th style="width:35%">Funkcja</th>
                <th style="width:40%">Nazwisko i imię</th>
                <th>Klasa sędziowska</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($judges as $j): ?>
            <tr>
                <td><?= htmlspecialchars($roleLabels[$j['role']] ?? $j['role']) ?></td>
                <td><?= htmlspecialchars($j['last_name'] . ' ' . $j['first_name']) ?></td>
                <td><?= $j['judge_class'] ? 'kl. ' . htmlspecialchars($j['judge_class']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div style="margin-top:30pt;page-break-inside:avoid">
    <table style="border:none">
        <tr>
            <td style="width:50%;padding:0 20pt 0 0;background:none">
                <div>Sędzia główny:</div>
                <div style="border-top:0.5pt solid #333;margin-top:25pt;padding-top:3pt;font-size:8pt;color:#666">podpis</div>
            </td>
            <td style="width:50%;background:none">
                <div>Protokolant:</div>
                <div style="border-top:0.5pt solid #333;margin-top:25pt;padding-top:3pt;font-size:8pt;color:#666">podpis</div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top:12pt;text-align:right;font-size:7pt;color:#999">
    Protokół wygenerowany: <?= date('d.m.Y H:i') ?>
</div>
