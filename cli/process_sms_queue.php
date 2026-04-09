#!/usr/bin/env php
<?php
/**
 * SMS queue processor.
 * Cron: every 5 min — (asterisk)/5 * * * * php .../cli/process_sms_queue.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('STDIN_CLI', true);

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/app/autoload.php';
require ROOT_PATH . '/app/Helpers/helpers.php';

use App\Helpers\Database;
use App\Helpers\SmsService;

$db    = Database::getInstance();
$limit = (int)($argv[1] ?? 50);

$stmt = $db->prepare(
    "SELECT sq.*, cs.value AS api_key
     FROM sms_queue sq
     LEFT JOIN club_settings cs ON cs.club_id = sq.club_id AND cs.`key` = 'sms_api_key'
     WHERE sq.status = 'pending' AND sq.scheduled_at <= NOW()
     ORDER BY sq.scheduled_at ASC LIMIT ?"
);
$stmt->execute([$limit]);
$rows = $stmt->fetchAll();

$sent = $failed = 0;

foreach ($rows as $row) {
    $apiKey = $row['api_key'] ?? null;
    if (!$apiKey) {
        $db->prepare("UPDATE sms_queue SET status='failed', error='Brak klucza API SMS' WHERE id=?")->execute([$row['id']]);
        $failed++;
        continue;
    }

    // Get sender name
    $senderStmt = $db->prepare("SELECT value FROM club_settings WHERE club_id=? AND `key`='sms_sender' LIMIT 1");
    $senderStmt->execute([$row['club_id']]);
    $sender = $senderStmt->fetchColumn() ?: 'KlubStrzel';

    $ok  = SmsService::send($apiKey, $sender, $row['to_phone'], $row['message']);
    if ($ok) {
        $db->prepare("UPDATE sms_queue SET status='sent', sent_at=NOW() WHERE id=?")->execute([$row['id']]);
        $sent++;
    } else {
        $db->prepare("UPDATE sms_queue SET status='failed', error='Błąd API SMS' WHERE id=?")->execute([$row['id']]);
        $failed++;
    }
}

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] SMS processed: sent={$sent}, failed={$failed}" . PHP_EOL;
