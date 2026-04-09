<?php

namespace App\Models;

class AmmoModel extends ClubScopedModel
{
    protected string $table = 'ammo_stock';

    public function getAll(array $filters = [], int $page = 1, int $perPage = 40): array
    {
        $where  = ['1=1'];
        $params = [];

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
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `ammo_stock` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function getSummaryByCaliber(): array
    {
        $stmt = $this->db->query(
            "SELECT caliber, SUM(quantity) AS balance
             FROM ammo_stock
             GROUP BY caliber
             ORDER BY caliber"
        );
        return $stmt->fetchAll();
    }

    public function getCaliberList(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT caliber FROM ammo_stock ORDER BY caliber"
        );
        return array_column($stmt->fetchAll(), 'caliber');
    }
}
