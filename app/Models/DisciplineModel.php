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

    // ── Event templates ──────────────────────────────────────────────

    public function getEventTemplates(int $disciplineId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM discipline_event_templates
             WHERE discipline_id = ?
             ORDER BY sort_order, name"
        );
        $stmt->execute([$disciplineId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns all active templates grouped by discipline.
     * [ ['discipline' => [...], 'templates' => [...]], ... ]
     */
    public function getAllTemplatesGrouped(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT det.*, d.name AS discipline_name, d.short_code
                FROM discipline_event_templates det
                JOIN disciplines d ON d.id = det.discipline_id
                WHERE det.is_active = 1 AND d.is_active = 1
                ORDER BY d.name, det.sort_order, det.name
            ");
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $did = (int)$row['discipline_id'];
            if (!isset($grouped[$did])) {
                $grouped[$did] = [
                    'discipline' => [
                        'id'         => $did,
                        'name'       => $row['discipline_name'],
                        'short_code' => $row['short_code'],
                    ],
                    'templates' => [],
                ];
            }
            $grouped[$did]['templates'][] = $row;
        }
        return array_values($grouped);
    }

    public function saveTemplate(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `discipline_event_templates` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function updateTemplate(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE `discipline_event_templates` SET {$set} WHERE id = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function deleteTemplate(int $id): void
    {
        $this->db->prepare("DELETE FROM discipline_event_templates WHERE id = ?")->execute([$id]);
    }

    public function toggleTemplate(int $id): void
    {
        $this->db->prepare("UPDATE discipline_event_templates SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }

    public function findTemplate(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM discipline_event_templates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
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
