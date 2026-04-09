<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; background: #fff; }
.cert-page {
    width: 190mm; height: 130mm;
    padding: 12mm 14mm;
    border: 3mm solid #1a3a6b;
    box-sizing: border-box;
    text-align: center;
    position: relative;
}
.cert-border-inner {
    border: 1mm solid #c8a000;
    padding: 8mm;
    height: calc(130mm - 26mm - 6mm);
}
.cert-header { font-size: 10pt; color: #555; text-transform: uppercase; letter-spacing: 2px; }
.cert-title { font-size: 22pt; font-weight: bold; color: #1a3a6b; margin: 4mm 0 2mm; }
.cert-subtitle { font-size: 9pt; color: #777; margin-bottom: 5mm; }
.cert-name { font-size: 16pt; font-weight: bold; color: #222; border-bottom: 0.5mm solid #ccc; display: inline-block; padding: 1mm 6mm; margin: 2mm 0; }
.cert-place { font-size: 11pt; color: #1a3a6b; margin: 3mm 0; }
.cert-place span { font-size: 18pt; font-weight: bold; color: #c8a000; }
.cert-comp { font-size: 9pt; color: #444; }
.cert-footer { margin-top: 5mm; display: flex; justify-content: space-between; font-size: 8pt; color: #888; }
.cert-sig { text-align: center; font-size: 8pt; }
.cert-sig-line { border-top: 0.4mm solid #888; width: 45mm; display: inline-block; }
.trophy-1 { color: #FFD700; } /* Gold */
.trophy-2 { color: #C0C0C0; } /* Silver */
.trophy-3 { color: #CD7F32; } /* Bronze */
</style>
</head>
<body>
<?php foreach ($certificates as $cert): ?>
<div class="cert-page">
    <div class="cert-border-inner">
        <div class="cert-header"><?= e($clubName) ?></div>
        <div class="cert-title">DYPLOM</div>
        <div class="cert-subtitle">Strzeleckie Zawody Sportowe</div>
        <p style="font-size:9pt;color:#444;margin:2mm 0">przyznany</p>
        <div class="cert-name"><?= e($cert['first_name']) ?> <?= e($cert['last_name']) ?></div>
        <div class="cert-place">
            za zajęcie miejsca
            <span class="trophy-<?= min($cert['place'],3) ?>"><?= (int)$cert['place'] ?>.</span>
            w kategorii <strong><?= e($cert['category']) ?></strong>
        </div>
        <div class="cert-comp">
            <strong><?= e($competition['name']) ?></strong><br>
            <?= e($competition['location'] ?? '') ?>, <?= date('d.m.Y', strtotime($competition['competition_date'])) ?>
        </div>
        <div class="cert-footer">
            <div class="cert-sig">
                <div class="cert-sig-line"></div><br>Organizator
            </div>
            <div style="color:#ccc;font-size:7pt;align-self:flex-end">
                Wygenerowano: <?= date('d.m.Y') ?>
            </div>
            <div class="cert-sig">
                <div class="cert-sig-line"></div><br>Sędzia Główny
            </div>
        </div>
    </div>
</div>
<?php if (!$loop->last ?? false): ?><pagebreak><?php endif; ?>
<?php endforeach; ?>
</body>
</html>
