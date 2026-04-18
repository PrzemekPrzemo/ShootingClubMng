<?php

namespace App\Models;

class MemberPortalModel extends BaseModel
{
    protected string $table = 'members';

    /**
     * Returns all competition event results for a member with competition/event names.
     */
    public function getMemberResults(int $memberId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cer.score, cer.score_inner, cer.place, cer.notes,
                       ce.name AS event_name,
                       c.name AS competition_name,
                       c.competition_date,
                       c.location
                FROM competition_event_results cer
                JOIN competition_events ce ON ce.id = cer.competition_event_id
                JOIN competitions c ON c.id = ce.competition_id
                WHERE cer.member_id = ?
                ORDER BY c.competition_date DESC, ce.name
            ");
            $stmt->execute([$memberId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns upcoming competitions from member's own club (open + planned) with entry status.
     * Only is_public = 1 shown to athletes.
     */
    public function getUpcomingCompetitions(int $memberId, ?int $clubId = null): array
    {
        try {
            $sql = "SELECT c.*, d.name AS discipline_name, cl.name AS club_name,
                           ce.id AS entry_id, ce.status AS entry_status
                    FROM competitions c
                    LEFT JOIN disciplines d ON d.id = c.discipline_id
                    LEFT JOIN clubs cl ON cl.id = c.club_id
                    LEFT JOIN competition_entries ce ON ce.competition_id = c.id AND ce.member_id = ?
                    WHERE c.status IN ('otwarte','planowane')
                      AND c.competition_date >= CURDATE()
                      AND c.is_public = 1";
            $params = [$memberId];
            if ($clubId !== null) { $sql .= " AND c.club_id = ?"; $params[] = $clubId; }
            $sql .= " ORDER BY c.competition_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns open competitions from member's own club with entry status (if any).
     * Only is_public = 1 shown to athletes.
     */
    public function getOpenCompetitions(int $memberId, ?int $clubId = null): array
    {
        try {
            $sql = "SELECT c.*,
                           d.name AS discipline_name,
                           ce.id AS entry_id,
                           ce.status AS entry_status,
                           ce.start_fee_paid
                    FROM competitions c
                    LEFT JOIN disciplines d ON d.id = c.discipline_id
                    LEFT JOIN competition_entries ce
                           ON ce.competition_id = c.id AND ce.member_id = ?
                    WHERE c.status = 'otwarte'
                      AND c.is_public = 1";
            $params = [$memberId];
            if ($clubId !== null) { $sql .= " AND c.club_id = ?"; $params[] = $clubId; }
            $sql .= " ORDER BY c.competition_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns all entries for a member (past and present), scoped to member's own club.
     */
    public function getMemberEntries(int $memberId, ?int $clubId = null): array
    {
        try {
            $sql = "SELECT ce.*, c.name AS competition_name, c.competition_date,
                           c.location, c.status AS competition_status
                    FROM competition_entries ce
                    JOIN competitions c ON c.id = ce.competition_id
                    WHERE ce.member_id = ?";
            $params = [$memberId];
            if ($clubId !== null) { $sql .= " AND c.club_id = ?"; $params[] = $clubId; }
            $sql .= " ORDER BY c.competition_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns competition event IDs selected for an entry.
     */
    public function getEntryEventIds(int $entryId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT competition_event_id FROM competition_entry_events
                WHERE competition_entry_id = ?
            ");
            $stmt->execute([$entryId]);
            return array_column($stmt->fetchAll(), 'competition_event_id');
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns payment history summary for member in given year, scoped to member's club.
     */
    public function getFeesSummary(int $memberId, int $year, ?int $clubId = null): array
    {
        try {
            $sql = "SELECT p.*, pt.name AS type_name, pt.category
                    FROM payments p
                    JOIN payment_types pt ON pt.id = p.payment_type_id
                    WHERE p.member_id = ? AND YEAR(p.payment_date) = ?";
            $params = [$memberId, $year];
            if ($clubId !== null) { $sql .= " AND p.club_id = ?"; $params[] = $clubId; }
            $sql .= " ORDER BY p.payment_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns active licenses for a member.
     */
    public function getMemberLicenses(int $memberId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, d.name AS discipline_name
                FROM licenses l
                LEFT JOIN disciplines d ON d.id = l.discipline_id
                WHERE l.member_id = ? AND l.status = 'aktywna'
                ORDER BY l.valid_until DESC
            ");
            $stmt->execute([$memberId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns medical exams for a member.
     */
    public function getMemberExams(int $memberId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, et.name AS exam_type_name, et.validity_months
                FROM member_medical_exams e
                LEFT JOIN medical_exam_types et ON et.id = e.exam_type_id
                WHERE e.member_id = ?
                ORDER BY e.exam_date DESC
            ");
            $stmt->execute([$memberId]);
            return $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Returns statistics per discipline/event for Chart.js.
     * Returns ['labels' => [...], 'scores' => [...], 'competitions' => [...]]
     */
    public function getMemberStats(int $memberId): array
    {
        try {
            // Last 20 results ordered by date for trend chart
            $stmt = $this->db->prepare("
                SELECT cer.score, cer.place,
                       ce.name AS event_name,
                       c.name AS competition_name,
                       c.competition_date
                FROM competition_event_results cer
                JOIN competition_events ce ON ce.id = cer.competition_event_id
                JOIN competitions c ON c.id = ce.competition_id
                WHERE cer.member_id = ? AND cer.score IS NOT NULL
                ORDER BY c.competition_date ASC, ce.name
                LIMIT 20
            ");
            $stmt->execute([$memberId]);
            $rows = $stmt->fetchAll();

            $labels = [];
            $scores = [];
            foreach ($rows as $r) {
                $labels[] = date('d.m.Y', strtotime($r['competition_date'])) . ' — ' . $r['event_name'];
                $scores[] = (float)$r['score'];
            }

            // Summary stats
            $stmt2 = $this->db->prepare("
                SELECT COUNT(*) AS total_starts,
                       MAX(score) AS best_score,
                       AVG(score) AS avg_score,
                       SUM(place = 1) AS gold,
                       SUM(place = 2) AS silver,
                       SUM(place = 3) AS bronze
                FROM competition_event_results
                WHERE member_id = ? AND score IS NOT NULL
            ");
            $stmt2->execute([$memberId]);
            $summary = $stmt2->fetch();

            return [
                'labels'  => $labels,
                'scores'  => $scores,
                'summary' => $summary ?: [],
            ];
        } catch (\PDOException) {
            return ['labels' => [], 'scores' => [], 'summary' => []];
        }
    }
}
