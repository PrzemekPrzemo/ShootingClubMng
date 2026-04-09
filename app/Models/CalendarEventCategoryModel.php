<?php

namespace App\Models;

class CalendarEventCategoryModel extends ClubScopedModel
{
    protected string $table = 'calendar_event_categories';

    public function getAll(): array
    {
        try {
            return $this->db->query("
                SELECT * FROM calendar_event_categories
                ORDER BY sort_order ASC, id ASC
            ")->fetchAll();
        } catch (\PDOException) {
            return $this->defaults();
        }
    }

    public function getActive(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM calendar_event_categories
                WHERE is_active = 1
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return $this->defaults();
        }
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateCategory(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function delete(int $id): bool
    {
        try {
            // Null out category_id in events that use this category
            $stmt = $this->db->prepare("UPDATE calendar_events SET category_id = NULL WHERE category_id = ?");
            $stmt->execute([$id]);

            $stmt2 = $this->db->prepare("DELETE FROM calendar_event_categories WHERE id = ?");
            $stmt2->execute([$id]);
            return $stmt2->rowCount() > 0;
        } catch (\PDOException) {
            return false;
        }
    }

    /** Fallback defaults when table doesn't exist yet */
    private function defaults(): array
    {
        return [
            ['id' => 1, 'name' => 'Zawody zewnętrzne', 'color' => 'info',      'icon' => 'trophy',         'is_active' => 1, 'sort_order' => 1],
            ['id' => 2, 'name' => 'Zebranie / spotkanie', 'color' => 'primary','icon' => 'people',         'is_active' => 1, 'sort_order' => 2],
            ['id' => 3, 'name' => 'Szkolenie / kurs',   'color' => 'success',  'icon' => 'mortarboard',    'is_active' => 1, 'sort_order' => 3],
            ['id' => 4, 'name' => 'Wyjazd',             'color' => 'warning',  'icon' => 'geo-alt',        'is_active' => 1, 'sort_order' => 4],
            ['id' => 5, 'name' => 'Inne',               'color' => 'secondary','icon' => 'calendar-event', 'is_active' => 1, 'sort_order' => 5],
        ];
    }
}
