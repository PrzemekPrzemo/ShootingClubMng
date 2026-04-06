<?php

namespace App\Models;

class MemberWeaponModel extends BaseModel
{
    protected string $table = 'member_weapons';

    public static array $TYPES = [
        'pistolet' => 'Pistolet',
        'rewolwer' => 'Rewolwer',
        'karabin'  => 'Karabin',
        'strzelba' => 'Strzelba',
        'inne'     => 'Inne',
    ];

    public function getForMember(int $memberId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT mw.*, u.full_name AS created_by_name
                FROM member_weapons mw
                LEFT JOIN users u ON u.id = mw.created_by
                WHERE mw.member_id = ?
                ORDER BY mw.is_active DESC, mw.created_at DESC
            ");
            $stmt->execute([$memberId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getActiveForMember(int $memberId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM member_weapons
                WHERE member_id = ? AND is_active = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([$memberId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateWeapon(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM member_weapons WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException) {
            return false;
        }
    }

    /** Count weapons per member, used in stats */
    public function countForMember(int $memberId): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM member_weapons WHERE member_id = ? AND is_active = 1");
            $stmt->execute([$memberId]);
            return (int)$stmt->fetchColumn();
        } catch (\PDOException) {
            return 0;
        }
    }
}
