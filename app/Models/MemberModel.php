<?php

namespace App\Models;

class MemberModel extends ClubScopedModel
{
    protected string $table = 'members';

    public function search(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where  = ['1=1'];
        $params = [];

        // Scope to current club — prevents cross-club data leakage
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $where[]  = 'm.club_id = ?';
            $params[] = $clubId;
        }

        if (!empty($filters['q'])) {
            $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.member_number LIKE ? OR m.card_number LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            array_push($params, $q, $q, $q, $q);
        }
        if (!empty($filters['status'])) {
            $where[]  = "m.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['member_type'])) {
            $where[]  = "m.member_type = ?";
            $params[] = $filters['member_type'];
        }
        if (!empty($filters['age_category_id'])) {
            $where[]  = "m.age_category_id = ?";
            $params[] = $filters['age_category_id'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT m.*, ac.name AS age_category_name
                FROM members m
                LEFT JOIN member_age_categories ac ON ac.id = m.age_category_id
                WHERE {$whereClause}
                ORDER BY m.last_name, m.first_name";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Find which page a member appears on in the default listing (sorted by last_name, first_name).
     */
    public function findPageFor(int $memberId, int $perPage = 25): int
    {
        $clubId = $this->clubId();
        $where  = ['1=1'];
        $params = [];

        if ($clubId !== null) {
            $where[]  = 'm.club_id = ?';
            $params[] = $clubId;
        }

        $whereClause = implode(' AND ', $where);

        // Get the member's sort values
        $stmt = $this->db->prepare("SELECT last_name, first_name FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        if (!$member) return 1;

        // Count how many members come before this one in sort order
        $countSql = "SELECT COUNT(*) FROM members m
                     WHERE {$whereClause}
                       AND (m.last_name < ? OR (m.last_name = ? AND m.first_name < ?))";
        $countParams = array_merge($params, [$member['last_name'], $member['last_name'], $member['first_name']]);
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($countParams);
        $position = (int)$stmt->fetchColumn();

        return (int)floor($position / $perPage) + 1;
    }

    public function getWithDetails(int $id): ?array
    {
        $clubId    = $this->clubId();
        $clubWhere = $clubId !== null ? 'AND m.club_id = ?' : '';
        $params    = $clubId !== null ? [$id, $clubId] : [$id];

        $stmt = $this->db->prepare("
            SELECT m.*, ac.name AS age_category_name,
                   mc.name AS member_class_name, mc.short_code AS member_class_code
            FROM members m
            LEFT JOIN member_age_categories ac ON ac.id = m.age_category_id
            LEFT JOIN member_classes mc ON mc.id = m.member_class_id
            WHERE m.id = ? {$clubWhere}
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getDisciplines(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT md.*, d.name AS discipline_name, d.short_code, u.full_name AS instructor_name
            FROM member_disciplines md
            JOIN disciplines d ON d.id = md.discipline_id
            LEFT JOIN users u ON u.id = md.instructor_id
            WHERE md.member_id = ?
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function addDiscipline(array $data): int
    {
        return $this->insert_table('member_disciplines', $data);
    }

    public function removeDiscipline(int $memberId, int $disciplineId): void
    {
        $this->db->prepare("DELETE FROM member_disciplines WHERE member_id = ? AND discipline_id = ?")->execute([$memberId, $disciplineId]);
    }

    public function clearDisciplines(int $memberId): void
    {
        $this->db->prepare("DELETE FROM member_disciplines WHERE member_id = ?")->execute([$memberId]);
    }

    public function createMember(array $data): int
    {
        // Auto-generate member number scoped to current club
        $year   = date('Y');
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $stmt = $this->db->prepare(
                "SELECT member_number FROM members WHERE member_number LIKE ? AND club_id = ? ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute(["KS{$year}%", $clubId]);
            $last = $stmt->fetchColumn();
        } else {
            $last = $this->db->query(
                "SELECT member_number FROM members WHERE member_number LIKE 'KS{$year}%' ORDER BY id DESC LIMIT 1"
            )->fetchColumn();
        }
        if ($last) {
            $seq = (int)substr($last, 6) + 1;
        } else {
            $seq = 1;
        }
        $data['member_number'] = sprintf('KS%s%04d', $year, $seq);
        return $this->insert($data);
    }

    public function updateMember(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function getLatestMedical(int $memberId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM member_medical_exams
            WHERE member_id = ?
            ORDER BY valid_until DESC
            LIMIT 1
        ");
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getLatestLicense(int $memberId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM licenses WHERE member_id = ? AND license_type = 'zawodnicza' ORDER BY valid_until DESC LIMIT 1
        ");
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Returns the most recent license of each type for the member.
     * Keyed by license_type, e.g. ['zawodnicza' => [...], 'patent' => [...]]
     */
    public function getAllLicensesByType(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM licenses
            WHERE member_id = ?
            ORDER BY license_type ASC, valid_until DESC, id DESC
        ");
        $stmt->execute([$memberId]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $type = $row['license_type'];
            if (!isset($result[$type])) {
                $result[$type] = $row; // keep the latest per type
            }
        }
        return $result;
    }

    public function getPaymentStatus(int $memberId, int $year): array
    {
        $stmt = $this->db->prepare("
            SELECT SUM(amount) as total FROM payments WHERE member_id = ? AND period_year = ?
        ");
        $stmt->execute([$memberId, $year]);
        return $stmt->fetch();
    }

    /** Count members grouped by status — scoped to current club */
    public function countByStatus(): array
    {
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $stmt = $this->db->prepare(
                "SELECT status, COUNT(*) as cnt FROM members WHERE club_id = ? GROUP BY status"
            );
            $stmt->execute([$clubId]);
        } else {
            $stmt = $this->db->query(
                "SELECT status, COUNT(*) as cnt FROM members GROUP BY status"
            );
        }
        $result = [];
        foreach ($stmt->fetchAll() as $r) {
            $result[$r['status']] = (int)$r['cnt'];
        }
        return $result;
    }

    /** Get members with expiring medicals — scoped to current club */
    public function getExpiredMedicals(int $daysAhead = 30): array
    {
        $clubId    = $this->clubId();
        $clubWhere = $clubId !== null ? 'AND m.club_id = ?' : '';
        $params    = $clubId !== null ? [$clubId, $daysAhead] : [$daysAhead];

        $stmt = $this->db->prepare("
            SELECT m.id, m.first_name, m.last_name, m.member_number,
                   e.valid_until, DATEDIFF(e.valid_until, CURDATE()) AS days_left
            FROM members m
            INNER JOIN member_medical_exams e ON e.id = (
                SELECT id FROM member_medical_exams WHERE member_id = m.id ORDER BY valid_until DESC LIMIT 1
            )
            WHERE m.member_type = 'wyczynowy'
              AND m.status = 'aktywny'
              {$clubWhere}
              AND DATEDIFF(e.valid_until, CURDATE()) <= ?
            ORDER BY days_left
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Get active members for dropdowns/lookups — scoped to current club */
    public function getAllActive(): array
    {
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $stmt = $this->db->prepare(
                "SELECT id, CONCAT(last_name, ' ', first_name) AS full_name, member_number, member_class_id
                 FROM members WHERE status = 'aktywny' AND club_id = ? ORDER BY last_name, first_name"
            );
            $stmt->execute([$clubId]);
        } else {
            $stmt = $this->db->query(
                "SELECT id, CONCAT(last_name, ' ', first_name) AS full_name, member_number, member_class_id
                 FROM members WHERE status = 'aktywny' ORDER BY last_name, first_name"
            );
        }
        return $stmt->fetchAll();
    }

    private function insert_table(string $table, array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $stmt  = $this->db->prepare("INSERT INTO `{$table}` (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }
}
