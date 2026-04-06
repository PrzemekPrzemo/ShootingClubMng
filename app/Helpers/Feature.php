<?php
namespace App\Helpers;

class Feature
{
    private static ?array $cache = null;

    public static function enabled(string $name): bool
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                $rows = \App\Helpers\Database::pdo()
                    ->query("SELECT `key`, value FROM settings WHERE `key` LIKE 'feature_%'")
                    ->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    self::$cache[substr($r['key'], 8)] = (bool)(int)$r['value'];
                }
            } catch (\Throwable) {
                // DB not ready or table missing — all features enabled
            }
        }
        // default: enabled if no row exists
        return self::$cache[$name] ?? true;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
