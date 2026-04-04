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
        try {
            $stmt = $this->db->prepare("
                SELECT ce.*,
                       m.first_name, m.last_name, m.member_number,
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
        } catch (\PDOException) {
            // Fallback without new columns if migration not run
            $stmt = $this->db->prepare("
                SELECT ce.id, ce.competition_id, ce.member_id, ce.group_id, ce.class,
                       ce.status, ce.registered_by, ce.registered_at,
                       NULL AS start_fee_paid, NULL AS discount,
                       m.first_name, m.last_name, m.member_number,
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
        try {
            $stmt = $this->db->prepare(
                "SELECT ce.*,
                        (SELECT COUNT(*) FROM competition_event_results WHERE competition_event_id = ce.id) AS result_count
                 FROM competition_events ce
                 WHERE ce.competition_id = ?
                 ORDER BY ce.sort_order, ce.id"
            );
            $stmt->execute([$competitionId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            // Fallback without fee columns (pre-migration)
            $stmt = $this->db->prepare(
                "SELECT ce.id, ce.competition_id, ce.name, ce.shots_count, ce.scoring_type, ce.sort_order,
                        NULL AS fee_own_weapon, NULL AS fee_club_weapon,
                        (SELECT COUNT(*) FROM competition_event_results WHERE competition_event_id = ce.id) AS result_count
                 FROM competition_events ce
                 WHERE ce.competition_id = ?
                 ORDER BY ce.sort_order, ce.id"
            );
            $stmt->execute([$competitionId]);
            return $stmt->fetchAll();
        }
    }

    /**
     * Calculate total entry fee for a competitor based on their selected events and weapon type.
     */
    public function calcEntryFee(int $entryId): float
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cee.weapon_type,
                       COALESCE(ce.discount, 0) AS discount,
                       ev.fee_own_weapon,
                       ev.fee_club_weapon
                FROM competition_entries ce
                JOIN competition_entry_events cee ON cee.competition_entry_id = ce.id
                JOIN competition_events ev ON ev.id = cee.competition_event_id
                WHERE ce.id = ?
            ");
            $stmt->execute([$entryId]);
            $rows  = $stmt->fetchAll();
            if (empty($rows)) return 0.0;

            $total    = 0.0;
            $discount = (float)$rows[0]['discount'];
            foreach ($rows as $r) {
                if ($r['weapon_type'] === 'klubowa') {
                    $total += (float)($r['fee_club_weapon'] ?? 0);
                } else {
                    $total += (float)($r['fee_own_weapon'] ?? 0);
                }
            }
            return max(0.0, $total - $discount);
        } catch (\PDOException) {
            return 0.0;
        }
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

    // ── Competition Judges ───────────────────────────────────────────

    public function getCompetitionJudges(int $competitionId): array
    {
        $stmt = $this->db->prepare("
            SELECT cj.*, m.first_name, m.last_name, m.member_number,
                   jl.judge_class, jl.valid_until AS license_valid_until,
                   d.name AS discipline_name
            FROM competition_judges cj
            JOIN members m ON m.id = cj.member_id
            LEFT JOIN judge_licenses jl ON jl.member_id = cj.member_id
                AND jl.valid_until = (
                    SELECT MAX(valid_until) FROM judge_licenses
                    WHERE member_id = cj.member_id
                )
            LEFT JOIN disciplines d ON d.id = jl.discipline_id
            WHERE cj.competition_id = ?
            ORDER BY FIELD(cj.role,'glowny','liniowy','obliczeniowy','bezpieczenstwa','protokolant')
        ");
        $stmt->execute([$competitionId]);
        return $stmt->fetchAll();
    }

    public function addJudge(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `competition_judges` (`{$cols}`) VALUES ({$holds})")->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function removeJudge(int $id): void
    {
        $this->db->prepare("DELETE FROM competition_judges WHERE id = ?")->execute([$id]);
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

    // ── Entry status management (portal approval) ────────────────────

    public function changeEntryStatus(int $entryId, string $status): void
    {
        $this->db->prepare("UPDATE competition_entries SET status = ? WHERE id = ?")
                 ->execute([$status, $entryId]);
    }

    public function toggleStartFee(int $entryId): void
    {
        try {
            $this->db->prepare("UPDATE competition_entries SET start_fee_paid = 1 - start_fee_paid WHERE id = ?")
                     ->execute([$entryId]);
        } catch (\PDOException) {}
    }

    /**
     * Returns [event_id => weapon_type] map for an entry.
     */
    public function getEntryEventIds(int $entryId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT competition_event_id, weapon_type FROM competition_entry_events WHERE competition_entry_id = ?"
            );
            $stmt->execute([$entryId]);
            $result = [];
            foreach ($stmt->fetchAll() as $row) {
                $result[(int)$row['competition_event_id']] = $row['weapon_type'];
            }
            return $result;
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Saves per-event weapon types. $eventWeapons = [event_id => 'własna'|'klubowa'].
     */
    public function setEntryEvents(int $entryId, array $eventWeapons): void
    {
        try {
            $this->db->prepare("DELETE FROM competition_entry_events WHERE competition_entry_id = ?")
                     ->execute([$entryId]);
            foreach ($eventWeapons as $evId => $wt) {
                $wt = in_array($wt, ['własna', 'klubowa']) ? $wt : 'własna';
                $this->db->prepare(
                    "INSERT IGNORE INTO competition_entry_events (competition_entry_id, competition_event_id, weapon_type) VALUES (?, ?, ?)"
                )->execute([$entryId, (int)$evId, $wt]);
            }
        } catch (\PDOException) {}
    }

    /**
     * Returns all entries for a competition grouped by entry, each with
     * their selected events and any existing per-event results.
     * Returns: [ ['entry_id'=>, 'member_id'=>, ..., 'events'=>[...]], ... ]
     */
    public function getEntriesWithEventResults(int $competitionId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    ce.id          AS entry_id,
                    ce.member_id,
                    ce.start_fee_paid,
                    m.first_name,
                    m.last_name,
                    m.member_number,
                    mc.name        AS member_class_name,
                    mc.short_code  AS member_class_code,
                    ce.class       AS entry_class,
                    cg.name        AS group_name,
                    cee.competition_event_id AS event_id,
                    cee.weapon_type,
                    ev.name        AS event_name,
                    ev.scoring_type,
                    ev.shots_count,
                    ev.sort_order  AS event_sort,
                    cer.score,
                    cer.score_inner,
                    cer.place,
                    cer.notes
                FROM competition_entries ce
                JOIN members m ON m.id = ce.member_id
                LEFT JOIN member_classes mc ON mc.id = m.member_class_id
                LEFT JOIN competition_groups cg ON cg.id = ce.group_id
                JOIN competition_entry_events cee ON cee.competition_entry_id = ce.id
                JOIN competition_events ev ON ev.id = cee.competition_event_id
                LEFT JOIN competition_event_results cer
                    ON cer.competition_event_id = cee.competition_event_id
                    AND cer.member_id = ce.member_id
                WHERE ce.competition_id = ?
                ORDER BY m.last_name, m.first_name, ev.sort_order
            ");
            $stmt->execute([$competitionId]);
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }

        $entries = [];
        foreach ($rows as $row) {
            $eid = $row['entry_id'];
            if (!isset($entries[$eid])) {
                $entries[$eid] = [
                    'entry_id'          => $eid,
                    'member_id'         => $row['member_id'],
                    'first_name'        => $row['first_name'],
                    'last_name'         => $row['last_name'],
                    'member_number'     => $row['member_number'],
                    'member_class_name' => $row['member_class_name'],
                    'member_class_code' => $row['member_class_code'],
                    'entry_class'       => $row['entry_class'],
                    'group_name'        => $row['group_name'],
                    'start_fee_paid'    => $row['start_fee_paid'],
                    'events'            => [],
                ];
            }
            $entries[$eid]['events'][] = [
                'event_id'     => $row['event_id'],
                'weapon_type'  => $row['weapon_type'],
                'event_name'   => $row['event_name'],
                'scoring_type' => $row['scoring_type'],
                'shots_count'  => $row['shots_count'],
                'score'        => $row['score'],
                'score_inner'  => $row['score_inner'],
                'place'        => $row['place'],
                'notes'        => $row['notes'],
            ];
        }
        return array_values($entries);
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
