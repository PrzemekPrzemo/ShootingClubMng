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

        // Reject invalid or header-injection-prone addresses
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // Strip any newlines from fields that go into headers
        $toEmail   = preg_replace('/[\r\n]/', '', $toEmail);
        $toName    = preg_replace('/[\r\n]/', '', $toName);
        $fromEmail = preg_replace('/[\r\n]/', '', $fromEmail);
        $fromName  = preg_replace('/[\r\n]/', '', $fromName);

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
