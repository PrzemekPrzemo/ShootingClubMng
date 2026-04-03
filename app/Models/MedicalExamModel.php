<?php

namespace App\Models;

class MedicalExamModel extends BaseModel
{
    protected string $table = 'member_medical_exams';

    public function getForMember(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.full_name AS created_by_name
            FROM member_medical_exams e
            JOIN users u ON u.id = e.created_by
            WHERE e.member_id = ?
            ORDER BY e.exam_date DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateExam(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /** All exams expiring within $days days (for alerts) */
    public function getExpiring(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT m.id AS member_id, m.first_name, m.last_name, m.member_number,
                   e.valid_until, DATEDIFF(e.valid_until, CURDATE()) AS days_left
            FROM members m
            INNER JOIN member_medical_exams e ON e.id = (
                SELECT id FROM member_medical_exams WHERE member_id = m.id ORDER BY valid_until DESC LIMIT 1
            )
            WHERE m.member_type = 'wyczynowy'
              AND m.status = 'aktywny'
              AND DATEDIFF(e.valid_until, CURDATE()) <= ?
            ORDER BY days_left ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
