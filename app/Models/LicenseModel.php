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
        $sql = "SELECT l.*, m.first_name, m.last_name, m.member_number, d.name AS discipline_name
                FROM licenses l
                JOIN members m ON m.id = l.member_id
                LEFT JOIN disciplines d ON d.id = l.discipline_id
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
            SELECT l.*, m.first_name, m.last_name, m.member_number, d.name AS discipline_name
            FROM licenses l
            JOIN members m ON m.id = l.member_id
            LEFT JOIN disciplines d ON d.id = l.discipline_id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
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
