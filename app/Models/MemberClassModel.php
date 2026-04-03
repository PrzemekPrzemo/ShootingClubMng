<?php

namespace App\Models;

class MemberClassModel extends BaseModel
{
    protected string $table = 'member_classes';

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM member_classes ORDER BY sort_order, name")->fetchAll();
    }

    public function getActive(): array
    {
        return $this->db->query("SELECT * FROM member_classes WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll();
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
