<?php

namespace App\Models;

class JudgeLicenseModel extends BaseModel
{
    protected string $table = 'judge_licenses';

    public function getAll(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        // Scope to current club (via member's club_id) when context is set.
        // Also include judges where the member has no club assigned (legacy data).
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId !== null) {
            $where[]  = '(m.club_id = ? OR m.club_id IS NULL)';
            $params[] = $clubId;
        }

        if (!empty($filters['judge_class'])) {
            $where[]  = "jl.judge_class = ?";
            $params[] = $filters['judge_class'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $where[] = "jl.valid_until >= CURDATE()";
            } elseif ($filters['status'] === 'expired') {
                $where[] = "jl.valid_until < CURDATE()";
            }
        }
        if (!empty($filters['fee_paid'])) {
            if ($filters['fee_paid'] === 'yes') {
                $where[] = "jl.fee_paid_year = YEAR(CURDATE())";
            } elseif ($filters['fee_paid'] === 'no') {
                $where[] = "(jl.fee_paid_year IS NULL OR jl.fee_paid_year < YEAR(CURDATE()))";
            }
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT jl.*,
                   m.first_name, m.last_name, m.member_number, m.status AS member_status,
                   d.name AS discipline_name,
                   u.id AS user_id, u.username AS user_username,
                   DATEDIFF(jl.valid_until, CURDATE()) AS days_left
            FROM judge_licenses jl
            JOIN members m ON m.id = jl.member_id
            LEFT JOIN disciplines d ON d.id = jl.discipline_id
            LEFT JOIN users u ON u.member_id = m.id
            WHERE {$whereClause}
            ORDER BY jl.valid_until ASC, m.last_name, m.first_name
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getForMember(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT jl.*, d.name AS discipline_name,
                   DATEDIFF(jl.valid_until, CURDATE()) AS days_left
            FROM judge_licenses jl
            LEFT JOIN disciplines d ON d.id = jl.discipline_id
            WHERE jl.member_id = ?
            ORDER BY jl.valid_until DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function getExpiring(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT jl.*, m.first_name, m.last_name, m.member_number,
                   d.name AS discipline_name,
                   DATEDIFF(jl.valid_until, CURDATE()) AS days_left
            FROM judge_licenses jl
            JOIN members m ON m.id = jl.member_id
            LEFT JOIN disciplines d ON d.id = jl.discipline_id
            WHERE jl.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY days_left ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getActiveJudges(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        $clubWhere = $clubId !== null ? 'AND m.club_id = ?' : '';
        $params    = $clubId !== null ? [$clubId] : [];

        $stmt = $this->db->prepare("
            SELECT DISTINCT m.id, m.first_name, m.last_name, m.member_number,
                   jl.judge_class, jl.valid_until,
                   d.name AS discipline_name
            FROM judge_licenses jl
            JOIN members m ON m.id = jl.member_id
            LEFT JOIN disciplines d ON d.id = jl.discipline_id
            WHERE jl.valid_until >= CURDATE()
              AND m.status = 'aktywny'
              {$clubWhere}
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateLicense(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function markFeePaid(int $id): void
    {
        $this->db->prepare("
            UPDATE judge_licenses
            SET fee_paid_year = YEAR(CURDATE()), fee_paid_date = CURDATE()
            WHERE id = ?
        ")->execute([$id]);
    }
}
