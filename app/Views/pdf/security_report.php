<?php
$levelColors = ['critical' => '#dc3545', 'warning' => '#fd7e14', 'info' => '#0dcaf0'];
$levelLabels = ['critical' => 'KRYTYCZNY', 'warning' => 'OSTRZEŻENIE', 'info' => 'INFO'];
$h = 'htmlspecialchars';
?>
<div style="text-align:center;margin-bottom:10pt">
    <div style="font-size:15pt;font-weight:bold">RAPORT AUDYTU BEZPIECZEŃSTWA</div>
    <div style="font-size:9pt;color:#555;margin-top:3pt">
        Wygenerowano: <?= $h($timestamp) ?> &nbsp;|&nbsp; PHP <?= $h($phpVer) ?>
    </div>
    <div style="border-bottom:1.5pt solid #222;margin-top:6pt"></div>
</div>

<!-- Score summary -->
<table style="width:100%;border-collapse:collapse;margin-bottom:12pt;font-size:9pt">
    <tr>
        <td style="width:25%;text-align:center;padding:6pt;border:0.5pt solid #ccc;background:#f8f9fa">
            <div style="font-size:22pt;font-weight:bold;color:<?= $score['critical']>0?'#dc3545':($score['pct']>=80?'#198754':'#fd7e14') ?>">
                <?= $score['pct'] ?>%
            </div>
            <div style="color:#666">Ogólny wynik</div>
        </td>
        <td style="width:25%;text-align:center;padding:6pt;border:0.5pt solid #ccc">
            <div style="font-size:18pt;font-weight:bold;color:#dc3545"><?= $score['critical'] ?></div>
            <div style="color:#666">Krytyczne</div>
        </td>
        <td style="width:25%;text-align:center;padding:6pt;border:0.5pt solid #ccc">
            <div style="font-size:18pt;font-weight:bold;color:#fd7e14"><?= $score['warnings'] ?></div>
            <div style="color:#666">Ostrzeżenia</div>
        </td>
        <td style="width:25%;text-align:center;padding:6pt;border:0.5pt solid #ccc;background:#f8f9fa">
            <div style="font-size:18pt;font-weight:bold;color:#198754"><?= $score['passed'] ?>/<?= $score['total'] ?></div>
            <div style="color:#666">Zaliczone</div>
        </td>
    </tr>
</table>

<?php foreach ($checks as $groupName => $items): ?>
<?php $fails = array_filter($items, fn($c) => !$c['pass']); ?>
<div style="margin-bottom:10pt;page-break-inside:avoid">
    <div style="background:#1E2838;color:#fff;padding:4pt 6pt;font-weight:bold;font-size:9pt">
        <?= $h($groupName) ?>
        <span style="float:right;font-weight:normal">
            <?= count($items)-count($fails) ?>/<?= count($items) ?> OK
        </span>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:8pt">
        <?php foreach ($items as $item): ?>
        <tr style="background:<?= $item['pass'] ? '#ffffff' : '#fff8f8' ?>">
            <td style="border:0.5pt solid #ddd;padding:3pt 5pt;width:5%;text-align:center">
                <?php if ($item['pass']): ?>
                <span style="color:#198754;font-weight:bold">✓</span>
                <?php else: ?>
                <span style="color:<?= $levelColors[$item['level']] ?? '#999' ?>;font-weight:bold">✗</span>
                <?php endif; ?>
            </td>
            <td style="border:0.5pt solid #ddd;padding:3pt 5pt;width:35%">
                <?= $h($item['name']) ?>
            </td>
            <td style="border:0.5pt solid #ddd;padding:3pt 5pt;color:#555">
                <?= $item['pass'] ? '<span style="color:#198754">Zaliczone</span>' : $h($item['suggestion']) ?>
            </td>
            <td style="border:0.5pt solid #ddd;padding:3pt 5pt;width:12%;text-align:center">
                <?php if (!$item['pass']): ?>
                <span style="background:<?= $levelColors[$item['level']] ?? '#999' ?>;color:#fff;padding:1pt 4pt;font-size:7pt;border-radius:2pt">
                    <?= $levelLabels[$item['level']] ?? strtoupper($item['level']) ?>
                </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endforeach; ?>

<div style="margin-top:10pt;font-size:7.5pt;color:#888;border-top:0.5pt solid #ddd;padding-top:4pt">
    Analiza statyczna lokalna — bez połączeń zewnętrznych. Wyniki należy zweryfikować ręcznie.
</div>
