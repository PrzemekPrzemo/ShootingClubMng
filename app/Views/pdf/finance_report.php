<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222; margin: 0; }
h1   { font-size: 14pt; color: #1a3a6b; }
h2   { font-size: 11pt; color: #1a3a6b; border-bottom: 0.5pt solid #ddd; padding-bottom: 2mm; }
table{ width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
th   { background: #1a3a6b; color: #fff; padding: 2mm 3mm; text-align: left; font-size: 9pt; }
td   { padding: 2mm 3mm; border-bottom: 0.3pt solid #eee; }
.total-row td { font-weight: bold; background: #f0f4ff; }
.right { text-align: right; }
.header-bar { background: #1a3a6b; color: #fff; padding: 4mm 6mm; margin-bottom: 6mm; }
.header-bar h1 { color: #fff; margin: 0; font-size: 16pt; }
.header-bar p  { margin: 1mm 0 0; font-size: 9pt; opacity: .8; }
.summary-box { background: #f8f9fa; border: 0.5pt solid #ddd; padding: 3mm 5mm; margin-bottom: 5mm; display: inline-block; }
.summary-box .big { font-size: 20pt; font-weight: bold; color: #1a3a6b; }
</style>
</head>
<body>

<div class="header-bar">
    <h1><?= e($clubName) ?></h1>
    <p>Raport finansowy za rok <?= (int)$year ?> &mdash; wygenerowany: <?= date('d.m.Y H:i') ?></p>
</div>

<div class="summary-box">
    <div>Łączne wpływy <?= (int)$year ?></div>
    <div class="big"><?= number_format((float)$total, 2, ',', ' ') ?> PLN</div>
</div>

<h2>Wpływy według miesięcy</h2>
<?php
$months = ['','Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec','Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];
?>
<table>
    <tr>
        <th>Miesiąc</th>
        <th class="right">Liczba transakcji</th>
        <th class="right">Kwota (PLN)</th>
    </tr>
    <?php foreach ($monthly as $row): ?>
    <tr>
        <td><?= $months[(int)$row['month']] ?></td>
        <td class="right"><?= (int)$row['count'] ?></td>
        <td class="right"><?= number_format((float)$row['total'], 2, ',', ' ') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-row">
        <td>SUMA</td>
        <td class="right"><?= array_sum(array_column($monthly, 'count')) ?></td>
        <td class="right"><?= number_format((float)$total, 2, ',', ' ') ?></td>
    </tr>
</table>

<h2>Wpływy według rodzaju płatności</h2>
<table>
    <tr>
        <th>Rodzaj płatności</th>
        <th class="right">Liczba</th>
        <th class="right">Kwota (PLN)</th>
        <th class="right">Udział %</th>
    </tr>
    <?php foreach ($byType as $row): ?>
    <tr>
        <td><?= e($row['type_name']) ?></td>
        <td class="right"><?= (int)$row['count'] ?></td>
        <td class="right"><?= number_format((float)$row['total'], 2, ',', ' ') ?></td>
        <td class="right"><?= $total > 0 ? round($row['total'] / $total * 100, 1) : 0 ?>%</td>
    </tr>
    <?php endforeach; ?>
</table>

<p style="font-size:7pt;color:#aaa;margin-top:10mm">
    Dokument wygenerowany automatycznie przez system ShootingClubMng.
    Nie stanowi dokumentu księgowego.
</p>
</body>
</html>
