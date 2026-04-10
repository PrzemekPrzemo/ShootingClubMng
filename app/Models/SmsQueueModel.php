<?php

namespace App\Models;

use App\Helpers\ClubContext;

/**
 * SMS queue model — mirrors EmailQueueModel for SMS notifications.
 * Requires sms_queue table (migration_v27.sql).
 */
class SmsQueueModel extends BaseModel
{
    protected string $table = 'sms_queue';

    // ── Core queue operations ─────────────────────────────────────────────────

    public function enqueue(array $data): void
    {
        $clubId = $data['club_id'] ?? ClubContext::current();
        if ($clubId === null) return;

        try {
            $this->db->prepare(
                "INSERT INTO sms_queue (club_id, to_phone, to_name, message, type, scheduled_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            )->execute([
                (int)$clubId,
                $data['to_phone'],
                $data['to_name'] ?? null,
                mb_substr(trim($data['message']), 0, 459, 'UTF-8'),
                $data['type'] ?? 'general',
            ]);
        } catch (\PDOException) {
            // SMS queuing failure is non-fatal
        }
    }

    public function getPending(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM sms_queue WHERE status = 'pending' AND scheduled_at <= NOW()
                 ORDER BY scheduled_at ASC LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function markSent(int $id): void
    {
        $this->db->prepare("UPDATE sms_queue SET status='sent', sent_at=NOW() WHERE id=?")->execute([$id]);
    }

    public function markFailed(int $id, string $error = ''): void
    {
        $this->db->prepare("UPDATE sms_queue SET status='failed', error=? WHERE id=?")->execute([
            mb_substr($error, 0, 255),
            $id,
        ]);
    }

    // ── Reminder generators ───────────────────────────────────────────────────

    /**
     * Queue SMS reminders for upcoming competitions (members who have phone numbers).
     * Only if club has sms_enabled=1 in club_settings.
     */
    public function queueCompetitionReminders(int $days = 3): int
    {
        $clubId = ClubContext::current();
        if ($clubId === null || !$this->isSmsEnabled($clubId)) return 0;

        try {
            $stmt = $this->db->prepare("
                SELECT c.id AS competition_id, c.name AS competition_name,
                       c.competition_date, c.location,
                       m.id AS member_id, m.first_name, m.last_name, m.phone
                FROM competitions c
                JOIN competition_entries ce ON ce.competition_id = c.id
                JOIN members m ON m.id = ce.member_id
                WHERE c.club_id = ?
                  AND c.status IN ('otwarte','planowane')
                  AND c.competition_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND m.phone IS NOT NULL AND m.phone != ''
                  AND NOT EXISTS (
                      SELECT 1 FROM sms_queue sq
                      WHERE sq.type = 'competition_reminder'
                        AND sq.to_phone = m.phone
                        AND sq.message LIKE CONCAT('%', c.name, '%')
                        AND sq.status != 'failed'
                  )
            ");
            $stmt->execute([$clubId, $days]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $date = date('d.m.Y', strtotime($row['competition_date']));
            $msg  = "Przypomnienie: zawody \"{$row['competition_name']}\" odbędą się {$date}"
                  . ($row['location'] ? " ({$row['location']})" : '') . ". Powodzenia!";
            $this->enqueue([
                'club_id'  => $clubId,
                'to_phone' => $row['phone'],
                'to_name'  => $row['first_name'] . ' ' . $row['last_name'],
                'message'  => $msg,
                'type'     => 'competition_reminder',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Queue SMS reminders for expiring licenses.
     */
    public function queueLicenseReminders(int $days = 14): int
    {
        $clubId = ClubContext::current();
        if ($clubId === null || !$this->isSmsEnabled($clubId)) return 0;

        try {
            $stmt = $this->db->prepare("
                SELECT m.first_name, m.last_name, m.phone,
                       l.valid_until, DATEDIFF(l.valid_until, CURDATE()) AS days_left
                FROM licenses l
                JOIN members m ON m.id = l.member_id
                WHERE m.club_id = ?
                  AND l.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND m.phone IS NOT NULL AND m.phone != ''
                  AND NOT EXISTS (
                      SELECT 1 FROM sms_queue sq
                      WHERE sq.type = 'license_expiry'
                        AND sq.to_phone = m.phone
                        AND YEAR(sq.created_at) = YEAR(CURDATE())
                        AND sq.status != 'failed'
                  )
            ");
            $stmt->execute([$clubId, $days]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $daysLeft = (int)$row['days_left'];
            $until    = date('d.m.Y', strtotime($row['valid_until']));
            $msg = "Twoja licencja wygasa za {$daysLeft} dni ({$until}). Odnów ją jak najszybciej w biurze klubu.";
            $this->enqueue([
                'club_id'  => $clubId,
                'to_phone' => $row['phone'],
                'to_name'  => $row['first_name'] . ' ' . $row['last_name'],
                'message'  => $msg,
                'type'     => 'license_expiry',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Queue SMS reminders for overdue annual membership fees.
     */
    public function queuePaymentReminders(): int
    {
        $clubId = ClubContext::current();
        if ($clubId === null || !$this->isSmsEnabled($clubId)) return 0;

        try {
            $year = (int)date('Y');
            $stmt = $this->db->prepare("
                SELECT m.first_name, m.last_name, m.phone
                FROM members m
                LEFT JOIN payments p
                    ON p.member_id = m.id AND p.period_year = ?
                    AND p.payment_type_id IN (SELECT id FROM payment_types WHERE name LIKE '%składka roczna%')
                WHERE m.club_id = ?
                  AND m.status = 'aktywny'
                  AND m.phone IS NOT NULL AND m.phone != ''
                  AND NOT EXISTS (
                      SELECT 1 FROM sms_queue sq
                      WHERE sq.type = 'payment_overdue'
                        AND sq.to_phone = m.phone
                        AND YEAR(sq.created_at) = ?
                        AND sq.status != 'failed'
                  )
                GROUP BY m.id
                HAVING COUNT(p.id) = 0
            ");
            $stmt->execute([$year, $clubId, $year]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $msg = "Przypomnienie: nie odnotowaliśmy Twojej składki rocznej za " . $year
                 . ". Prosimy o uregulowanie w biurze klubu lub przelewem.";
            $this->enqueue([
                'club_id'  => $clubId,
                'to_phone' => $row['phone'],
                'to_name'  => $row['first_name'] . ' ' . $row['last_name'],
                'message'  => $msg,
                'type'     => 'payment_overdue',
            ]);
            $count++;
        }
        return $count;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isSmsEnabled(int $clubId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT value FROM club_settings WHERE club_id = ? AND `key` = 'sms_enabled' LIMIT 1"
            );
            $stmt->execute([$clubId]);
            return (bool)$stmt->fetchColumn();
        } catch (\PDOException) {
            return false;
        }
    }
}
