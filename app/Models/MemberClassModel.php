<?php

namespace App\Models;

class MemberClassModel extends BaseModel
{
    protected string $table = 'member_classes';

    public function getAll(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM member_classes ORDER BY sort_order, name")->fetchAll();
        }
        $stmt = $this->db->prepare("SELECT * FROM member_classes WHERE club_id IS NULL OR club_id = ? ORDER BY sort_order, name");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM member_classes WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll();
        }
        $stmt = $this->db->prepare("SELECT * FROM member_classes WHERE is_active = 1 AND (club_id IS NULL OR club_id = ?) ORDER BY sort_order, name");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function save(array $data): int
    {
        return $this->insert($data);
    }

    public function saveUpdate(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
