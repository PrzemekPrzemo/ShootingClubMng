<?php

namespace App\Helpers;

/**
 * TOTP (RFC 6238) implementation without external libraries.
 * Compatible with Google Authenticator, Authy, etc.
 */
class Totp
{
    private const PERIOD  = 30;
    private const DIGITS  = 6;
    private const WINDOW  = 1; // Accept 1 step before/after (clock skew)
    private const CHARS   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32

    /**
     * Generate a random Base32-encoded secret (160-bit).
     */
    public static function generateSecret(): string
    {
        $bytes  = random_bytes(20);
        $secret = '';
        $chars  = self::CHARS;
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[ord($bytes[$i % 20]) % 32];
        }
        return $secret;
    }

    /**
     * Compute TOTP for given secret at given timestamp.
     */
    public static function compute(string $secret, int $timestamp = 0): string
    {
        if ($timestamp === 0) $timestamp = time();
        $counter   = (int)floor($timestamp / self::PERIOD);
        $key       = self::base32Decode($secret);
        $time      = pack('N*', 0) . pack('N*', $counter);
        $hash      = hash_hmac('sha1', $time, $key, true);
        $offset    = ord($hash[19]) & 0x0F;
        $code      = ((ord($hash[$offset]) & 0x7F) << 24)
                   | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                   | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                   |  (ord($hash[$offset + 3]) & 0xFF);
        $code      = $code % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a user-provided code against the secret (with clock-skew window).
     */
    public static function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s/', '', $code);
        if (strlen($code) !== self::DIGITS) return false;
        $now = time();
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $expected = self::compute($secret, $now + $i * self::PERIOD);
            if (hash_equals($expected, $code)) return true;
        }
        return false;
    }

    /**
     * Build an otpauth:// URL for QR code generation.
     */
    public static function otpauthUrl(string $secret, string $account, string $issuer = 'ShootingClubMng'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
             . '?secret=' . $secret
             . '&issuer=' . rawurlencode($issuer)
             . '&digits=' . self::DIGITS
             . '&period=' . self::PERIOD;
    }

    /**
     * Return data URI of a QR code PNG using Google Charts API (optional visual aid).
     * Gracefully returns empty string if cURL/file_get_contents not available.
     */
    public static function qrCodeUrl(string $otpauthUrl): string
    {
        $size = '200x200';
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . '&data=' . rawurlencode($otpauthUrl);
    }

    // ── Base32 decoder ────────────────────────────────────────────────

    private static function base32Decode(string $input): string
    {
        $input    = strtoupper($input);
        $charmap  = array_flip(str_split(self::CHARS));
        $output   = '';
        $buffer   = 0;
        $bitsLeft = 0;

        foreach (str_split($input) as $char) {
            if (!isset($charmap[$char])) continue;
            $buffer   = ($buffer << 5) | $charmap[$char];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output   .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}
