#!/usr/bin/env php
<?php
/**
 * Demo cleanup — removes expired demo environments.
 * Cron: 0 (asterisk) (asterisk) (asterisk) (asterisk) php .../cli/cleanup_demos.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('STDIN_CLI', true);

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/autoload.php';
require ROOT_PATH . '/app/Helpers/helpers.php';

use App\Helpers\Database;
use App\Helpers\DemoSeeder;

$db = Database::getInstance();

$expired = $db->query(
    "SELECT id, name FROM clubs WHERE is_demo = 1 AND demo_expires_at IS NOT NULL AND demo_expires_at < NOW()"
)->fetchAll();

$removed = 0;
foreach ($expired as $demo) {
    DemoSeeder::destroy((int)$demo['id']);
    $removed++;
    echo "[" . date('Y-m-d H:i:s') . "] Removed demo: {$demo['name']} (id={$demo['id']})" . PHP_EOL;
}

echo "[" . date('Y-m-d H:i:s') . "] Demo cleanup done. Removed={$removed}" . PHP_EOL;
