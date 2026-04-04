<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title><?= e($title ?? 'Metryczki') ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        /* ── Ekranowe kontrolki ─────────────────────────────── */
        .no-print {
            padding: 8px 14px;
            background: #f0f0f0;
            border-bottom: 1px solid #ccc;
            display: flex;
            gap: 8px;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .no-print button { padding: 4px 14px; cursor: pointer; font-size: 10pt; }
        .no-print .info  { margin-left: auto; color: #666; font-size: 9pt; }

        /* ── Metryczka A5 pozioma ───────────────────────────── */
        .scorecard {
            width: 210mm;
            height: 143mm;            /* A5 landscape inner height (148mm - margins) */
            padding: 5mm 6mm 4mm;
            border: 0.3mm solid #aaa;
            page-break-after: always;
            break-after: page;
            display: grid;
            grid-template-rows: auto auto 1fr auto auto;
            gap: 2mm;
            margin: 5mm auto;
            background: #fff;
            overflow: hidden;
        }

        /* ── Nagłówek ────────────────────────────────────────── */
        .sc-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-bottom: 0.5mm solid #000;
            padding-bottom: 1.5mm;
            gap: 4mm;
        }
        .sc-competition {
            font-size: 10pt;
            font-weight: bold;
            flex: 1;
        }
        .sc-date-loc {
            font-size: 8pt;
            color: #444;
            text-align: right;
            white-space: nowrap;
        }

        /* ── Dane zawodnika ──────────────────────────────────── */
        .sc-member {
            display: flex;
            gap: 5mm;
            align-items: center;
            border: 0.4mm solid #333;
            padding: 1.5mm 3mm;
            border-radius: 1mm;
            background: #f8f8f8;
        }
        .sc-member-name {
            font-size: 11pt;
            font-weight: bold;
            flex: 1;
        }
        .sc-member-meta {
            display: flex;
            gap: 5mm;
            font-size: 8pt;
            color: #333;
        }
        .sc-member-meta span { white-space: nowrap; }
        .sc-lbl { color: #777; font-size: 7pt; text-transform: uppercase; }
        .sc-event-badge {
            background: #333;
            color: #fff;
            padding: 1mm 3mm;
            border-radius: 1mm;
            font-size: 8.5pt;
            font-weight: bold;
            white-space: nowrap;
        }

        /* ── Siatka strzałów ─────────────────────────────────── */
        .sc-shots-wrap { overflow: hidden; }
        .sc-shots-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            table-layout: fixed;
        }
        .sc-shots-table th, .sc-shots-table td {
            border: 0.25mm solid #555;
            text-align: center;
            padding: 0;
            height: 6.5mm;
        }
        .sc-shots-table thead th {
            background: #e0e0e0;
            font-size: 7pt;
            height: 5mm;
            font-weight: bold;
        }
        .th-ser, .td-ser {
            width: 12mm;
            background: #f0f0f0;
            font-weight: bold;
            font-size: 7pt;
        }
        .th-sum, .td-sum { width: 12mm; background: #e8f4e8; font-weight: bold; }
        .th-x,   .td-x   { width: 8mm;  background: #fff8e8; }
        .td-score         { width: auto; }
        .disabled-cell    { color: #ccc; font-size: 7pt; }

        /* Stopka tabeli — wynik całkowity */
        .sc-shots-table tfoot th,
        .sc-shots-table tfoot td {
            background: #d4ecd4;
            font-weight: bold;
            height: 7mm;
            font-size: 9pt;
            border: 0.3mm solid #444;
        }
        .tfoot-label {
            text-align: right;
            padding-right: 2mm;
            font-size: 8pt;
        }
        .tfoot-total { font-size: 11pt; }
        .tfoot-x     { font-size: 9pt; }

        /* ── Dolna belka: miejsce + uwagi ────────────────────── */
        .sc-bottom {
            display: flex;
            gap: 3mm;
            align-items: stretch;
        }
        .sc-place-box {
            width: 24mm;
            border: 0.5mm solid #555;
            border-radius: 1mm;
            padding: 1mm 2mm;
            text-align: center;
        }
        .sc-place-val { font-size: 14pt; font-weight: bold; min-height: 8mm; line-height: 8mm; }
        .sc-notes-box {
            flex: 1;
            border: 0.5mm solid #555;
            border-radius: 1mm;
            padding: 1mm 2mm;
            min-height: 10mm;
        }

        /* ── Podpisy ─────────────────────────────────────────── */
        .sc-signatures {
            display: flex;
            gap: 8mm;
        }
        .sc-sig { flex: 1; }
        .sc-sig-line  { border-top: 0.4mm solid #000; margin-bottom: 1mm; }
        .sc-sig-label { font-size: 7pt; color: #555; text-align: center; }

        /* ── Druk ────────────────────────────────────────────── */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .scorecard {
                border: none;
                margin: 0;
                padding: 4mm 5mm;
                width: 210mm;
                height: 148mm;
                page-break-after: always;
                break-after: page;
            }
            @page { size: A5 landscape; margin: 0; }
        }

        /* Podgląd ekranowy */
        @media screen {
            body { background: #888; padding-bottom: 10mm; }
            .scorecard {
                box-shadow: 0 2px 8px rgba(0,0,0,.4);
                border: 1px solid #bbb;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">&#128438; Drukuj / Zapisz jako PDF</button>
        <button onclick="window.history.back()">&#8592; Wróć do wyboru</button>
        <span class="info">
            A5 poziomo &mdash; <?= count($cards) ?> metryczek
            &mdash; <?= e($competition['name']) ?>
        </span>
    </div>
    <?= $content ?>
</body>
</html>
