<?php
// ============================================================
// Application configuration
// ============================================================

return [
    'app_name'    => 'Klub Strzelecki',
    'app_version' => '1.0.0',
    'debug'       => false,   // set true locally, false on production
    'timezone'    => 'Europe/Warsaw',
    'locale'      => 'pl_PL',
    'base_url'    => '',      // auto-detected in Router; override if needed

    // Session
    'session_lifetime' => 7200,  // seconds

    // Paths
    'root_path'   => dirname(__DIR__),
    'view_path'   => dirname(__DIR__) . '/app/Views',
    'upload_path' => dirname(__DIR__) . '/public/uploads',
];
