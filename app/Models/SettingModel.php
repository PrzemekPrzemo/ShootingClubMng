<?php

namespace App\Models;

class SettingModel extends BaseModel
{
    protected string $table = 'settings';

    public function getAll(): array
    {
        $rows = $this->db->query("SELECT * FROM settings ORDER BY label")->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['key']] = $r;
        }
        return $result;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $stmt = $this->db->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
        $stmt->execute([$value, $key]);
    }

    public function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
