<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

class ClubCustomizationModel
{
    private PDO $db;

    private const DEFAULTS = [
        'logo_path'     => null,
        'primary_color' => '#0d6efd',
        'navbar_bg'     => '#212529',
        'custom_css'    => null,
        'subdomain'     => null,
    ];

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    /** Pobierz customizację dla klubu (z fallback do domyślnych wartości). */
    public function getForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `club_customization` WHERE club_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();

        if (!$row) {
            return array_merge(self::DEFAULTS, ['club_id' => $clubId]);
        }

        return $row;
    }

    /** Pobierz customizację dla aktualnego klubu z kontekstu. */
    public static function getForCurrentClub(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return self::DEFAULTS;
        }
        return (new self())->getForClub($clubId);
    }

    /** Zapisz customizację (upsert). */
    public function save(int $clubId, array $data): void
    {
        $allowed = ['logo_path', 'primary_color', 'navbar_bg', 'custom_css', 'subdomain'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        $existing = $this->getForClub($clubId);

        if (isset($existing['club_id']) && $this->exists($clubId)) {
            // UPDATE
            if (empty($filtered)) {
                return;
            }
            $set = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($filtered))) . ' = ?';
            $stmt = $this->db->prepare(
                "UPDATE `club_customization` SET {$set} WHERE club_id = ?"
            );
            $stmt->execute([...array_values($filtered), $clubId]);
        } else {
            // INSERT
            $filtered['club_id'] = $clubId;
            $cols  = implode('`, `', array_keys($filtered));
            $holds = implode(', ', array_fill(0, count($filtered), '?'));
            $stmt  = $this->db->prepare(
                "INSERT INTO `club_customization` (`{$cols}`) VALUES ({$holds})"
            );
            $stmt->execute(array_values($filtered));
        }
    }

    /** Sprawdza czy rekord customizacji istnieje dla danego klubu. */
    private function exists(int $clubId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM `club_customization` WHERE club_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId]);
        return (bool)$stmt->fetchColumn();
    }

    /** Pobierz subdomenę dla klubu. */
    public function getSubdomain(int $clubId): ?string
    {
        $row = $this->getForClub($clubId);
        return $row['subdomain'] ?? null;
    }

    /** Sprawdź czy subdomena jest już zajęta (przez inny klub). */
    public function isSubdomainTaken(string $subdomain, ?int $excludeClubId = null): bool
    {
        $sql = "SELECT club_id FROM `club_customization` WHERE subdomain = ?";
        $params = [$subdomain];

        if ($excludeClubId !== null) {
            $sql .= " AND club_id != ?";
            $params[] = $excludeClubId;
        }

        $stmt = $this->db->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }
}
