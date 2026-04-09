<?php

namespace App\Models;

class WeaponModel extends ClubScopedModel
{
    protected string $table = 'weapons';

    public function getAll(array $filters = [], int $page = 1, int $perPage = 30): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]  = "(w.name LIKE ? OR w.serial_number LIKE ? OR w.caliber LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['type'])) {
            $where[]  = "w.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['condition'])) {
            $where[]  = "w.`condition` = ?";
            $params[] = $filters['condition'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[]  = "w.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT w.*,
                       m.first_name AS assigned_to_first,
                       m.last_name  AS assigned_to_last,
                       wa.assigned_date
                FROM weapons w
                LEFT JOIN weapon_assignments wa
                    ON wa.weapon_id = w.id AND wa.returned_date IS NULL
                LEFT JOIN members m ON m.id = wa.member_id
                WHERE {$whereClause}
                ORDER BY w.type, w.name";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function getActive(): array
    {
        return $this->db->query(
            "SELECT * FROM weapons WHERE is_active = 1 ORDER BY type, name"
        )->fetchAll();
    }

    public function getNeedingService(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM weapons
             WHERE `condition` IN ('wymaga_obslugi','uszkodzona')
               AND is_active = 1
             ORDER BY `condition` DESC, name"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCurrentAssignment(int $weaponId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT wa.*, m.first_name, m.last_name, m.member_number
            FROM weapon_assignments wa
            JOIN members m ON m.id = wa.member_id
            WHERE wa.weapon_id = ? AND wa.returned_date IS NULL
            LIMIT 1
        ");
        $stmt->execute([$weaponId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAssignmentHistory(int $weaponId): array
    {
        $stmt = $this->db->prepare("
            SELECT wa.*, m.first_name, m.last_name, m.member_number
            FROM weapon_assignments wa
            JOIN members m ON m.id = wa.member_id
            WHERE wa.weapon_id = ?
            ORDER BY wa.assigned_date DESC
        ");
        $stmt->execute([$weaponId]);
        return $stmt->fetchAll();
    }

    public function assign(int $weaponId, int $memberId, string $assignedDate, ?string $notes): int
    {
        // Close any existing open assignment first
        $this->db->prepare(
            "UPDATE weapon_assignments SET returned_date = CURDATE()
             WHERE weapon_id = ? AND returned_date IS NULL"
        )->execute([$weaponId]);

        $this->db->prepare(
            "INSERT INTO weapon_assignments (weapon_id, member_id, assigned_date, notes)
             VALUES (?, ?, ?, ?)"
        )->execute([$weaponId, $memberId, $assignedDate, $notes ?: null]);

        return (int)$this->db->lastInsertId();
    }

    public function returnWeapon(int $assignmentId, string $returnedDate): void
    {
        $this->db->prepare(
            "UPDATE weapon_assignments SET returned_date = ? WHERE id = ?"
        )->execute([$returnedDate, $assignmentId]);
    }

    public function createWeapon(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `weapons` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function updateWeapon(int $id, array $data): void
    {
        $set    = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        $this->db->prepare("UPDATE `weapons` SET {$set} WHERE id = ?")->execute($values);
    }

    public function deleteWeapon(int $id): void
    {
        // Soft delete
        $this->db->prepare("UPDATE weapons SET is_active = 0 WHERE id = ?")->execute([$id]);
    }
}
