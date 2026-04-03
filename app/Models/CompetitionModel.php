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
                   cg.name AS group_name
            FROM competition_entries ce
            JOIN members m ON m.id = ce.member_id
            LEFT JOIN competition_groups cg ON cg.id = ce.group_id
            WHERE ce.competition_id = ?
            ORDER BY m.last_name, m.first_name
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
