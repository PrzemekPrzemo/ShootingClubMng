<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Legitymacja — <?= e($member['last_name']) ?> <?= e($member['first_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .member-card {
            width: 74mm;
            min-height: 105mm;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 8mm;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            position: relative;
            overflow: hidden;
        }
        .member-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 8mm;
            background: #dc3545;
        }
        .card-club-name {
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #fff;
            position: absolute;
            top: 2.5mm;
            left: 8mm;
            right: 8mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-heading {
            margin-top: 7mm;
            text-align: center;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #333;
            border-bottom: 0.5pt solid #dc3545;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        .card-member-number {
            text-align: center;
            font-size: 20pt;
            font-weight: 900;
            color: #dc3545;
            letter-spacing: .05em;
            line-height: 1;
            margin-bottom: 1mm;
        }
        .card-full-name {
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            color: #111;
            margin-bottom: 1mm;
        }
        .card-meta {
            text-align: center;
            font-size: 7.5pt;
            color: #555;
            margin-bottom: 3mm;
        }
        .card-divider {
            border: none;
            border-top: 0.5pt solid #e0e0e0;
            margin: 2mm 0;
        }
        .card-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #888;
        }
        .card-value {
            font-size: 8pt;
            color: #222;
            font-weight: 600;
        }
        .disciplines-list {
            font-size: 7.5pt;
            color: #333;
        }
        .qr-placeholder {
            width: 18mm;
            height: 18mm;
            border: 1pt solid #ccc;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6pt;
            color: #999;
            text-align: center;
            line-height: 1.3;
            padding: 1mm;
        }
        .signature-line {
            border-top: 0.5pt solid #333;
            margin-top: 3mm;
            padding-top: 1mm;
            font-size: 6.5pt;
            color: #888;
            text-align: center;
        }
        .card-issue-date {
            font-size: 6.5pt;
            color: #888;
            text-align: right;
        }

        @media screen {
            .print-controls {
                display: block;
            }
        }
        @media print {
            body { background: #fff; }
            .print-controls { display: none !important; }
            .member-card {
                box-shadow: none;
                border: 0.5pt solid #ccc;
                margin: 0 auto;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

<!-- Print controls (screen only) -->
<div class="print-controls container py-3">
    <div class="d-flex gap-2 align-items-center mb-3">
        <a href="<?= url('members/' . $member['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Wróć do zawodnika
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-danger">
            <i class="bi bi-printer"></i> Drukuj legitymację
        </button>
        <span class="text-muted small">Zalecany rozmiar papieru: A7 (74×105 mm) lub wytnij z A4</span>
    </div>
</div>

<!-- Member card -->
<div class="d-flex justify-content-center py-2">
<div class="member-card">
    <!-- Red top bar with club name -->
    <div class="card-club-name"><?= e($clubName ?? 'Klub Strzelecki') ?></div>

    <div class="card-heading">Legitymacja Zawodnicza</div>

    <!-- Member number -->
    <div class="card-member-number"><?= e($member['member_number']) ?></div>

    <!-- Full name -->
    <div class="card-full-name"><?= e($member['last_name']) ?> <?= e($member['first_name']) ?></div>

    <!-- Type + age category -->
    <div class="card-meta">
        <?= e(ucfirst($member['member_type'] ?? '')) ?>
        <?php if (!empty($member['age_category_name'])): ?>
            &bull; <?= e($member['age_category_name']) ?>
        <?php endif; ?>
    </div>

    <hr class="card-divider">

    <!-- Disciplines and QR side by side -->
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="flex-grow-1">
            <div class="card-label">Dyscypliny</div>
            <?php if (!empty($disciplines)): ?>
                <ul class="disciplines-list ps-3 mb-0">
                    <?php foreach ($disciplines as $d): ?>
                        <li><?= e($d['discipline_name']) ?>
                            <?php if (!empty($d['class'])): ?>
                                <span style="color:#888">(<?= e($d['class']) ?>)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-value text-muted">—</div>
            <?php endif; ?>
        </div>
        <div class="qr-placeholder flex-shrink-0">
            <div>
                <i class="bi bi-qr-code" style="font-size:14pt;color:#555"></i><br>
                weryfikacja<br>online
            </div>
        </div>
    </div>

    <hr class="card-divider">

    <!-- Bottom: issue date + signature -->
    <div class="d-flex justify-content-between align-items-end">
        <div>
            <div class="card-label">Nr licencji PZSS</div>
            <div class="card-value"><?= e($license['license_number'] ?? '—') ?></div>
            <?php if (!empty($license['valid_until'])): ?>
                <div class="card-label" style="margin-top:1mm">Ważna do</div>
                <div class="card-value"><?= format_date($license['valid_until']) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div class="card-issue-date">
                Wystawiono: <?= date('d.m.Y') ?>
            </div>
            <div class="signature-line" style="width:28mm">
                Podpis prezesa
            </div>
        </div>
    </div>

    <!-- Verify URL -->
    <div style="font-size:5.5pt;color:#bbb;text-align:center;margin-top:2mm;word-break:break-all">
        <?= e(($verifyUrl ?? '') ?: 'Weryfikacja: ' . ($_SERVER['HTTP_HOST'] ?? '') . '/verify/' . $member['member_number']) ?>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
