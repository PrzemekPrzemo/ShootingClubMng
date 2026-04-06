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

    /**
     * INSERT OR UPDATE a setting value (safe for feature flags that may not exist yet).
     */
    public function upsert(string $key, mixed $value, string $label = '', string $type = 'boolean'): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (`key`, value, label, type) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $stmt->execute([$key, $value, $label, $type]);
    }

    /**
     * Upsert many feature flags at once.
     * $flags: ['name' => '0'|'1', ...]
     */
    public function saveFeatureFlags(array $flags): void
    {
        foreach ($flags as $name => $value) {
            $this->upsert('feature_' . $name, (string)(int)$value);
        }
    }
}
