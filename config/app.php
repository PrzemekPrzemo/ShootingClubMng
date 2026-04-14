<?php
// ============================================================
// Application configuration
// ============================================================

return [
    'app_name'    => 'Shootero',
    'app_version' => '1.0.0',
    'debug'       => false,   // set true locally, false on production
    'timezone'    => 'Europe/Warsaw',
    'locale'      => 'pl_PL',
    'base_url'    => '',      // auto-detected in Router; override if needed

    // Encryption key — used by App\Helpers\Crypto for AES-256-CBC field encryption.
    // CHANGE THIS on every installation. Min 32 random characters recommended.
    'encryption_key' => getenv('APP_ENCRYPTION_KEY') ?: 'ShooteroDefaultKey2024!ChangeMe!',

    // Session
    'session_lifetime' => 7200,  // seconds

    // Paths
    'root_path'   => dirname(__DIR__),
    'view_path'   => dirname(__DIR__) . '/app/Views',
    'upload_path' => dirname(__DIR__) . '/public/uploads',
];
