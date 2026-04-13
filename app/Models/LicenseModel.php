<?php

namespace App\Models;

class LicenseModel extends BaseModel
{
    protected string $table = 'licenses';

    public function search(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[]  = "(m.last_name LIKE ? OR m.first_name LIKE ? OR l.license_number LIKE ?)";
            array_push($params, $q, $q, $q);
        }
        if (!empty($filters['license_type'])) {
            // Filter by short_code — works before and after migration_v7
            $where[]  = "l.license_type = ?";
            $params[] = $filters['license_type'];
        }
        if (!empty($filters['status'])) {
            $where[]  = "l.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['member_id'])) {
            $where[]  = "l.member_id = ?";
            $params[] = $filters['member_id'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT l.*,
                       m.first_name, m.last_name, m.member_number,
                       (SELECT GROUP_CONCAT(d2.name ORDER BY d2.name SEPARATOR ', ')
                        FROM license_disciplines ld2
                        JOIN disciplines d2 ON d2.id = ld2.discipline_id
                        WHERE ld2.license_id = l.id) AS discipline_names
                FROM licenses l
                JOIN members m ON m.id = l.member_id
                WHERE {$whereClause}
                ORDER BY l.valid_until ASC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateLicense(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function getWithMember(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, m.first_name, m.last_name, m.member_number,
                   (SELECT GROUP_CONCAT(d2.name ORDER BY d2.name SEPARATOR ', ')
                    FROM license_disciplines ld2
                    JOIN disciplines d2 ON d2.id = ld2.discipline_id
                    WHERE ld2.license_id = l.id) AS discipline_names
            FROM licenses l
            JOIN members m ON m.id = l.member_id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Returns discipline IDs assigned to this license (for form pre-selection) */
    public function getDisciplineIds(int $licenseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT discipline_id FROM license_disciplines WHERE license_id = ? ORDER BY discipline_id"
        );
        $stmt->execute([$licenseId]);
        return array_column($stmt->fetchAll(), 'discipline_id');
    }

    /** Replaces all discipline assignments for a license */
    public function saveDisciplines(int $licenseId, array $disciplineIds): void
    {
        $this->db->prepare("DELETE FROM license_disciplines WHERE license_id = ?")->execute([$licenseId]);
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO license_disciplines (license_id, discipline_id) VALUES (?, ?)"
        );
        foreach (array_unique($disciplineIds) as $did) {
            if ((int)$did > 0) {
                $stmt->execute([$licenseId, (int)$did]);
            }
        }
    }

    /**
     * Returns [member_id => license_number] for the most recent active
     * 'zawodnicza' license for each of the given member IDs.
     */
    public function getLicenseMapForMembers(array $memberIds): array
    {
        if (empty($memberIds)) return [];
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = $this->db->prepare("
            SELECT member_id, license_number
            FROM licenses
            WHERE member_id IN ($placeholders)
              AND license_type = 'zawodnicza'
            ORDER BY valid_until DESC
        ");
        $stmt->execute(array_values($memberIds));
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            // Keep first (most recent) per member
            if (!isset($map[(int)$row['member_id']])) {
                $map[(int)$row['member_id']] = $row['license_number'];
            }
        }
        return $map;
    }

    public function getExpiring(int $days = 60): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, m.first_name, m.last_name, m.member_number,
                   DATEDIFF(l.valid_until, CURDATE()) AS days_left
            FROM licenses l
            JOIN members m ON m.id = l.member_id
            WHERE l.status = 'aktywna'
              AND m.status = 'aktywny'
              AND DATEDIFF(l.valid_until, CURDATE()) <= ?
            ORDER BY days_left ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
