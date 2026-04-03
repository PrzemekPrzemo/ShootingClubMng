<?php

namespace App\Models;

class CompetitionModel extends BaseModel
{
    protected string $table = 'competitions';

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]  = "c.name LIKE ?";
            $params[] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[]  = "c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['year'])) {
            $where[]  = "YEAR(c.competition_date) = ?";
            $params[] = $filters['year'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT c.*, d.name AS discipline_name,
                       (SELECT COUNT(*) FROM competition_entries WHERE competition_id = c.id) AS entry_count
                FROM competitions c
                LEFT JOIN disciplines d ON d.id = c.discipline_id
                WHERE {$whereClause}
                ORDER BY c.competition_date DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function getWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, d.name AS discipline_name
            FROM competitions c
            LEFT JOIN disciplines d ON d.id = c.discipline_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updateCompetition(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    // Groups
    public function getGroups(int $competitionId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM competition_groups WHERE competition_id = ? ORDER BY start_time");
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }

    public function createGroup(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `competition_groups` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    // Entries
    public function getEntries(int $competitionId): array
    {
        $stmt = $this->db->prepare("
            SELECT ce.*, m.first_name, m.last_name, m.member_number,
                   cg.name AS group_name, cg.start_time AS group_start_time,
                   mac.name AS age_category_name,
                   mc.name AS member_class_name, mc.short_code AS member_class_code
            FROM competition_entries ce
            JOIN members m ON m.id = ce.member_id
            LEFT JOIN competition_groups cg ON cg.id = ce.group_id
            LEFT JOIN member_age_categories mac ON mac.id = m.age_category_id
            LEFT JOIN member_classes mc ON mc.id = m.member_class_id
            WHERE ce.competition_id = ?
            ORDER BY cg.start_time, m.last_name, m.first_name
        ");
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }

    public function addEntry(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `competition_entries` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function removeEntry(int $entryId): void
    {
        $this->db->prepare("DELETE FROM competition_entries WHERE id = ?")->execute([$entryId]);
    }

    // Results
    public function getResults(int $competitionId): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.*, m.first_name, m.last_name, m.member_number,
                   cg.name AS group_name
            FROM competition_results cr
            JOIN members m ON m.id = cr.member_id
            LEFT JOIN competition_groups cg ON cg.id = cr.group_id
            WHERE cr.competition_id = ?
            ORDER BY cr.place ASC, cr.score DESC
        ");
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }

    public function upsertResult(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO competition_results (competition_id, member_id, group_id, score, place, notes, entered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                group_id = VALUES(group_id),
                score    = VALUES(score),
                place    = VALUES(place),
                notes    = VALUES(notes),
                entered_by = VALUES(entered_by)
        ");
        $stmt->execute([
            $data['competition_id'],
            $data['member_id'],
            $data['group_id'],
            $data['score'],
            $data['place'],
            $data['notes'],
            $data['entered_by'],
        ]);
    }

    // ── Competition Events (Konkurencje) ─────────────────────────────

    public function getEvents(int $competitionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ce.*, (SELECT COUNT(*) FROM competition_event_results WHERE competition_event_id = ce.id) AS result_count
             FROM competition_events ce
             WHERE ce.competition_id = ?
             ORDER BY ce.sort_order, ce.id"
        );
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }

    public function getEvent(int $eventId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ce.*, c.name AS competition_name, c.competition_date, c.location, c.id AS competition_id
             FROM competition_events ce
             JOIN competitions c ON c.id = ce.competition_id
             WHERE ce.id = ?"
        );
        $stmt->execute([$eventId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function addEvent(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `competition_events` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function deleteEvent(int $eventId): void
    {
        $this->db->prepare("DELETE FROM competition_events WHERE id = ?")->execute([$eventId]);
    }

    // ── Per-Event Results ────────────────────────────────────────────

    public function getEventResults(int $eventId): array
    {
        $stmt = $this->db->prepare("
            SELECT cer.*,
                   m.first_name, m.last_name, m.member_number,
                   mc.name      AS member_class_name,
                   mc.short_code AS member_class_code,
                   mac.name     AS age_category_name,
                   ce_entry.class AS sport_class,
                   cg.name      AS group_name,
                   cg.start_time
            FROM competition_event_results cer
            JOIN members m               ON m.id = cer.member_id
            LEFT JOIN member_classes mc  ON mc.id = m.member_class_id
            LEFT JOIN member_age_categories mac ON mac.id = m.age_category_id
            LEFT JOIN competition_events ev ON ev.id = cer.competition_event_id
            LEFT JOIN competition_entries ce_entry
                   ON ce_entry.competition_id = ev.competition_id
                  AND ce_entry.member_id = cer.member_id
            LEFT JOIN competition_groups cg ON cg.id = ce_entry.group_id
            WHERE cer.competition_event_id = ?
            ORDER BY cer.place ASC, cer.score DESC, m.last_name, m.first_name
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public function getEventResultsMap(int $eventId): array
    {
        $rows = $this->getEventResults($eventId);
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['member_id']] = $r;
        }
        return $map;
    }

    public function upsertEventResult(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO competition_event_results
                (competition_event_id, member_id, score, score_inner, place, notes, entered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                score       = VALUES(score),
                score_inner = VALUES(score_inner),
                place       = VALUES(place),
                notes       = VALUES(notes),
                entered_by  = VALUES(entered_by)
        ");
        $stmt->execute([
            $data['competition_event_id'],
            $data['member_id'],
            $data['score'] !== '' ? $data['score'] : null,
            $data['score_inner'] !== '' ? $data['score_inner'] : null,
            $data['place'] !== '' ? $data['place'] : null,
            $data['notes'],
            $data['entered_by'],
        ]);
    }

    // ── Ranking ──────────────────────────────────────────────────────

    public function getRankingForMember(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.*, c.name AS competition_name, c.competition_date, d.name AS discipline_name
            FROM competition_results cr
            JOIN competitions c ON c.id = cr.competition_id
            LEFT JOIN disciplines d ON d.id = c.discipline_id
            WHERE cr.member_id = ?
            ORDER BY c.competition_date DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function getUpcoming(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, d.name AS discipline_name,
                   (SELECT COUNT(*) FROM competition_entries WHERE competition_id = c.id) AS entry_count
            FROM competitions c
            LEFT JOIN disciplines d ON d.id = c.discipline_id
            WHERE c.competition_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
              AND c.status IN ('planowane','otwarte')
            ORDER BY c.competition_date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
