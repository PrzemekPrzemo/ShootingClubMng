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

        /* ── Metryczka A5 pozioma — pełna strona ────────────── */
        .scorecard {
            width: 210mm;
            height: 148mm;
            padding: 4mm 5mm;
            page-break-after: always;
            break-after: page;
            display: grid;
            grid-template-rows: auto auto 1fr auto auto;
            gap: 2mm;
            background: #fff;
            overflow: hidden;
        }

        /* ── Nagłówek ────────────────────────────────────────── */
        .sc-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-bottom: 0.5mm solid #000;
            padding-bottom: 1mm;
            gap: 4mm;
        }
        .sc-competition {
            font-size: 10.5pt;
            font-weight: bold;
            flex: 1;
        }
        .sc-date-loc {
            font-size: 8.5pt;
            color: #333;
            text-align: right;
            white-space: nowrap;
        }

        /* ── Dane zawodnika ──────────────────────────────────── */
        .sc-member {
            border: 0.4mm solid #333;
            padding: 1.5mm 3mm;
            border-radius: 1mm;
            background: #f5f5f5;
        }
        .sc-member-row1 {
            display: flex;
            align-items: baseline;
            gap: 4mm;
            margin-bottom: 1mm;
        }
        .sc-member-name {
            font-size: 12pt;
            font-weight: bold;
            flex: 1;
        }
        .sc-event-badge {
            background: #222;
            color: #fff;
            padding: 1mm 3mm;
            border-radius: 1mm;
            font-size: 8.5pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .sc-member-row2 {
            display: flex;
            gap: 6mm;
            align-items: center;
            font-size: 8pt;
            color: #333;
            flex-wrap: wrap;
            margin-bottom: 0.5mm;
        }
        .sc-member-row2 .item { white-space: nowrap; }
        .sc-member-row2 .sep  { color: #aaa; }
        .sc-member-row3 {
            display: flex;
            gap: 6mm;
            font-size: 7.5pt;
            color: #555;
            flex-wrap: wrap;
        }
        .sc-lbl { color: #888; font-size: 6.5pt; text-transform: uppercase; margin-right: 1mm; }

        /* ── Siatka strzałów ─────────────────────────────────── */
        .sc-shots-wrap {
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .sc-shots-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            table-layout: fixed;
            flex: 1;
        }
        .sc-shots-table th, .sc-shots-table td {
            border: 0.25mm solid #555;
            text-align: center;
            padding: 0;
        }
        /* Header row */
        .sc-shots-table thead th {
            background: #ddd;
            font-size: 7.5pt;
            height: 5.5mm;
            font-weight: bold;
        }
        /* Data rows — tall for comfortable writing */
        .sc-shots-table tbody td,
        .sc-shots-table tbody th {
            height: 9.5mm;
        }
        /* Footer row */
        .sc-shots-table tfoot th,
        .sc-shots-table tfoot td {
            background: #d0ecd0;
            font-weight: bold;
            height: 8.5mm;
            font-size: 9.5pt;
            border: 0.3mm solid #444;
        }

        .th-ser, .td-ser {
            width: 14mm;
            background: #ebebeb;
            font-weight: bold;
            font-size: 7pt;
        }
        .th-sum, .td-sum { width: 14mm; background: #e0f0e0; font-weight: bold; }
        .th-x,   .td-x   { width: 9mm;  background: #fff8e0; }
        .td-score         { width: auto; }
        .disabled-cell    { background: #f0f0f0; color: #bbb; font-size: 7pt; }

        .tfoot-label { text-align: right; padding-right: 2mm; font-size: 8pt; }
        .tfoot-total { font-size: 12pt; }
        .tfoot-x     { font-size: 9pt;  }

        /* ── Dolna belka: miejsce + uwagi ────────────────────── */
        .sc-bottom {
            display: flex;
            gap: 3mm;
            align-items: stretch;
            min-height: 14mm;
        }
        .sc-place-box {
            width: 22mm;
            border: 0.5mm solid #555;
            border-radius: 1mm;
            padding: 1mm 2mm;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .sc-place-val { font-size: 16pt; font-weight: bold; }
        .sc-notes-box {
            flex: 1;
            border: 0.5mm solid #555;
            border-radius: 1mm;
            padding: 1.5mm 2mm;
        }

        /* ── Podpisy — dużo miejsca ──────────────────────────── */
        .sc-signatures {
            display: flex;
            gap: 6mm;
            align-items: flex-end;
            min-height: 18mm;
            padding-top: 4mm;
        }
        .sc-sig { flex: 1; }
        .sc-sig-line  { border-top: 0.4mm solid #000; margin-bottom: 1.5mm; }
        .sc-sig-label { font-size: 7.5pt; color: #444; text-align: center; }

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

        /* ── Podgląd ekranowy ────────────────────────────────── */
        @media screen {
            body { background: #888; padding-bottom: 10mm; }
            .scorecard {
                box-shadow: 0 2px 10px rgba(0,0,0,.5);
                border: 1px solid #bbb;
                margin: 6mm auto;
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
