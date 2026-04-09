<?php

namespace App\Models;

class ClubModel extends BaseModel
{
    protected string $table = 'clubs';

    public function getActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `clubs` WHERE is_active = 1 ORDER BY `name` ASC"
        );
        return $stmt->fetchAll();
    }

    public function findBySubdomain(string $subdomain): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, cc.subdomain, cc.logo_path, cc.primary_color, cc.navbar_bg
             FROM `clubs` c
             LEFT JOIN `club_customization` cc ON cc.club_id = c.id
             WHERE cc.subdomain = ? AND c.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$subdomain]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateClub(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteClub(int $id): bool
    {
        return $this->delete($id);
    }

    /** Statystyki per klub — liczba członków, zawodów itp. */
    public function getStats(int $clubId): array
    {
        $stats = [];

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'");
        $stmt->execute([$clubId]);
        $stats['active_members'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM competitions WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $stats['competitions'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_clubs WHERE club_id = ? AND is_active = 1");
        $stmt->execute([$clubId]);
        $stats['staff'] = (int)$stmt->fetchColumn();

        return $stats;
    }

    /** Globalne statystyki — dla super admina. */
    public function getGlobalStats(): array
    {
        return [
            'clubs'   => (int)$this->db->query("SELECT COUNT(*) FROM clubs WHERE is_active = 1")->fetchColumn(),
            'members' => (int)$this->db->query("SELECT COUNT(*) FROM members WHERE status = 'aktywny'")->fetchColumn(),
            'users'   => (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
        ];
    }
}
