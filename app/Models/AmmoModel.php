<?php

namespace App\Models;

class AmmoModel extends ClubScopedModel
{
    protected string $table = 'ammo_stock';

    public function getAll(array $filters = [], int $page = 1, int $perPage = 40): array
    {
        $where  = ['1=1'];
        $params = [];

        $clubId = $this->clubId();
        if ($clubId !== null) {
            $where[]  = "a.club_id = ?";
            $params[] = $clubId;
        }

        if (!empty($filters['caliber'])) {
            $where[]  = "a.caliber = ?";
            $params[] = $filters['caliber'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = "a.recorded_at >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = "a.recorded_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT a.*, u.full_name AS recorded_by_name
                FROM ammo_stock a
                LEFT JOIN users u ON u.id = a.recorded_by
                WHERE {$whereClause}
                ORDER BY a.recorded_at DESC, a.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function recordMovement(array $data): int
    {
        return $this->insert($data);
    }

    public function getSummaryByCaliber(): array
    {
        $params = [];
        $where  = '1=1';
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $where    = 'club_id = ?';
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare(
            "SELECT caliber, SUM(quantity) AS balance
             FROM ammo_stock
             WHERE {$where}
             GROUP BY caliber
             ORDER BY caliber"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCaliberList(): array
    {
        $params = [];
        $where  = '1=1';
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $where    = 'club_id = ?';
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare(
            "SELECT DISTINCT caliber FROM ammo_stock WHERE {$where} ORDER BY caliber"
        );
        $stmt->execute($params);
        return array_column($stmt->fetchAll(), 'caliber');
    }
}
