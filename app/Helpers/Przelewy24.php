<?php

namespace App\Helpers;

/**
 * Przelewy24 REST API v1 wrapper.
 *
 * Per-club config keys (stored in club_settings):
 *   p24_enabled     boolean
 *   p24_merchant_id int     — Merchant ID from P24 panel
 *   p24_pos_id      int     — POS ID (often same as merchant_id)
 *   p24_api_key     text    — API key from P24 panel
 *   p24_crc_key     text    — CRC key for signature verification
 *   p24_sandbox     boolean — use sandbox environment
 *
 * Flow:
 *   1. registerTransaction() → returns redirect URL + token
 *   2. Redirect user to P24 payment page
 *   3. P24 posts notification to urlStatus endpoint
 *   4. verifyNotification() validates the signature
 *   5. verifyTransaction()  confirms the payment with P24 API
 */
class Przelewy24
{
    private const URL_PROD    = 'https://secure.przelewy24.pl';
    private const URL_SANDBOX = 'https://sandbox.przelewy24.pl';

    private string $baseUrl;
    private int    $merchantId;
    private int    $posId;
    private string $apiKey;
    private string $crcKey;

    public function __construct(array $config)
    {
        $sandbox          = (bool)($config['sandbox'] ?? true);
        $this->baseUrl    = $sandbox ? self::URL_SANDBOX : self::URL_PROD;
        $this->merchantId = (int)($config['merchant_id'] ?? 0);
        $this->posId      = (int)($config['pos_id'] ?: $this->merchantId);
        $this->apiKey     = (string)($config['api_key'] ?? '');
        $this->crcKey     = (string)($config['crc_key'] ?? '');
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Register a transaction with P24.
     *
     * @param  array $params {
     *   sessionId   string  — unique ID for this transaction (your reference)
     *   amount      int     — amount in grosze (PLN × 100)
     *   description string
     *   email       string  — payer email
     *   firstName   string
     *   lastName    string
     *   urlReturn   string  — redirect after payment
     *   urlStatus   string  — P24 webhook endpoint
     * }
     * @return array { token: string, redirectUrl: string }
     * @throws \RuntimeException on API error
     */
    public function registerTransaction(array $params): array
    {
        $sessionId = $params['sessionId'];
        $amount    = (int)$params['amount'];

        $body = [
            'merchantId'  => $this->merchantId,
            'posId'       => $this->posId,
            'sessionId'   => $sessionId,
            'amount'      => $amount,
            'currency'    => 'PLN',
            'description' => mb_substr($params['description'] ?? 'Opłata', 0, 1024),
            'email'       => $params['email'] ?? '',
            'client'      => trim(($params['firstName'] ?? '') . ' ' . ($params['lastName'] ?? '')),
            'firstName'   => $params['firstName'] ?? '',
            'lastName'    => $params['lastName']  ?? '',
            'country'     => 'PL',
            'language'    => 'pl',
            'urlReturn'   => $params['urlReturn'],
            'urlStatus'   => $params['urlStatus'],
            'encoding'    => 'UTF-8',
            'sign'        => $this->signRegister($sessionId, $amount),
        ];

        $response = $this->apiPost('/api/v1/transaction/register', $body);

        $token = $response['data']['token'] ?? '';
        if ($token === '') {
            throw new \RuntimeException('P24: brak tokenu w odpowiedzi rejestracji transakcji.');
        }

        return [
            'token'       => $token,
            'redirectUrl' => $this->baseUrl . '/trnRequest/' . $token,
        ];
    }

    /**
     * Verify transaction with P24 after receiving notification.
     *
     * @throws \RuntimeException on API error
     */
    public function verifyTransaction(string $sessionId, int $orderId, int $amount): bool
    {
        $body = [
            'merchantId' => $this->merchantId,
            'posId'      => $this->posId,
            'sessionId'  => $sessionId,
            'amount'     => $amount,
            'currency'   => 'PLN',
            'orderId'    => $orderId,
            'sign'       => $this->signVerify($sessionId, $orderId, $amount),
        ];

        $response = $this->apiPut('/api/v1/transaction/verify', $body);
        return ($response['data']['status'] ?? '') === 'success';
    }

    /**
     * Validate the signature of a P24 webhook notification.
     */
    public function verifyNotification(array $post): bool
    {
        $expected = $this->signNotification(
            $post['sessionId']    ?? '',
            (int)($post['amount'] ?? 0),
            (int)($post['originAmount'] ?? 0),
            $post['currency']     ?? 'PLN',
            (int)($post['orderId'] ?? 0),
            (int)($post['methodId'] ?? 0),
            $post['statement']    ?? ''
        );
        return hash_equals($expected, $post['sign'] ?? '');
    }

    // ── Signature helpers ─────────────────────────────────────────────────────

    private function signRegister(string $sessionId, int $amount): string
    {
        return hash('sha384', json_encode([
            'sessionId'  => $sessionId,
            'merchantId' => $this->merchantId,
            'amount'     => $amount,
            'currency'   => 'PLN',
            'crc'        => $this->crcKey,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function signVerify(string $sessionId, int $orderId, int $amount): string
    {
        return hash('sha384', json_encode([
            'sessionId'  => $sessionId,
            'orderId'    => $orderId,
            'amount'     => $amount,
            'currency'   => 'PLN',
            'crc'        => $this->crcKey,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function signNotification(
        string $sessionId,
        int    $amount,
        int    $originAmount,
        string $currency,
        int    $orderId,
        int    $methodId,
        string $statement
    ): string {
        return hash('sha384', json_encode([
            'merchantId'   => $this->merchantId,
            'posId'        => $this->posId,
            'sessionId'    => $sessionId,
            'amount'       => $amount,
            'originAmount' => $originAmount,
            'currency'     => $currency,
            'orderId'      => $orderId,
            'methodId'     => $methodId,
            'statement'    => $statement,
            'crc'          => $this->crcKey,
        ], JSON_UNESCAPED_UNICODE));
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function apiPost(string $endpoint, array $body): array
    {
        return $this->apiCall('POST', $endpoint, $body);
    }

    private function apiPut(string $endpoint, array $body): array
    {
        return $this->apiCall('PUT', $endpoint, $body);
    }

    private function apiCall(string $method, string $endpoint, array $body): array
    {
        $url  = $this->baseUrl . $endpoint;
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_USERPWD        => $this->posId . ':' . $this->apiKey,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException("P24: błąd cURL — $err");
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException("P24: nieprawidłowa odpowiedź JSON (HTTP $code).");
        }

        if (($decoded['responseCode'] ?? 0) !== 0) {
            $msg = $decoded['error'] ?? 'Błąd P24';
            throw new \RuntimeException("P24 API error [{$decoded['responseCode']}]: $msg");
        }

        return $decoded;
    }

    // ── Static factory ────────────────────────────────────────────────────────

    /**
     * Build instance from club_settings for a given club.
     * Returns null if P24 is not enabled or not configured.
     */
    public static function forClub(int $clubId): ?self
    {
        $settings = new \App\Models\ClubSettingsModel();

        if (!(bool)$settings->get($clubId, 'p24_enabled', false)) {
            return null;
        }

        $merchantId = (int)$settings->get($clubId, 'p24_merchant_id', 0);
        $apiKey     = (string)$settings->get($clubId, 'p24_api_key', '');
        $crcKey     = (string)$settings->get($clubId, 'p24_crc_key', '');

        if ($merchantId === 0 || $apiKey === '' || $crcKey === '') {
            return null;
        }

        return new self([
            'merchant_id' => $merchantId,
            'pos_id'      => (int)$settings->get($clubId, 'p24_pos_id', $merchantId),
            'api_key'     => $apiKey,
            'crc_key'     => $crcKey,
            'sandbox'     => (bool)$settings->get($clubId, 'p24_sandbox', true),
        ]);
    }
}
