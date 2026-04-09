<?php

namespace App\Models;

class AgeCategoryModel extends BaseModel
{
    protected string $table = 'member_age_categories';

    public function getAll(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM member_age_categories ORDER BY sort_order, age_from")->fetchAll();
        }
        $stmt = $this->db->prepare("SELECT * FROM member_age_categories WHERE club_id IS NULL OR club_id = ? ORDER BY sort_order, age_from");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function detectCategory(int $age): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM member_age_categories WHERE age_from <= ? AND age_to >= ? ORDER BY sort_order LIMIT 1");
        $stmt->execute([$age, $age]);
        $row = $stmt->fetch();
        return $row ?: null;
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
