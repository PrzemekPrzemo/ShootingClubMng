<?php

namespace App\Models;

class CalendarEventModel extends ClubScopedModel
{
    protected string $table = 'calendar_events';

    /** Returns all custom events for a given month, grouped by day. */
    public function getForMonth(int $year, int $month, bool $publicOnly = false): array
    {
        try {
            $cid = $this->clubId();
            $sql = "SELECT ce.*, u.full_name AS created_by_name
                    FROM calendar_events ce
                    LEFT JOIN users u ON u.id = ce.created_by
                    WHERE ((YEAR(ce.event_date) = ? AND MONTH(ce.event_date) = ?)
                        OR (ce.event_date_end IS NOT NULL
                            AND ce.event_date <= LAST_DAY(?)
                            AND ce.event_date_end >= ?))";
            $firstOfMonth = sprintf('%04d-%02d-01', $year, $month);
            $params = [$year, $month, $firstOfMonth, $firstOfMonth];
            if ($cid !== null) { $sql .= " AND ce.club_id = ?"; $params[] = $cid; }
            if ($publicOnly)   { $sql .= " AND ce.is_public = 1"; }
            $sql .= " ORDER BY ce.event_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getAll(): array
    {
        try {
            $cid = $this->clubId();
            $sql = "SELECT ce.*, u.full_name AS created_by_name
                    FROM calendar_events ce
                    LEFT JOIN users u ON u.id = ce.created_by";
            $params = [];
            if ($cid !== null) { $sql .= " WHERE ce.club_id = ?"; $params[] = $cid; }
            $sql .= " ORDER BY ce.event_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    public function getUpcoming(int $limit = 10, bool $publicOnly = false): array
    {
        try {
            $cid = $this->clubId();
            $sql = "SELECT * FROM calendar_events WHERE event_date >= CURDATE()";
            $params = [];
            if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
            if ($publicOnly)   { $sql .= " AND is_public = 1"; }
            $sql .= " ORDER BY event_date ASC LIMIT ?";
            $params[] = $limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
