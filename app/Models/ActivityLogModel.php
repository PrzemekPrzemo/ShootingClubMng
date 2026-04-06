<?php
namespace App\Models;

use App\Helpers\Auth;

class ActivityLogModel extends BaseModel
{
    protected string $table = 'activity_log';

    public function log(string $action, ?string $entity = null, ?int $entityId = null, ?string $details = null): void
    {
        try {
            $this->insert([
                'user_id'    => Auth::id(),
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'details'    => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable) {
            // Never throw from logging
        }
    }

    public function getRecent(array $filters = [], int $limit = 200): array
    {
        try {
            $where  = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = 'al.user_id = ?';
                $params[] = (int)$filters['user_id'];
            }
            if (!empty($filters['entity'])) {
                $where[] = 'al.entity = ?';
                $params[] = $filters['entity'];
            }
            if (!empty($filters['action'])) {
                $where[] = 'al.action LIKE ?';
                $params[] = '%' . $filters['action'] . '%';
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'DATE(al.created_at) >= ?';
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'DATE(al.created_at) <= ?';
                $params[] = $filters['date_to'];
            }

            $sql = "SELECT al.*, u.full_name AS user_name, u.username
                    FROM activity_log al
                    LEFT JOIN users u ON u.id = al.user_id"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getDistinctEntities(): array
    {
        try {
            return $this->db->query(
                "SELECT DISTINCT entity FROM activity_log WHERE entity IS NOT NULL ORDER BY entity"
            )->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException) {
            return [];
        }
    }
}
