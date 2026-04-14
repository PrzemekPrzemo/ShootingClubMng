<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Lista startowa</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 10pt;
    color: #1a1a1a;
    margin: 0;
    padding: 0;
}
/* ── Header ─────────────────────────────────── */
.doc-header {
    border-bottom: 2.5pt solid #c0392b;
    padding-bottom: 6pt;
    margin-bottom: 12pt;
}
.doc-title {
    font-size: 16pt;
    font-weight: bold;
    color: #c0392b;
}
.doc-meta {
    font-size: 8.5pt;
    color: #555;
    margin-top: 3pt;
}
/* ── Conflict box ───────────────────────────── */
.conflict-box {
    background: #fff8e1;
    border: 1pt solid #f0ad4e;
    padding: 6pt 8pt;
    margin-bottom: 12pt;
    font-size: 9pt;
}
.conflict-box h3 {
    margin: 0 0 4pt;
    font-size: 9.5pt;
    color: #e07b00;
}
.conflict-box table {
    width: 100%;
    border-collapse: collapse;
}
.conflict-box td, .conflict-box th {
    border: 0.5pt solid #e0c060;
    padding: 2pt 4pt;
}
.conflict-box th { background: #fce8b0; font-weight: bold; }
/* ── Discipline section ─────────────────────── */
.disc-section {
    margin-bottom: 14pt;
    page-break-inside: avoid;
}
.disc-header {
    background: #2c3e50;
    color: #fff;
    padding: 4pt 8pt;
    font-size: 10pt;
    font-weight: bold;
}
.disc-code {
    font-family: DejaVu Sans Mono, Courier, monospace;
    font-size: 9pt;
    background: rgba(255,255,255,.18);
    padding: 1pt 4pt;
    margin-left: 6pt;
    border-radius: 2pt;
}
/* ── Relay block ────────────────────────────── */
.relay-block {
    margin-top: 0;
    page-break-inside: avoid;
}
.relay-header {
    background: #ecf0f1;
    padding: 3pt 8pt;
    font-size: 9pt;
    border-bottom: 0.5pt solid #bdc3c7;
    display: flex;
}
.relay-time {
    color: #c0392b;
    font-weight: bold;
    font-size: 10pt;
    margin-right: 10pt;
}
.relay-slot {
    font-weight: bold;
    margin-right: 10pt;
}
/* ── Entry table ────────────────────────────── */
table.entries {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}
table.entries th {
    background: #f8f9fa;
    border: 0.5pt solid #dee2e6;
    padding: 2pt 5pt;
    font-weight: bold;
    text-align: left;
}
table.entries td {
    border: 0.5pt solid #dee2e6;
    padding: 2.5pt 5pt;
}
table.entries tr:nth-child(even) td { background: #f9fbfc; }
.lane-num {
    font-weight: bold;
    text-align: center;
    color: #2c3e50;
    background: #e8edf2;
}
/* ── Footer ─────────────────────────────────── */
.doc-footer {
    border-top: 0.5pt solid #bdc3c7;
    padding-top: 4pt;
    font-size: 7.5pt;
    color: #888;
    text-align: right;
    margin-top: 16pt;
}
.badge-separate {
    font-size: 7.5pt;
    background: #17a2b8;
    color: #fff;
    padding: 1pt 4pt;
    border-radius: 3pt;
    margin-left: 6pt;
}
</style>
</head>
<body>

<!-- Document header -->
<div class="doc-header">
    <div class="doc-title">Lista startowa — <?= htmlspecialchars($generator['name']) ?></div>
    <div class="doc-meta">
        Data zawodów: <strong><?= htmlspecialchars($generator['start_date']) ?></strong>
        &nbsp;|&nbsp;
        Start: <strong><?= substr($generator['start_time'], 0, 5) ?></strong>
        &nbsp;|&nbsp;
        Przerwa między zmianami: <strong><?= (int)$generator['break_minutes'] ?> min</strong>
        <?php if (!empty($generator['competition_name'])): ?>
        &nbsp;|&nbsp; Zawody: <strong><?= htmlspecialchars($generator['competition_name']) ?></strong>
        <?php endif; ?>
        &nbsp;|&nbsp; Wygenerowano: <?= date('d.m.Y H:i') ?>
    </div>
</div>

<!-- Conflicts -->
<?php if (!empty($conflicts)): ?>
<div class="conflict-box">
    <h3>⚠ Ostrzeżenia: przerwa &lt; 40 min (<?= count($conflicts) ?>)</h3>
    <table>
        <tr>
            <th>Zawodnik</th>
            <th>Dyscyplina 1 (koniec)</th>
            <th>Dyscyplina 2 (start)</th>
            <th>Przerwa</th>
        </tr>
        <?php foreach ($conflicts as $cf): ?>
        <tr>
            <td><?= htmlspecialchars($cf['competitor_name']) ?></td>
            <td><?= htmlspecialchars($cf['discipline_a']) ?> do <?= htmlspecialchars($cf['end_a']) ?></td>
            <td><?= htmlspecialchars($cf['discipline_b']) ?> od <?= htmlspecialchars($cf['start_b']) ?></td>
            <td style="color:#c0392b; font-weight:bold"><?= $cf['gap_minutes'] ?> min</td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<!-- Schedule -->
<?php foreach ($schedule as $group):
    $disc      = $group['discipline'];
    $relayList = $group['relays'];
    $totalComp = array_sum(array_map(fn($r) => count($r['entries']), $relayList));
    $hasCombo  = !empty($relayList[0]['relay']['combo_id'] ?? null);
?>
<div class="disc-section">
    <div class="disc-header">
        <?= htmlspecialchars($disc['name']) ?>
        <span class="disc-code"><?= htmlspecialchars($disc['code']) ?></span>
        <?php if ($disc['gender_mode'] === 'separate'): ?>
            <span class="badge-separate">M / K osobno</span>
        <?php endif; ?>
        <span style="float:right; font-size:8.5pt; font-weight:normal">
            <?= count($relayList) ?> zmian | <?= $totalComp ?> zawodników
        </span>
    </div>

    <?php foreach ($relayList as $slot):
        $relay   = $slot['relay'];
        $entries = $slot['entries'];
        $startFmt = date('H:i', strtotime($relay['start_datetime']));
        $endFmt   = date('H:i', strtotime($relay['end_datetime']));
        $dateFmt  = date('d.m.Y', strtotime($relay['start_datetime']));
    ?>
    <div class="relay-block">
        <div class="relay-header">
            <span class="relay-slot">Zmiana <?= $relay['slot_index'] ?></span>
            <span class="relay-time"><?= $startFmt ?> &ndash; <?= $endFmt ?></span>
            <span style="color:#666"><?= $dateFmt ?></span>
            <span style="margin-left:auto; color:#555"><?= count($entries) ?> osób</span>
        </div>

        <?php if (!empty($entries)): ?>
        <table class="entries">
            <tr>
                <th style="width:3em">Stan.</th>
                <th>Nazwisko i imię</th>
                <th style="width:3em">Płeć</th>
                <?php if ($disc['gender_mode'] === 'separate' || $hasCombo): ?>
                <th style="width:6em">Dyscyplina</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td class="lane-num"><?= (int)$entry['lane'] ?></td>
                <td><?= htmlspecialchars($entry['last_name'] . ' ' . $entry['first_name']) ?></td>
                <td style="text-align:center"><?= htmlspecialchars($entry['gender'] ?? '') ?></td>
                <?php if ($disc['gender_mode'] === 'separate' || $hasCombo): ?>
                <td style="font-family: monospace"><?= htmlspecialchars($entry['discipline_code'] ?? $disc['code']) ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div style="padding:3pt 8pt; font-size:8.5pt; color:#888">Brak zawodników.</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<div class="doc-footer">
    Dokument wygenerowany przez system zarządzania klubem &mdash; <?= date('d.m.Y H:i') ?>
</div>
</body>
</html>
