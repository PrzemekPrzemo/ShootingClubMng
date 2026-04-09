<?php
$primaryColor = '#dc3545';
$disciplineList = $disciplines ?? [];
$lic = $license ?? null;
?>
<div style="width:74mm;min-height:105mm;border:0.5pt solid #ccc;padding:0;font-family:DejaVu Sans,sans-serif;margin:0 auto">
    <!-- Top bar -->
    <div style="background:<?= $primaryColor ?>;color:#fff;padding:3mm 5mm;font-size:7pt;font-weight:bold;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;overflow:hidden">
        <?= htmlspecialchars($clubName) ?>
    </div>

    <div style="padding:3mm 4mm">
        <!-- Heading -->
        <div style="text-align:center;font-size:8pt;font-weight:bold;text-transform:uppercase;letter-spacing:0.08em;color:#333;border-bottom:0.5pt solid <?= $primaryColor ?>;padding-bottom:2mm;margin-bottom:3mm">
            Legitymacja Zawodnicza
        </div>

        <!-- Member number -->
        <div style="text-align:center;font-size:18pt;font-weight:bold;color:<?= $primaryColor ?>;line-height:1;margin-bottom:1mm">
            <?= htmlspecialchars($member['member_number'] ?? '') ?>
        </div>

        <!-- Full name -->
        <div style="text-align:center;font-size:11pt;font-weight:bold;color:#111;margin-bottom:1mm">
            <?= htmlspecialchars($member['last_name'] . ' ' . $member['first_name']) ?>
        </div>

        <!-- Type + category -->
        <div style="text-align:center;font-size:7.5pt;color:#555;margin-bottom:3mm">
            <?= htmlspecialchars(ucfirst($member['member_type'] ?? '')) ?>
            <?php if (!empty($member['age_category_name'])): ?>
                &bull; <?= htmlspecialchars($member['age_category_name']) ?>
            <?php endif; ?>
        </div>

        <div style="border-top:0.5pt solid #e0e0e0;margin:2mm 0"></div>

        <!-- Disciplines -->
        <div style="margin-bottom:3mm">
            <div style="font-size:6.5pt;text-transform:uppercase;letter-spacing:0.05em;color:#888;margin-bottom:1mm">Dyscypliny</div>
            <?php if (!empty($disciplineList)): ?>
                <?php foreach ($disciplineList as $d): ?>
                    <div style="font-size:8pt;color:#222">
                        <?= htmlspecialchars($d['discipline_name']) ?>
                        <?php if (!empty($d['class'])): ?>
                            <span style="color:#888">(<?= htmlspecialchars($d['class']) ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="font-size:8pt;color:#aaa">—</div>
            <?php endif; ?>
        </div>

        <div style="border-top:0.5pt solid #e0e0e0;margin:2mm 0"></div>

        <!-- License + issue date -->
        <div style="display:table;width:100%">
            <div style="display:table-cell;vertical-align:bottom">
                <div style="font-size:6.5pt;text-transform:uppercase;color:#888">Nr licencji PZSS</div>
                <div style="font-size:8.5pt;font-weight:bold;color:#222">
                    <?= htmlspecialchars($lic['license_number'] ?? '—') ?>
                </div>
                <?php if (!empty($lic['valid_until'])): ?>
                    <div style="font-size:6.5pt;text-transform:uppercase;color:#888;margin-top:1mm">Ważna do</div>
                    <div style="font-size:8.5pt;font-weight:bold;color:#222">
                        <?= date('d.m.Y', strtotime($lic['valid_until'])) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="display:table-cell;vertical-align:bottom;text-align:right">
                <div style="font-size:6.5pt;color:#888">Wystawiono: <?= date('d.m.Y') ?></div>
                <div style="border-top:0.5pt solid #333;width:28mm;margin-top:8mm;padding-top:1mm;font-size:6.5pt;color:#888;text-align:center;display:inline-block">Podpis prezesa</div>
            </div>
        </div>
    </div>
</div>
