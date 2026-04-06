<?php

namespace App\Models;

use App\Helpers\Auth;

class AnnouncementModel extends BaseModel
{
    protected string $table = 'announcements';

    public function getAll(bool $publishedOnly = false): array
    {
        try {
            $sql    = "SELECT a.*, u.full_name AS created_by_name
                       FROM announcements a
                       LEFT JOIN users u ON u.id = a.created_by";
            $params = [];

            if ($publishedOnly) {
                $sql   .= " WHERE a.is_published = 1";
            }

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
            $stmt = $this->db->prepare(
                "SELECT a.*, u.full_name AS created_by_name
                 FROM announcements a
                 LEFT JOIN users u ON u.id = a.created_by
                 WHERE a.is_published = 1
                   AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
                 ORDER BY
                   FIELD(a.priority,'pilne','wazne','normal'),
                   a.published_at DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT a.*, u.full_name AS created_by_name
                 FROM announcements a
                 LEFT JOIN users u ON u.id = a.created_by
                 WHERE a.id = ?"
            );
            $stmt->execute([$id]);
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

    public function delete(int $id): void
    {
        try {
            $this->db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
        } catch (\PDOException) {
            // Silently fail
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
