<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Metryczki') ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ── Kontrolki ekranowe ─────────────────────────── */
        .no-print {
            padding: 8px 12px;
            background: #f0f0f0;
            border-bottom: 1px solid #ccc;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .no-print button {
            padding: 4px 14px;
            cursor: pointer;
            font-size: 10pt;
        }
        .no-print .info {
            margin-left: auto;
            color: #666;
            font-size: 9pt;
        }

        /* ── Metryczka A5 ───────────────────────────────── */
        .scorecard {
            width: 148mm;
            min-height: 205mm;
            padding: 6mm 7mm 5mm;
            border: 1px solid #ccc;
            page-break-after: always;
            break-after: page;
            display: flex;
            flex-direction: column;
            gap: 3mm;
            margin: 6mm auto;
            background: #fff;
        }

        /* Nagłówek zawodów */
        .sc-header {
            border-bottom: 0.5mm solid #000;
            padding-bottom: 2mm;
        }
        .sc-competition {
            font-size: 10.5pt;
            margin-bottom: 1mm;
        }
        .sc-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4mm;
            font-size: 8.5pt;
            color: #333;
        }

        /* Nagłówek konkurencji */
        .sc-event {
            background: #f0f0f0;
            border: 0.3mm solid #999;
            padding: 1.5mm 3mm;
            font-size: 10pt;
            border-radius: 1mm;
        }
        .sc-scoring {
            font-size: 8pt;
            color: #555;
        }

        /* Dane zawodnika */
        .sc-competitor {
            border: 0.5mm solid #333;
            padding: 2mm 3mm;
            border-radius: 1mm;
        }
        .sc-name {
            font-size: 11pt;
            margin-bottom: 1mm;
        }
        .sc-details {
            display: flex;
            flex-wrap: wrap;
            gap: 3mm;
            font-size: 8.5pt;
            color: #333;
        }

        /* Label */
        .sc-label {
            font-size: 7.5pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        /* Tabela strzałów */
        .sc-shots-section {
            flex: 1;
        }
        .sc-shots-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .sc-shots-table th,
        .sc-shots-table td {
            border: 0.3mm solid #555;
            text-align: center;
            padding: 0;
            height: 7mm;
            min-width: 6mm;
        }
        .sc-shots-table thead th {
            background: #e8e8e8;
            font-size: 7pt;
            height: 5mm;
        }
        .th-series, .td-series {
            width: 12mm !important;
            font-size: 7pt;
            background: #f5f5f5;
            font-weight: bold;
        }
        .th-sum, .td-sum,
        .th-x, .td-x {
            width: 10mm !important;
            background: #f0f8f0;
            font-weight: bold;
        }
        .td-score {
            width: 6.5mm !important;
        }
        .disabled {
            color: #bbb;
            font-size: 7pt;
        }

        /* Stopka tabeli: wynik całkowity */
        .sc-shots-table tfoot th,
        .sc-shots-table tfoot td {
            background: #e0e0e0;
            font-weight: bold;
            height: 8mm;
            font-size: 9pt;
        }
        .total-label {
            text-align: right;
            padding-right: 2mm;
            border: 0.3mm solid #555;
        }
        .td-total, .td-x-total {
            background: #d0ead0;
            font-size: 10pt;
            font-weight: bold;
        }

        /* Wariant bez liczby strzałów */
        .sc-plain-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sc-plain-table th,
        .sc-plain-table td {
            border: 0.3mm solid #555;
            padding: 2mm 3mm;
            height: 10mm;
        }
        .sc-plain-table th {
            background: #eee;
            font-size: 8pt;
            width: 20mm;
        }

        /* Miejsce + uwagi */
        .sc-footer-row {
            display: flex;
            gap: 3mm;
        }
        .sc-place-box {
            width: 28mm;
            border: 0.5mm solid #555;
            padding: 1.5mm 2mm;
            border-radius: 1mm;
        }
        .sc-place-value {
            height: 9mm;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            line-height: 9mm;
        }
        .sc-notes-box {
            flex: 1;
            border: 0.5mm solid #555;
            padding: 1.5mm 2mm;
            border-radius: 1mm;
        }
        .sc-notes-value {
            min-height: 9mm;
            font-size: 9pt;
        }

        /* Podpisy */
        .sc-signatures {
            display: flex;
            gap: 5mm;
            margin-top: auto;
            padding-top: 3mm;
        }
        .sc-sig-field {
            flex: 1;
        }
        .sc-sig-line {
            border-top: 0.4mm solid #000;
            margin-bottom: 1mm;
        }
        .sc-sig-label {
            font-size: 7pt;
            color: #555;
            text-align: center;
        }

        /* ── Druk ─────────────────────────────────────────── */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            .scorecard {
                border: none;
                margin: 0;
                padding: 5mm 6mm;
                width: 148mm;
                min-height: 210mm;
                page-break-after: always;
                break-after: page;
            }
            @page {
                size: A5 portrait;
                margin: 0;
            }
        }

        /* Podgląd ekranowy: szare tło między kartami */
        @media screen {
            body { background: #888; padding-bottom: 10mm; }
            .scorecard {
                box-shadow: 0 2px 8px rgba(0,0,0,0.35);
                border: 1px solid #bbb;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">&#128438; Drukuj / Zapisz jako PDF (A5)</button>
        <button onclick="window.close()">&#10005; Zamknij</button>
        <span class="info">Każda metryczka drukowana na osobnej kartce A5 &mdash; <?= e($title ?? '') ?></span>
    </div>
    <?= $content ?>
</body>
</html>
