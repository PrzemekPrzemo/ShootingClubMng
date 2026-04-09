<?php

namespace App\Helpers;

use App\Models\ClubSettingsModel;
use App\Models\SettingModel;

/**
 * Serwis wysyłki e-mail z routingiem SMTP per klub.
 *
 * Priorytet:
 * 1. SMTP klubu (jeśli club_settings.smtp_enabled = 1)
 * 2. Globalny SMTP (settings table)
 * 3. PHP mail() jako fallback
 */
class EmailService
{
    /**
     * Wyślij e-mail z automatycznym wyborem SMTP.
     *
     * @param int    $clubId  ID klubu (dla wyboru konfiguracji SMTP)
     * @param string $to      Adres odbiorcy
     * @param string $toName  Nazwa odbiorcy
     * @param string $subject Temat wiadomości
     * @param string $html    Treść HTML
     * @return bool           true jeśli wysłano (lub mail() zwróciło true)
     */
    public static function send(int $clubId, string $to, string $toName, string $subject, string $html): bool
    {
        $config = self::resolveSmtpConfig($clubId);

        if ($config === null) {
            // Brak konfiguracji SMTP — użyj PHP mail() jako fallback
            return self::sendViaMail($to, $subject, $html, $config);
        }

        return self::sendViaSmtp($config, $to, $toName, $subject, $html);
    }

    /**
     * Rozwiąż konfigurację SMTP: najpierw per-klub, potem globalna.
     * Zwraca null jeśli brak konfiguracji.
     */
    private static function resolveSmtpConfig(int $clubId): ?array
    {
        // 1. Sprawdź SMTP klubu
        $clubSettings = new ClubSettingsModel();
        $clubSmtp = $clubSettings->getSmtpConfig($clubId);

        if ($clubSmtp !== null && !empty($clubSmtp['host'])) {
            return $clubSmtp;
        }

        // 2. Globalny SMTP
        $settingModel = new SettingModel();
        $host = $settingModel->get('smtp_host', '');

        if (empty($host)) {
            return null;
        }

        return [
            'host'       => $host,
            'port'       => (int)$settingModel->get('smtp_port', 587),
            'secure'     => $settingModel->get('smtp_secure', 'tls'),
            'user'       => $settingModel->get('smtp_user', ''),
            'pass_enc'   => $settingModel->get('smtp_pass_enc', ''),
            'from_email' => $settingModel->get('mail_from_email', ''),
            'from_name'  => $settingModel->get('mail_from_name', ''),
        ];
    }

    /**
     * Wyślij przez SMTP (socket-based, bez zależności zewnętrznych).
     */
    private static function sendViaSmtp(array $cfg, string $to, string $toName, string $subject, string $html): bool
    {
        $host    = $cfg['host'];
        $port    = $cfg['port'] ?: 587;
        $secure  = $cfg['secure'] ?? 'tls';
        $user    = $cfg['user'] ?? '';
        $pass    = $cfg['pass_enc'] ?? '';
        $from    = $cfg['from_email'] ?? 'noreply@system.pl';
        $fromN   = $cfg['from_name']  ?? 'System';

        // Prefer PHPMailer if available
        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::sendViaPhpMailer($cfg, $to, $toName, $subject, $html);
        }

        // Simple SMTP via fsockopen
        $prefix = match ($secure) {
            'ssl'   => 'ssl://',
            'tls'   => '',
            default => '',
        };

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$fp) {
            self::log("SMTP connect failed: {$errstr} ({$errno})");
            return false;
        }

        try {
            stream_set_timeout($fp, 15);
            self::smtpRead($fp); // banner

            self::smtpCmd($fp, "EHLO " . gethostname());

            // STARTTLS for tls mode
            if ($secure === 'tls') {
                self::smtpCmd($fp, "STARTTLS");
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    self::log("SMTP STARTTLS failed");
                    return false;
                }
                self::smtpCmd($fp, "EHLO " . gethostname());
            }

            // AUTH LOGIN
            if ($user !== '') {
                self::smtpCmd($fp, "AUTH LOGIN");
                self::smtpCmd($fp, base64_encode($user));
                self::smtpCmd($fp, base64_encode($pass));
            }

            self::smtpCmd($fp, "MAIL FROM:<{$from}>");
            self::smtpCmd($fp, "RCPT TO:<{$to}>");
            self::smtpCmd($fp, "DATA");

            $boundary = md5(uniqid());
            $headers  = "From: =?UTF-8?B?" . base64_encode($fromN) . "?= <{$from}>\r\n";
            $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";

            $body = $headers . "\r\n" . chunk_split(base64_encode($html)) . "\r\n.\r\n";
            fwrite($fp, $body);
            self::smtpRead($fp);

            self::smtpCmd($fp, "QUIT");
            return true;
        } catch (\Throwable $e) {
            self::log("SMTP error: " . $e->getMessage());
            return false;
        } finally {
            @fclose($fp);
        }
    }

    /** PHPMailer integration (when available via composer). */
    private static function sendViaPhpMailer(array $cfg, string $to, string $toName, string $subject, string $html): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet  = 'UTF-8';
            $mail->Host     = $cfg['host'];
            $mail->Port     = $cfg['port'] ?: 587;
            $mail->SMTPAuth = !empty($cfg['user']);
            $mail->Username = $cfg['user'] ?? '';
            $mail->Password = $cfg['pass_enc'] ?? '';
            $mail->SMTPSecure = match ($cfg['secure'] ?? 'tls') {
                'ssl' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
                'tls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };

            $mail->setFrom($cfg['from_email'] ?? 'noreply@system.pl', $cfg['from_name'] ?? 'System');
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;

            return $mail->send();
        } catch (\Throwable $e) {
            self::log("PHPMailer error: " . $e->getMessage());
            return false;
        }
    }

    /** Fallback: PHP mail(). */
    private static function sendViaMail(string $to, string $subject, string $html, ?array $cfg): bool
    {
        $from = $cfg['from_email'] ?? 'noreply@system.pl';
        $headers  = "From: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return @mail($to, $subject, $html, $headers);
    }

    private static function smtpCmd($fp, string $cmd): string
    {
        fwrite($fp, $cmd . "\r\n");
        return self::smtpRead($fp);
    }

    private static function smtpRead($fp): string
    {
        $data = '';
        while ($line = fgets($fp, 512)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    }

    private static function log(string $msg): void
    {
        $logDir = ROOT_PATH . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @error_log("[" . date('Y-m-d H:i:s') . "] [EmailService] {$msg}\n", 3, $logDir . '/email.log');
    }
}
