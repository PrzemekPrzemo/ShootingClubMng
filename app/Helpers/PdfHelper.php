<?php

namespace App\Helpers;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/**
 * Wrapper around mPDF for generating PDFs.
 */
class PdfHelper
{
    /**
     * Creates and returns a configured mPDF instance.
     *
     * @param string $format   Page format: 'A4', 'A5', 'A4-L' (landscape), etc.
     * @param array  $margins  [top, right, bottom, left, header, footer] in mm
     */
    public static function create(string $format = 'A4', array $margins = [15, 15, 15, 15]): Mpdf
    {
        $tempDir = ROOT_PATH . '/storage/pdf_tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode'           => 'utf-8',
            'format'         => $format,
            'margin_top'     => $margins[0] ?? 15,
            'margin_right'   => $margins[1] ?? 15,
            'margin_bottom'  => $margins[2] ?? 15,
            'margin_left'    => $margins[3] ?? 15,
            'tempDir'        => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
        ]);

        return $mpdf;
    }

    /**
     * Generates a PDF from an HTML string and sends it directly to browser.
     *
     * @param string $html     Full HTML content (no need for <html><body> wrapper)
     * @param string $filename Suggested download filename
     * @param string $format   mPDF page format
     * @param bool   $inline   true = show in browser, false = force download
     */
    public static function send(
        string $html,
        string $filename = 'dokument.pdf',
        string $format   = 'A4',
        bool   $inline   = true
    ): void {
        $mpdf = self::create($format);
        $mpdf->SetTitle(pathinfo($filename, PATHINFO_FILENAME));
        $mpdf->WriteHTML(self::wrapHtml($html));
        $dest = $inline ? 'I' : 'D';
        $mpdf->Output($filename, $dest);
        exit;
    }

    /**
     * Returns PDF as a binary string.
     */
    public static function toString(string $html, string $format = 'A4'): string
    {
        $mpdf = self::create($format);
        $mpdf->WriteHTML(self::wrapHtml($html));
        return $mpdf->Output('', 'S');
    }

    /**
     * Wraps the HTML fragment with minimal CSS reset for clean PDF output.
     */
    private static function wrapHtml(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10pt;
    color: #111;
    margin: 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}
th, td {
    padding: 3px 5px;
    vertical-align: top;
}
th {
    background: #343a40;
    color: #fff;
    font-weight: bold;
    text-align: left;
}
tr:nth-child(even) { background: #f8f8f8; }
.text-center { text-align: center; }
.text-right  { text-align: right; }
.text-muted  { color: #666; }
.fw-bold     { font-weight: bold; }
.border-bottom { border-bottom: 1pt solid #dee2e6; }
h1 { font-size: 16pt; margin: 0 0 4pt; }
h2 { font-size: 14pt; margin: 0 0 4pt; }
h3 { font-size: 12pt; margin: 0 0 4pt; }
h4 { font-size: 11pt; margin: 0 0 4pt; }
h5 { font-size: 10pt; margin: 8pt 0 3pt; border-bottom: 0.5pt solid #aaa; padding-bottom: 2pt; }
.signature-row td { border-top: 0.5pt solid #333; padding-top: 2pt; font-size: 8pt; color: #666; }
</style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }
}
