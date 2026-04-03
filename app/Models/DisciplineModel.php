<?php

namespace App\Models;

class DisciplineModel extends BaseModel
{
    protected string $table = 'disciplines';

    public function getActive(): array
    {
        return $this->db->query("SELECT * FROM disciplines WHERE is_active = 1 ORDER BY name")->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM disciplines ORDER BY name")->fetchAll();
    }

    public function save(array $data): int
    {
        return $this->insert($data);
    }

    public function saveUpdate(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function toggle(int $id): void
    {
        $this->db->prepare("UPDATE disciplines SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }

    public function isUsed(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM (
               SELECT discipline_id FROM competitions       WHERE discipline_id = ?
               UNION ALL
               SELECT discipline_id FROM member_disciplines WHERE discipline_id = ?
               UNION ALL
               SELECT discipline_id FROM licenses           WHERE discipline_id = ?
             ) t"
        );
        $stmt->execute([$id, $id, $id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
