#!/usr/bin/env php
<?php
/**
 * Generates automatic reminders and queues them for sending.
 * Run once per day (e.g. 06:00):
 *   0 6 * * * php /path/to/cli/queue_reminders.php >> /var/log/shootingclub_reminders.log 2>&1
 */

define('ROOT_PATH', dirname(__DIR__));
define('STDIN_CLI', true);

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/autoload.php';
require ROOT_PATH . '/app/Helpers/helpers.php';

use App\Models\EmailQueueModel;
use App\Models\ClubModel;

$model     = new EmailQueueModel();
$clubModel = new ClubModel();
$clubs     = $clubModel->getActive();

$total = ['comp' => 0, 'pay' => 0, 'lic' => 0, 'med' => 0];

foreach ($clubs as $club) {
    // Set club scope for scoped models
    \App\Helpers\ClubContext::set((int)$club['id']);

    $total['comp'] += $model->queueCompetitionReminders(7);
    $total['pay']  += $model->queuePaymentReminders();
    $total['lic']  += $model->queueLicenseReminders(30);
    $total['med']  += $model->queueMedicalReminders(30);
}

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] Queued: competitions={$total['comp']}, payments={$total['pay']}, licenses={$total['lic']}, medical={$total['med']}" . PHP_EOL;
