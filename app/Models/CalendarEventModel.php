<?php

namespace App\Models;

class CalendarEventModel extends BaseModel
{
    protected string $table = 'calendar_events';

    /** Returns all custom events for a given month, grouped by day. */
    public function getForMonth(int $year, int $month): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ce.*, u.full_name AS created_by_name
                FROM calendar_events ce
                LEFT JOIN users u ON u.id = ce.created_by
                WHERE (YEAR(ce.event_date) = ? AND MONTH(ce.event_date) = ?)
                   OR (ce.event_date_end IS NOT NULL
                       AND ce.event_date <= LAST_DAY(?)
                       AND ce.event_date_end >= ?)
                ORDER BY ce.event_date ASC
            ");
            $firstOfMonth = sprintf('%04d-%02d-01', $year, $month);
            $stmt->execute([$year, $month, $firstOfMonth, $firstOfMonth]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getAll(): array
    {
        try {
            return $this->db->query("
                SELECT ce.*, u.full_name AS created_by_name
                FROM calendar_events ce
                LEFT JOIN users u ON u.id = ce.created_by
                ORDER BY ce.event_date DESC
            ")->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getUpcoming(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM calendar_events
                WHERE event_date >= CURDATE()
                ORDER BY event_date ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateEvent(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
