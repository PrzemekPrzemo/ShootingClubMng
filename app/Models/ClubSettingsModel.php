<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

class ClubSettingsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    /** Pobierz pojedyncze ustawienie per-klub z fallbackiem. */
    public function get(int $clubId, string $key, mixed $default = null): mixed
    {
        $stmt = $this->db->prepare(
            "SELECT `value`, `type` FROM `club_settings` WHERE club_id = ? AND `key` = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $key]);
        $row = $stmt->fetch();

        if (!$row) {
            return $default;
        }

        return $this->cast($row['value'], $row['type']);
    }

    /** Ustaw pojedyncze ustawienie per-klub (upsert). */
    public function set(int $clubId, string $key, mixed $value, string $label = '', string $type = 'text'): void
    {
        $strValue = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;

        $stmt = $this->db->prepare(
            "INSERT INTO `club_settings` (club_id, `key`, `value`, `label`, `type`)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `label` = VALUES(`label`)"
        );
        $stmt->execute([$clubId, $key, $strValue, $label, $type]);
    }

    /** Pobierz wszystkie ustawienia per-klub jako key→value. */
    public function getAll(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT `key`, `value`, `type` FROM `club_settings` WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $this->cast($row['value'], $row['type']);
        }
        return $result;
    }

    /** Pobierz konfigurację SMTP dla klubu (lub null jeśli wyłączona). */
    public function getSmtpConfig(int $clubId): ?array
    {
        $all = $this->getAll($clubId);

        if (empty($all['smtp_enabled'])) {
            return null;
        }

        return [
            'host'       => $all['smtp_host']       ?? '',
            'port'       => (int)($all['smtp_port'] ?? 587),
            'secure'     => $all['smtp_secure']      ?? 'tls',
            'user'       => $all['smtp_user']        ?? '',
            'pass_enc'   => $all['smtp_pass_enc']    ?? '',
            'from_email' => $all['smtp_from_email']  ?? '',
            'from_name'  => $all['smtp_from_name']   ?? '',
        ];
    }

    /** Zapisz zestaw ustawień naraz. */
    public function setMany(int $clubId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($clubId, $key, $value);
        }
    }

    /**
     * Zwraca stan włączenia modułów dla klubu.
     * Wynik: ['members' => true, 'calendar' => false, ...]
     * Moduł bez wpisu w club_settings = domyślnie włączony (true).
     */
    public function getModules(int $clubId): array
    {
        $all = $this->getAll($clubId);
        $modules = [];
        foreach ($all as $key => $value) {
            if (str_starts_with($key, 'module_')) {
                $modules[substr($key, 7)] = (bool)$value;
            }
        }
        return $modules;
    }

    /**
     * Zapisuje włączone moduły dla klubu.
     * $enabled: tablica kluczy modułów, które mają być włączone.
     * Moduły z RolePermissionModel::MODULES spoza $enabled są wyłączane.
     * 'dashboard' jest zawsze włączony.
     */
    public function setModules(int $clubId, array $enabled): void
    {
        foreach (\App\Models\RolePermissionModel::MODULES as $mod => $cfg) {
            if ($mod === 'dashboard') {
                continue; // dashboard zawsze aktywny
            }
            $isEnabled = in_array($mod, $enabled, true) ? '1' : '0';
            $this->set($clubId, "module_{$mod}", $isEnabled, $cfg['label'], 'boolean');
        }
    }

    /** Usun wszystkie ustawienia per-klub. */
    public function deleteAll(int $clubId): void
    {
        $stmt = $this->db->prepare("DELETE FROM `club_settings` WHERE club_id = ?");
        $stmt->execute([$clubId]);
    }

    /** Rzutuj wartość na typ PHP na podstawie pola type. */
    private function cast(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number'  => is_numeric($value) ? (str_contains($value, '.') ? (float)$value : (int)$value) : 0,
            'boolean' => (bool)$value,
            'json'    => json_decode($value, true) ?? [],
            default   => $value,
        };
    }
}
