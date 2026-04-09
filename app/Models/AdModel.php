<?php

namespace App\Models;

use App\Helpers\Database;

class AdModel
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        try {
            return $this->db->query(
                "SELECT a.*, c.name AS club_name
                 FROM ads a
                 LEFT JOIN clubs c ON c.id = a.club_id
                 ORDER BY a.sort_order ASC, a.id DESC"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    /**
     * Get active ads for a specific target (club_ui or member_portal)
     * filtered by club_id and plan.
     */
    public function getActive(string $target, int $clubId, string $plan = 'trial'): array
    {
        try {
            $today = date('Y-m-d');
            $stmt  = $this->db->prepare(
                "SELECT * FROM ads
                 WHERE is_active = 1
                   AND FIND_IN_SET(?, target)
                   AND (club_id IS NULL OR club_id = ?)
                   AND (starts_at IS NULL OR starts_at <= ?)
                   AND (ends_at   IS NULL OR ends_at   >= ?)
                   AND (plan_keys IS NULL OR FIND_IN_SET(?, plan_keys))
                 ORDER BY sort_order ASC, id DESC"
            );
            $stmt->execute([$target, $clubId, $today, $today, $plan]);
            return $stmt->fetchAll();
        } catch (\Throwable) { return []; }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ads WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable) { return null; }
    }

    public function create(array $data): int
    {
        $this->db->prepare(
            "INSERT INTO ads (title, content, image_path, link_url, target, club_id, plan_keys, is_active, starts_at, ends_at, sort_order)
             VALUES (:title, :content, :image_path, :link_url, :target, :club_id, :plan_keys, :is_active, :starts_at, :ends_at, :sort_order)"
        )->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        $data['id'] = $id;
        $this->db->prepare("UPDATE ads SET {$sets} WHERE id = :id")->execute($data);
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
    }

    public function toggle(int $id): void
    {
        $this->db->prepare("UPDATE ads SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }
}
