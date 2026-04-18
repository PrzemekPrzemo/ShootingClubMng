<?php

namespace App\Models;

class NotificationModel extends ClubScopedModel
{
    protected string $table = 'notifications';

    public function create(array $data): int
    {
        try {
            return $this->insert($data);
        } catch (\PDOException) {
            return 0;
        }
    }

    /**
     * Returns unread notifications intended for any of the given roles.
     * Uses FIND_IN_SET to match comma-separated for_roles column.
     */
    public function getUnreadForRoles(array $roles, int $limit = 20): array
    {
        if (empty($roles)) return [];
        try {
            $conditions = implode(' OR ', array_fill(0, count($roles), 'FIND_IN_SET(?, for_roles)'));
            $sql = "SELECT n.*, m.first_name, m.last_name
                    FROM notifications n
                    LEFT JOIN members m ON m.id = n.related_member_id
                    WHERE n.is_read = 0 AND ({$conditions}){$this->clubWhereAliased('n')}
                    ORDER BY n.created_at DESC
                    LIMIT {$limit}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge($roles, $this->clubParams()));
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function countUnreadForRoles(array $roles): int
    {
        if (empty($roles)) return 0;
        try {
            $conditions = implode(' OR ', array_fill(0, count($roles), 'FIND_IN_SET(?, for_roles)'));
            $sql = "SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND ({$conditions}){$this->clubWhere()}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge($roles, $this->clubParams()));
            return (int)$stmt->fetchColumn();
        } catch (\PDOException) {
            return 0;
        }
    }

    public function markRead(int $id): void
    {
        try {
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?{$this->clubWhere()}";
            $this->db->prepare($sql)->execute(array_merge([$id], $this->clubParams()));
        } catch (\PDOException) {}
    }

    public function markAllRead(array $roles): void
    {
        if (empty($roles)) return;
        try {
            $conditions = implode(' OR ', array_fill(0, count($roles), 'FIND_IN_SET(?, for_roles)'));
            $sql = "UPDATE notifications SET is_read = 1 WHERE ({$conditions}){$this->clubWhere()}";
            $this->db->prepare($sql)->execute(array_merge($roles, $this->clubParams()));
        } catch (\PDOException) {}
    }

    /** Like clubWhere() but uses a table alias (for queries with JOINs). */
    private function clubWhereAliased(string $alias): string
    {
        return \App\Helpers\ClubContext::current() !== null ? " AND {$alias}.club_id = ?" : '';
    }
}
