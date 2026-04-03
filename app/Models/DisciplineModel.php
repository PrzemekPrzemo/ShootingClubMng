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
}
