<?php

namespace App\Models;

use App\Helpers\Auth;

class AnnouncementModel extends ClubScopedModel
{
    protected string $table = 'announcements';

    public function getAll(bool $publishedOnly = false): array
    {
        try {
            $cid    = $this->clubId();
            $where  = [];
            $params = [];
            if ($publishedOnly) { $where[] = "a.is_published = 1"; }
            if ($cid !== null)  { $where[] = "a.club_id = ?"; $params[] = $cid; }

            $sql = "SELECT a.*, u.full_name AS created_by_name
                    FROM announcements a
                    LEFT JOIN users u ON u.id = a.created_by";
            if ($where) { $sql .= " WHERE " . implode(' AND ', $where); }
            $sql .= " ORDER BY
                       FIELD(a.priority,'pilne','wazne','normal'),
                       a.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getActive(): array
    {
        try {
            $cid = $this->clubId();
            $sql = "SELECT a.*, u.full_name AS created_by_name
                    FROM announcements a
                    LEFT JOIN users u ON u.id = a.created_by
                    WHERE a.is_published = 1
                      AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
            $params = [];
            if ($cid !== null) { $sql .= " AND a.club_id = ?"; $params[] = $cid; }
            $sql .= " ORDER BY
                       FIELD(a.priority,'pilne','wazne','normal'),
                       a.published_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $cid = $this->clubId();
            $sql = "SELECT a.*, u.full_name AS created_by_name
                    FROM announcements a
                    LEFT JOIN users u ON u.id = a.created_by
                    WHERE a.id = ?";
            $params = [$id];
            if ($cid !== null) { $sql .= " AND a.club_id = ?"; $params[] = $cid; }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException) {
            return null;
        }
    }

    public function create(array $data): int
    {
        try {
            $data['created_by'] = Auth::id();
            return $this->insert($data);
        } catch (\PDOException) {
            return 0;
        }
    }

    public function updateAnnouncement(int $id, array $data): bool
    {
        try {
            return $this->update($id, $data);
        } catch (\PDOException) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $cid = $this->clubId();
            $sql = "DELETE FROM announcements WHERE id = ?";
            $params = [$id];
            if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
            $this->db->prepare($sql)->execute($params);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function togglePublish(int $id): bool
    {
        try {
            $ann = $this->findById($id);
            if (!$ann) return false;

            $nowPublished = !(bool)$ann['is_published'];
            $data = ['is_published' => $nowPublished ? 1 : 0];
            if ($nowPublished) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            return $this->update($id, $data);
        } catch (\PDOException) {
            return false;
        }
    }
}
