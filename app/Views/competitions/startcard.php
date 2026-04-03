<?php
$stMap = ['decimal' => 'dziesiętna', 'integer' => 'całkowita', 'hit_miss' => 'trafiony/chybiony'];
?>
<h1><?= e($competition['name']) ?></h1>
<div class="meta">
    <strong>Data:</strong> <?= format_date($competition['competition_date']) ?>
    &nbsp;|&nbsp;
    <strong>Miejsce:</strong> <?= e($competition['location'] ?? '—') ?>
</div>

<h2>Metryczka startowa: <?= e($event['name']) ?></h2>
<div class="meta">
    <?php if ($event['shots_count']): ?>
        <strong>Liczba strzałów:</strong> <?= $event['shots_count'] ?>
        &nbsp;|&nbsp;
    <?php endif; ?>
    <strong>Punktacja:</strong> <?= $stMap[$event['scoring_type']] ?? $event['scoring_type'] ?>
</div>

<table>
    <thead>
        <tr>
            <th class="text-center" style="width:8mm">Nr</th>
            <th>Zawodnik</th>
            <th>Nr leg.</th>
            <th>Klasa</th>
            <th>Kategoria wiekowa</th>
            <th>Grupa / Godz.</th>
            <th style="width:30mm">Wynik</th>
            <th style="width:20mm">Miejsce</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($entries as $i => $entry): ?>
        <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td><?= e($entry['last_name']) ?> <?= e($entry['first_name']) ?></td>
            <td><?= e($entry['member_number']) ?></td>
            <td>
                <?= e($entry['class'] ?? '') ?>
                <?php if (!empty($entry['member_class_name'] ?? '')): ?>
                    / <?= e($entry['member_class_name']) ?>
                <?php endif; ?>
            </td>
            <td><?= e($entry['age_category_name'] ?? '—') ?></td>
            <td>
                <?= e($entry['group_name'] ?? '—') ?>
                <?php if (!empty($entry['group_start_time'])): ?>
                    <br><small><?= substr($entry['group_start_time'], 11, 5) ?></small>
                <?php endif; ?>
            </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($entries)): ?>
        <tr><td colspan="8" class="text-center">Brak zgłoszonych zawodników.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="signature-row">
    <div class="signature-field">Podpis sędziego głównego</div>
    <div class="signature-field">Podpis osoby wprowadzającej wyniki</div>
    <div class="signature-field">Data i miejsce</div>
</div>
