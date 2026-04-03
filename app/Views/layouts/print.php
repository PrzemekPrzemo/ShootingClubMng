<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Wydruk') ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            margin: 0;
            padding: 10mm;
            background: #fff;
        }
        h1 { font-size: 14pt; margin: 0 0 2mm; }
        h2 { font-size: 12pt; margin: 0 0 2mm; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4mm;
        }
        th, td {
            border: 1px solid #333;
            padding: 2mm 3mm;
            text-align: left;
            font-size: 10pt;
        }
        thead th {
            background: #eee;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .meta { color: #444; font-size: 10pt; margin-bottom: 3mm; }
        .signature-row { margin-top: 12mm; display: flex; gap: 20mm; }
        .signature-field { flex: 1; border-top: 1px solid #000; padding-top: 2mm; font-size: 9pt; color: #555; }
        @media print {
            body { padding: 5mm; }
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:6mm">
        <button onclick="window.print()" style="padding:4px 12px;cursor:pointer">🖨 Drukuj / Zapisz jako PDF</button>
        <button onclick="window.close()" style="padding:4px 12px;cursor:pointer;margin-left:6px">✕ Zamknij</button>
    </div>
    <?= $content ?>
</body>
</html>
