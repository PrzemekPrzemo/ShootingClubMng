<?php

namespace App\Helpers;

use App\Models\SettingModel;

class Mailer
{
    /**
     * Sends a single email via PHP mail() with proper UTF-8 / HTML headers.
     * Returns true on success.
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $fromEmail = '',
        string $fromName  = ''
    ): bool {
        if (empty($fromEmail)) {
            $settings  = new SettingModel();
            $fromEmail = $settings->get('mail_from_email', '');
            $fromName  = $settings->get('mail_from_name', $fromName);
        }

        if (empty($fromEmail)) {
            return false;
        }

        $toEncoded      = self::encodeHeader($toName) . ' <' . $toEmail . '>';
        $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $fromEncoded = empty($fromName)
            ? $fromEmail
            : self::encodeHeader($fromName) . ' <' . $fromEmail . '>';

        $headers  = "From: {$fromEncoded}\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "X-Mailer: ShootingClubMng\r\n";

        $encodedBody = chunk_split(base64_encode($bodyHtml));

        return mail($toEncoded, $subjectEncoded, $encodedBody, $headers);
    }

    private static function encodeHeader(string $text): string
    {
        if (empty($text)) return '';
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
}
