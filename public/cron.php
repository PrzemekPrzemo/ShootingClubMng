<?php
/**
 * HTTP cron endpoint — for shared hosting without shell access.
 * Protect with a secret token in the URL:
 *   https://system.example.com/cron.php?token=YOUR_SECRET_TOKEN&job=queue
 *
 * Add to cPanel cron:
 *   * * * * *   curl -s "https://system.example.com/cron.php?token=SECRET&job=queue" > /dev/null
 *   0 6 * * *   curl -s "https://system.example.com/cron.php?token=SECRET&job=reminders" > /dev/null
 */

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/autoload.php';
require ROOT_PATH . '/app/Helpers/helpers.php';

use App\Helpers\Database;
use App\Models\SettingModel;

// ── Token verification ────────────────────────────────────────────────────
$settings   = new SettingModel();
$cronSecret = $settings->get('cron_secret', '');

if ($cronSecret === '' || !isset($_GET['token']) || !hash_equals($cronSecret, $_GET['token'])) {
    http_response_code(403);
    exit('Forbidden');
}

// ── Dispatch ──────────────────────────────────────────────────────────────
$job = $_GET['job'] ?? 'queue';
header('Content-Type: text/plain; charset=utf-8');

switch ($job) {
    case 'queue':
        require ROOT_PATH . '/cli/process_queue.php';
        break;

    case 'reminders':
        require ROOT_PATH . '/cli/queue_reminders.php';
        break;

    case 'sms':
        require ROOT_PATH . '/cli/process_sms_queue.php';
        break;

    default:
        echo "Unknown job: {$job}\n";
        http_response_code(400);
}
