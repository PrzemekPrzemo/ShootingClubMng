<?php
namespace App\Models;

use App\Helpers\Auth;

class TrainingsModel extends ClubScopedModel
{
    protected string $table = 'trainings';

    public function getAll(array $filters = []): array
    {
        try {
            $where  = [];
            $params = [];

            if (!empty($filters['month'])) {
                $where[]  = 'DATE_FORMAT(t.training_date, \'%Y-%m\') = ?';
                $params[] = $filters['month'];
            }
            if (!empty($filters['status'])) {
                $where[]  = 't.status = ?';
                $params[] = $filters['status'];
            }
            if (!empty($filters['instructor_id'])) {
                $where[]  = 't.instructor_id = ?';
                $params[] = (int)$filters['instructor_id'];
            }

            $sql = "SELECT t.*,
                           u.full_name AS instructor_name,
                           (SELECT COUNT(*) FROM training_attendees ta WHERE ta.training_id = t.id) AS attendee_count
                    FROM trainings t
                    LEFT JOIN users u ON u.id = t.instructor_id"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY t.training_date DESC, t.time_start DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getUpcoming(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT t.*, u.full_name AS instructor_name
                 FROM trainings t
                 LEFT JOIN users u ON u.id = t.instructor_id
                 WHERE t.training_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                   AND t.status = 'planowany'
                 ORDER BY t.training_date ASC, t.time_start ASC"
            );
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function findWithDetails(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT t.*, u.full_name AS instructor_name, u.username AS instructor_username,
                        cb.full_name AS created_by_name
                 FROM trainings t
                 LEFT JOIN users u ON u.id = t.instructor_id
                 LEFT JOIN users cb ON cb.id = t.created_by
                 WHERE t.id = ?"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException) {
            return null;
        }
    }

    public function getAttendees(int $trainingId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT ta.*, m.first_name, m.last_name, m.member_number, m.status AS member_status
                 FROM training_attendees ta
                 JOIN members m ON m.id = ta.member_id
                 WHERE ta.training_id = ?
                 ORDER BY m.last_name, m.first_name"
            );
            $stmt->execute([$trainingId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function saveAttendees(int $trainingId, array $memberIds, array $attended): void
    {
        try {
            // Remove attendees not in new list
            if (empty($memberIds)) {
                $this->db->prepare("DELETE FROM training_attendees WHERE training_id = ?")
                         ->execute([$trainingId]);
                return;
            }

            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $params = array_merge([$trainingId], array_map('intval', $memberIds));
            $this->db->prepare(
                "DELETE FROM training_attendees WHERE training_id = ? AND member_id NOT IN ({$placeholders})"
            )->execute($params);

            foreach ($memberIds as $memberId) {
                $memberId    = (int)$memberId;
                $didAttend   = in_array($memberId, array_map('intval', $attended)) ? 1 : 0;
                $this->db->prepare(
                    "INSERT INTO training_attendees (training_id, member_id, attended)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE attended = VALUES(attended)"
                )->execute([$trainingId, $memberId, $didAttend]);
            }
        } catch (\PDOException) {
            // Silently fail — table may not exist yet
        }
    }

    public function getMemberAttendanceStats(int $memberId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    COUNT(*) AS total,
                    SUM(attended) AS attended
                 FROM training_attendees
                 WHERE member_id = ?"
            );
            $stmt->execute([$memberId]);
            $row = $stmt->fetch();
            return [
                'total'    => (int)($row['total']    ?? 0),
                'attended' => (int)($row['attended'] ?? 0),
            ];
        } catch (\PDOException) {
            return ['total' => 0, 'attended' => 0];
        }
    }

    public function create(array $data): int
    {
        try {
            return $this->insert($data);
        } catch (\PDOException) {
            return 0;
        }
    }

    public function updateTraining(int $id, array $data): bool
    {
        try {
            return $this->update($id, $data);
        } catch (\PDOException) {
            return false;
        }
    }
}
