<?php

namespace App\Helpers;

/**
 * Simple AES-256-CBC encrypt/decrypt helper.
 * Key is derived from 'encryption_key' in config/app.php.
 * Each encrypted value stores a fresh 16-byte IV prepended to the ciphertext,
 * then base64-encoded so it is safe to store in a VARCHAR column.
 */
class Crypto
{
    private const CIPHER = 'AES-256-CBC';

    private static function key(): string
    {
        static $key = null;
        if ($key === null) {
            $cfg = require ROOT_PATH . '/config/app.php';
            // Derive a 32-byte key from whatever string is in config
            $key = substr(hash('sha256', $cfg['encryption_key'] ?? 'shootero_default_key_change_me'), 0, 32);
        }
        return $key;
    }

    /**
     * Encrypt a plain-text string. Returns a base64 string safe for VARCHAR storage.
     * Returns empty string if $plain is empty.
     */
    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $iv     = random_bytes(16);
        $cipher = openssl_encrypt($plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    /**
     * Decrypt a value previously returned by encrypt().
     * Returns null if the input is empty, malformed, or decryption fails.
     */
    public static function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }
        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) <= 16) {
            return null;
        }
        $iv     = substr($data, 0, 16);
        $cipher = substr($data, 16);
        $plain  = openssl_decrypt($cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }
}
