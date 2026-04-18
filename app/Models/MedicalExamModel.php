<?php

namespace App\Models;

use App\Helpers\ClubContext;

class MedicalExamModel extends BaseModel
{
    protected string $table = 'member_medical_exams';

    public function getForMember(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.full_name AS created_by_name,
                   t.name AS exam_type_name, t.validity_months
            FROM member_medical_exams e
            JOIN users u ON u.id = e.created_by
            LEFT JOIN medical_exam_types t ON t.id = e.exam_type_id
            WHERE e.member_id = ?
            ORDER BY e.exam_date DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns exam matrix: one row per active exam type with latest exam status.
     * Status: ok | warn (≤warnDays) | expired | missing
     */
    public function getExamMatrix(int $memberId, int $warnDays = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT t.id AS type_id, t.name AS type_name, t.validity_months, t.required_for,
                   e.exam_date, e.valid_until, e.file_path,
                   DATEDIFF(e.valid_until, CURDATE()) AS days_left
            FROM medical_exam_types t
            LEFT JOIN (
                SELECT exam_type_id, exam_date, valid_until, file_path
                FROM member_medical_exams
                WHERE member_id = ?
                  AND exam_type_id IS NOT NULL
                ORDER BY valid_until DESC
            ) e ON e.exam_type_id = t.id
            WHERE t.is_active = 1
            ORDER BY t.sort_order, t.id
        ");
        $stmt->execute([$memberId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            if (is_null($row['valid_until'])) {
                $row['status'] = 'missing';
            } elseif ($row['days_left'] < 0) {
                $row['status'] = 'expired';
            } elseif ($row['days_left'] <= $warnDays) {
                $row['status'] = 'warn';
            } else {
                $row['status'] = 'ok';
            }
        }
        return $rows;
    }

    /**
     * Returns exams expiring within $days for dashboard.
     */
    public function getExpiringByType(int $days = 30): array
    {
        $cid = ClubContext::current();
        $sql = "SELECT m.id AS member_id, m.first_name, m.last_name, m.member_number,
                       e.valid_until, DATEDIFF(e.valid_until, CURDATE()) AS days_left,
                       t.name AS exam_type_name
                FROM members m
                JOIN member_medical_exams e ON e.member_id = m.id
                LEFT JOIN medical_exam_types t ON t.id = e.exam_type_id
                WHERE m.status = 'aktywny'
                  AND e.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
        $params = [$days];
        if ($cid !== null) { $sql .= " AND m.club_id = ?"; $params[] = $cid; }
        $sql .= " ORDER BY days_left ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
        $cid = ClubContext::current();
        $sql = "SELECT m.id AS member_id, m.first_name, m.last_name, m.member_number,
                       e.valid_until, DATEDIFF(e.valid_until, CURDATE()) AS days_left,
                       t.name AS exam_type_name
                FROM members m
                JOIN member_medical_exams e ON e.member_id = m.id
                LEFT JOIN medical_exam_types t ON t.id = e.exam_type_id
                WHERE m.status = 'aktywny'
                  AND DATEDIFF(e.valid_until, CURDATE()) <= ?";
        $params = [$days];
        if ($cid !== null) { $sql .= " AND m.club_id = ?"; $params[] = $cid; }
        $sql .= " ORDER BY days_left ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
