<?php
$h = 'htmlspecialchars';
?>
<div style="text-align:center;margin-bottom:10pt">
    <div style="font-size:13pt;font-weight:bold"><?= $h($clubName) ?></div>
    <div style="font-size:15pt;font-weight:bold;margin:4pt 0">EWIDENCJA BRONI OSOBISTEJ ZAWODNIKÓW</div>
    <div style="font-size:8pt;color:#555">Wygenerowano: <?= $h($generatedAt) ?></div>
    <div style="border-bottom:1.5pt solid #222;margin-top:6pt"></div>
</div>

<?php if (empty($rows)): ?>
<p style="color:#666">Brak zawodników z zarejestrowaną aktywną bronią.</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:8pt">
    <thead>
        <tr style="background:#1E2838;color:#ffffff">
            <th style="border:0.5pt solid #444;padding:4pt 3pt;text-align:center;width:4%">L.p.</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;width:14%">Nazwisko i Imię</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;text-align:center;width:10%">PESEL</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;text-align:center;width:11%">Nr pozwolenia</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;text-align:center;width:9%">Rodzaj broni</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;text-align:center;width:7%">Kaliber</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;width:20%">Producent / nazwa broni</th>
            <th style="border:0.5pt solid #444;padding:4pt 3pt;width:13%">Numer broni</th>
        </tr>
    </thead>
    <tbody>
        <?php $lp = 0; foreach ($rows as $row): $lp++; ?>
        <?php $bg = ($lp % 2 === 0) ? '#f5f5f5' : '#ffffff'; ?>
        <tr style="background:<?= $bg ?>;vertical-align:top">
            <td style="border:0.5pt solid #ccc;padding:3pt;text-align:center"><?= $lp ?></td>
            <td style="border:0.5pt solid #ccc;padding:3pt;font-weight:bold">
                <?= $h($row['last_name']) ?> <?= $h($row['first_name']) ?>
            </td>
            <td style="border:0.5pt solid #ccc;padding:3pt;text-align:center;font-family:monospace">
                <?= $h($row['pesel'] ?? '—') ?>
            </td>
            <td style="border:0.5pt solid #ccc;padding:3pt;text-align:center;font-size:7.5pt">
                <?php
                // Per-weapon permit takes precedence, fall back to member permit
                $permit = $row['mw_permit'] ?: ($row['firearm_permit_number'] ?? '');
                echo $permit ? $h($permit) : '—';
                ?>
            </td>
            <td style="border:0.5pt solid #ccc;padding:3pt;text-align:center">
                <?= $h($typeLabels[$row['type']] ?? ucfirst($row['type'])) ?>
            </td>
            <td style="border:0.5pt solid #ccc;padding:3pt;text-align:center">
                <?= $row['caliber'] ? $h($row['caliber']) : '—' ?>
            </td>
            <td style="border:0.5pt solid #ccc;padding:3pt">
                <?php if ($row['manufacturer']): ?>
                <span style="color:#555"><?= $h($row['manufacturer']) ?> /</span>
                <?php endif; ?>
                <?= $h($row['weapon_name']) ?>
            </td>
            <td style="border:0.5pt solid #ccc;padding:3pt;font-family:monospace;font-size:7.5pt">
                <?= $row['serial_number'] ? $h($row['serial_number']) : '—' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div style="margin-top:8pt;font-size:7.5pt;color:#666;text-align:right">
    Łącznie pozycji: <?= count($rows) ?>
</div>
<?php endif; ?>
