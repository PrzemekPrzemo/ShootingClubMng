<?php

namespace App\Helpers;

/**
 * SMS sending via SMSAPI.pl REST API.
 *
 * Docs: https://www.smsapi.pl/rest-api
 * Requires: club_settings[sms_api_key], club_settings[sms_sender]
 */
class SmsService
{
    private const API_URL = 'https://api.smsapi.pl/sms.do';

    /**
     * Send a single SMS. Returns true on success.
     */
    public static function send(
        string $apiKey,
        string $sender,
        string $toPhone,
        string $message
    ): bool {
        $phone = self::normalizePhone($toPhone);
        if (!$phone) return false;

        $message = mb_substr(trim($message), 0, 459, 'UTF-8');
        if ($message === '') return false;

        $params = http_build_query([
            'access_token' => $apiKey,
            'to'           => $phone,
            'message'      => $message,
            'from'         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $sender), 0, 11) ?: 'Info',
            'format'       => 'json',
        ]);

        try {
            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($params),
                'content' => $params,
                'timeout' => 8,
                'ignore_errors' => true,
            ]]);
            $resp = @file_get_contents(self::API_URL, false, $ctx);
            if ($resp === false) return false;

            $json = json_decode($resp, true);
            // SMSAPI returns {"list":[{"status":"QUEUE",...}]} on success
            return isset($json['list'][0]['status']) && !str_starts_with($json['list'][0]['status'], 'ERROR');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send a test SMS (for settings page).
     */
    public static function sendTest(string $apiKey, string $sender, string $toPhone): bool
    {
        return self::send($apiKey, $sender, $toPhone, 'Test SMS — ShootingClubMng. Jeśli widzisz tę wiadomość, konfiguracja działa poprawnie.');
    }

    /**
     * Queue an SMS for a given club (saves to sms_queue table).
     */
    public static function queue(int $clubId, string $toPhone, string $message, string $type = 'general', ?string $toName = null): void
    {
        $phone = self::normalizePhone($toPhone);
        if (!$phone) return;

        try {
            Database::getInstance()->prepare(
                "INSERT INTO sms_queue (club_id, to_phone, to_name, message, type) VALUES (?,?,?,?,?)"
            )->execute([$clubId, $phone, $toName, mb_substr($message, 0, 459, 'UTF-8'), $type]);
        } catch (\Throwable) {}
    }

    /**
     * Normalize a phone number to Polish +48 format.
     * Accepts: 48XXXXXXXXX, +48XXXXXXXXX, XXXXXXXXX (9 digits).
     */
    public static function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+48') && strlen($phone) === 12) {
            return substr($phone, 1); // Remove +
        }
        if (str_starts_with($phone, '48') && strlen($phone) === 11) {
            return $phone;
        }
        if (strlen($phone) === 9 && ctype_digit($phone)) {
            return '48' . $phone;
        }
        return null; // Invalid
    }
}
