<?php

namespace App\Models;

use App\Helpers\Database;

class EmailQueueModel extends BaseModel
{
    protected string $table = 'email_queue';

    public function enqueue(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `email_queue` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function getPending(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM email_queue WHERE status = 'pending' ORDER BY scheduled_at ASC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function markSent(int $id): void
    {
        $this->db->prepare(
            "UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $this->db->prepare(
            "UPDATE email_queue SET status = 'failed', error = ? WHERE id = ?"
        )->execute([mb_substr($error, 0, 500), $id]);
    }

    public function getRecent(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = "eq.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where[]  = "eq.type = ?";
            $params[] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT eq.* FROM email_queue eq WHERE {$whereClause} ORDER BY eq.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function countByStatus(): array
    {
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) AS cnt FROM email_queue GROUP BY status"
        );
        $result = ['pending' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['cnt'];
        }
        return $result;
    }

    public function clearSent(): void
    {
        $this->db->exec("DELETE FROM email_queue WHERE status = 'sent'");
    }

    // ── Notification generators ──────────────────────────────────────

    /**
     * Queues reminders for competitions happening within $days.
     * One email per member per competition. Skips already-queued.
     */
    public function queueCompetitionReminders(int $days = 7): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.id AS competition_id, c.name AS competition_name,
                       c.competition_date, c.location,
                       m.id AS member_id, m.first_name, m.last_name, m.email
                FROM competitions c
                JOIN competition_entries ce ON ce.competition_id = c.id
                JOIN members m ON m.id = ce.member_id
                WHERE c.status IN ('otwarte','planowane')
                  AND c.competition_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND m.email IS NOT NULL AND m.email != ''
                  AND NOT EXISTS (
                      SELECT 1 FROM email_queue eq
                      WHERE eq.type = 'competition_reminder'
                        AND eq.to_email = m.email
                        AND eq.subject LIKE CONCAT('%', c.name, '%')
                  )
            ");
            $stmt->execute([$days]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $date = date('d.m.Y', strtotime($row['competition_date']));
            $this->enqueue([
                'to_email'  => $row['email'],
                'to_name'   => $row['first_name'] . ' ' . $row['last_name'],
                'subject'   => 'Przypomnienie o zawodach: ' . $row['competition_name'],
                'body_html' => $this->tplCompetitionReminder($row, $date),
                'type'      => 'competition_reminder',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Queues overdue payment notifications for members without annual fee this year.
     */
    public function queuePaymentReminders(): int
    {
        try {
            $year = (int)date('Y');
            $stmt = $this->db->prepare("
                SELECT m.id, m.first_name, m.last_name, m.email
                FROM members m
                LEFT JOIN payments p
                    ON p.member_id = m.id AND p.period_year = ?
                    AND p.payment_type_id IN (SELECT id FROM payment_types WHERE name LIKE '%składka roczna%')
                WHERE m.status = 'aktywny'
                  AND m.email IS NOT NULL AND m.email != ''
                  AND NOT EXISTS (
                      SELECT 1 FROM email_queue eq
                      WHERE eq.type = 'payment_overdue'
                        AND eq.to_email = m.email
                        AND YEAR(eq.created_at) = ?
                  )
                GROUP BY m.id
                HAVING COUNT(p.id) = 0
            ");
            $stmt->execute([$year, $year]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $this->enqueue([
                'to_email'  => $row['email'],
                'to_name'   => $row['first_name'] . ' ' . $row['last_name'],
                'subject'   => 'Zaległość składki członkowskiej ' . date('Y'),
                'body_html' => $this->tplPaymentOverdue($row),
                'type'      => 'payment_overdue',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Queues expiring license notifications.
     */
    public function queueLicenseReminders(int $days = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.first_name, m.last_name, m.email,
                       l.valid_until, DATEDIFF(l.valid_until, CURDATE()) AS days_left
                FROM licenses l
                JOIN members m ON m.id = l.member_id
                WHERE l.status = 'aktywna'
                  AND m.status = 'aktywny'
                  AND m.email IS NOT NULL AND m.email != ''
                  AND DATEDIFF(l.valid_until, CURDATE()) BETWEEN 0 AND ?
                  AND NOT EXISTS (
                      SELECT 1 FROM email_queue eq
                      WHERE eq.type = 'license_expiry'
                        AND eq.to_email = m.email
                        AND eq.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  )
            ");
            $stmt->execute([$days]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $this->enqueue([
                'to_email'  => $row['email'],
                'to_name'   => $row['first_name'] . ' ' . $row['last_name'],
                'subject'   => 'Licencja wygasa za ' . $row['days_left'] . ' dni',
                'body_html' => $this->tplLicenseExpiry($row),
                'type'      => 'license_expiry',
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * Queues expiring medical exam notifications.
     */
    public function queueMedicalReminders(int $days = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.first_name, m.last_name, m.email,
                       me.valid_until, DATEDIFF(me.valid_until, CURDATE()) AS days_left,
                       met.name AS exam_type
                FROM medical_exams me
                JOIN members m ON m.id = me.member_id
                LEFT JOIN medical_exam_types met ON met.id = me.exam_type_id
                WHERE m.status = 'aktywny'
                  AND m.email IS NOT NULL AND m.email != ''
                  AND DATEDIFF(me.valid_until, CURDATE()) BETWEEN 0 AND ?
                  AND NOT EXISTS (
                      SELECT 1 FROM email_queue eq
                      WHERE eq.type = 'medical_expiry'
                        AND eq.to_email = m.email
                        AND eq.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  )
            ");
            $stmt->execute([$days]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $this->enqueue([
                'to_email'  => $row['email'],
                'to_name'   => $row['first_name'] . ' ' . $row['last_name'],
                'subject'   => 'Badania lekarskie wygasają za ' . $row['days_left'] . ' dni',
                'body_html' => $this->tplMedicalExpiry($row),
                'type'      => 'medical_expiry',
            ]);
            $count++;
        }
        return $count;
    }

    // ── HTML templates ───────────────────────────────────────────────

    private function tplCompetitionReminder(array $row, string $date): string
    {
        $name    = htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8');
        $comp    = htmlspecialchars($row['competition_name'], ENT_QUOTES, 'UTF-8');
        $loc     = htmlspecialchars($row['location'] ?? '', ENT_QUOTES, 'UTF-8');
        return "
<p>Dzień dobry {$name},</p>
<p>Przypominamy o zbliżających się zawodach:</p>
<ul>
  <li><strong>Zawody:</strong> {$comp}</li>
  <li><strong>Data:</strong> {$date}</li>" . ($loc ? "<li><strong>Miejsce:</strong> {$loc}</li>" : '') . "
</ul>
<p>Prosimy o punktualne przybycie.</p>
<p>Pozdrawiamy,<br>Zarząd Klubu</p>";
    }

    private function tplPaymentOverdue(array $row): string
    {
        $name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        return "
<p>Dzień dobry {$name},</p>
<p>W naszych ewidencjach brak jest opłacenia składki członkowskiej za rok <strong>{$year}</strong>.</p>
<p>Prosimy o uregulowanie należności lub skontaktowanie się z zarządem klubu.</p>
<p>Pozdrawiamy,<br>Zarząd Klubu</p>";
    }

    private function tplLicenseExpiry(array $row): string
    {
        $name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8');
        $date = date('d.m.Y', strtotime($row['valid_until']));
        return "
<p>Dzień dobry {$name},</p>
<p>Twoja licencja strzelecka wygasa <strong>{$date}</strong> (za {$row['days_left']} dni).</p>
<p>Prosimy o odnowienie licencji przed upływem terminu.</p>
<p>Pozdrawiamy,<br>Zarząd Klubu</p>";
    }

    private function tplMedicalExpiry(array $row): string
    {
        $name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES, 'UTF-8');
        $date = date('d.m.Y', strtotime($row['valid_until']));
        $type = htmlspecialchars($row['exam_type'] ?? 'badania', ENT_QUOTES, 'UTF-8');
        return "
<p>Dzień dobry {$name},</p>
<p>Twoje {$type} wygasają <strong>{$date}</strong> (za {$row['days_left']} dni).</p>
<p>Prosimy o wykonanie aktualnych badań i dostarczenie orzeczenia do biura klubu.</p>
<p>Pozdrawiamy,<br>Zarząd Klubu</p>";
    }
}
