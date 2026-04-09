#!/usr/bin/env php
<?php
/**
 * Email queue processor — run via cron every minute:
 *   * * * * * php /path/to/cli/process_queue.php >> /var/log/shootingclub_mail.log 2>&1
 */

define('ROOT_PATH', dirname(__DIR__));
define('STDIN_CLI', true);

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/autoload.php';
require ROOT_PATH . '/app/Helpers/helpers.php';

use App\Helpers\Database;
use App\Helpers\Mailer;
use App\Models\EmailQueueModel;

$db    = Database::getInstance();
$model = new EmailQueueModel();

$limit  = (int)($argv[1] ?? 30);
$rows   = $model->getPending($limit);
$sent   = 0;
$failed = 0;

foreach ($rows as $row) {
    try {
        $ok = Mailer::send(
            $row['to_email'],
            $row['to_name'] ?? '',
            $row['subject'],
            $row['body_html']
        );
        if ($ok) {
            $model->markSent((int)$row['id']);
            $sent++;
        } else {
            $model->markFailed((int)$row['id'], 'mail() returned false');
            $failed++;
        }
    } catch (\Throwable $e) {
        $model->markFailed((int)$row['id'], $e->getMessage());
        $failed++;
    }
}

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] Processed: sent={$sent}, failed={$failed}, skipped=" . (count($rows) - $sent - $failed) . PHP_EOL;
