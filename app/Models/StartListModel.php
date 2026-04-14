<?php

namespace App\Models;

use App\Helpers\ClubContext;
use PDO;

/**
 * StartListModel
 *
 * Manages all sl_* tables for the Start List Generator module.
 * Club scoping is handled manually via ClubContext::current().
 */
class StartListModel extends BaseModel
{
    protected string $table = 'sl_generators';

    // ── Generators ───────────────────────────────────────────────────────────

    public function getGenerators(): array
    {
        $clubId = ClubContext::current();
        if ($clubId === null) {
            $stmt = $this->db->query(
                "SELECT g.*, c.name AS competition_name
                 FROM sl_generators g
                 LEFT JOIN competitions c ON c.id = g.competition_id
                 ORDER BY g.created_at DESC"
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT g.*, c.name AS competition_name
                 FROM sl_generators g
                 LEFT JOIN competitions c ON c.id = g.competition_id
                 WHERE g.club_id = ?
                 ORDER BY g.created_at DESC"
            );
            $stmt->execute([$clubId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGenerator(int $id): ?array
    {
        $clubId = ClubContext::current();
        $sql    = "SELECT g.*, c.name AS competition_name
                   FROM sl_generators g
                   LEFT JOIN competitions c ON c.id = g.competition_id
                   WHERE g.id = ?";
        $params = [$id];
        if ($clubId !== null) {
            $sql    .= ' AND g.club_id = ?';
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createGenerator(array $data): int
    {
        return $this->insert($data);
    }

    public function updateGenerator(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function setGeneratorStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE sl_generators SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function deleteGenerator(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sl_generators WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ── Disciplines ───────────────────────────────────────────────────────────

    public function getDisciplines(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sl_disciplines WHERE generator_id = ? ORDER BY sort_order, id"
        );
        $stmt->execute([$generatorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDiscipline(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sl_disciplines WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function addDiscipline(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $stmt  = $this->db->prepare("INSERT INTO `sl_disciplines` (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function updateDiscipline(int $id, array $data): bool
    {
        $set  = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare("UPDATE `sl_disciplines` SET {$set} WHERE id = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function deleteDiscipline(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sl_disciplines WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Returns [code => id] map for a generator — used during CSV import.
     *
     * @return array<string, int>
     */
    public function getDisciplineCodeMap(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, code FROM sl_disciplines WHERE generator_id = ?"
        );
        $stmt->execute([$generatorId]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[strtolower($row['code'])] = (int)$row['id'];
        }
        return $map;
    }

    // ── Combos ────────────────────────────────────────────────────────────────

    /**
     * Returns combos with a 'discipline_ids' array key each.
     */
    public function getCombos(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, GROUP_CONCAT(ci.discipline_id ORDER BY ci.id) AS discipline_ids_raw
             FROM sl_combos c
             LEFT JOIN sl_combo_items ci ON ci.combo_id = c.id
             WHERE c.generator_id = ?
             GROUP BY c.id
             ORDER BY c.id"
        );
        $stmt->execute([$generatorId]);
        $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($combos as &$c) {
            $c['discipline_ids'] = $c['discipline_ids_raw']
                ? array_map('intval', explode(',', $c['discipline_ids_raw']))
                : [];
            unset($c['discipline_ids_raw']);
        }
        return $combos;
    }

    public function addCombo(int $generatorId, string $name, int $maxPerRelay, array $disciplineIds): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sl_combos (generator_id, name, max_per_relay) VALUES (?, ?, ?)"
            );
            $stmt->execute([$generatorId, $name, $maxPerRelay]);
            $comboId = (int)$this->db->lastInsertId();

            foreach ($disciplineIds as $did) {
                $this->db->prepare(
                    "INSERT INTO sl_combo_items (combo_id, discipline_id) VALUES (?, ?)"
                )->execute([$comboId, (int)$did]);
            }
            $this->db->commit();
            return $comboId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateCombo(int $id, string $name, int $maxPerRelay, array $disciplineIds): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE sl_combos SET name = ?, max_per_relay = ? WHERE id = ?"
            )->execute([$name, $maxPerRelay, $id]);

            $this->db->prepare("DELETE FROM sl_combo_items WHERE combo_id = ?")->execute([$id]);
            foreach ($disciplineIds as $did) {
                $this->db->prepare(
                    "INSERT INTO sl_combo_items (combo_id, discipline_id) VALUES (?, ?)"
                )->execute([$id, (int)$did]);
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteCombo(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sl_combos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ── Age categories ────────────────────────────────────────────────────────

    public function getAgeCategories(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sl_age_categories WHERE generator_id = ? ORDER BY sort_order, age_from"
        );
        $stmt->execute([$generatorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAgeCategory(array $data): int
    {
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $stmt  = $this->db->prepare("INSERT INTO `sl_age_categories` (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function updateAgeCategory(int $id, array $data): bool
    {
        $set  = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare("UPDATE `sl_age_categories` SET {$set} WHERE id = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function deleteAgeCategory(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sl_age_categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Resolve age category by age within a generator.
     *
     * @return array|null  Category row or null if not found
     */
    public function resolveAgeCategory(int $generatorId, int $age): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sl_age_categories
             WHERE generator_id = ? AND age_from <= ? AND age_to >= ?
             ORDER BY sort_order, age_from
             LIMIT 1"
        );
        $stmt->execute([$generatorId, $age, $age]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ── Competitors ───────────────────────────────────────────────────────────

    /**
     * Return competitors with resolved age category name and discipline codes.
     */
    public function getCompetitors(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, cat.name AS age_category_name,
                    GROUP_CONCAT(d.code ORDER BY d.sort_order SEPARATOR ', ') AS discipline_codes,
                    GROUP_CONCAT(d.id   ORDER BY d.sort_order) AS discipline_ids_raw
             FROM sl_competitors c
             LEFT JOIN sl_age_categories cat ON cat.id = c.age_category_id
             LEFT JOIN sl_competitor_disciplines cd ON cd.competitor_id = c.id
             LEFT JOIN sl_disciplines d ON d.id = cd.discipline_id
             WHERE c.generator_id = ?
             GROUP BY c.id
             ORDER BY c.last_name, c.first_name"
        );
        $stmt->execute([$generatorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['discipline_ids'] = $row['discipline_ids_raw']
                ? array_map('intval', explode(',', $row['discipline_ids_raw']))
                : [];
            unset($row['discipline_ids_raw']);
        }
        return $rows;
    }

    /**
     * Bulk-replace all competitors for a generator (wipe + re-insert).
     * Each row must have keys: generator_id, first_name, last_name,
     * birth_date, gender, age_category_id, and _discipline_ids (array of ints).
     */
    public function replaceCompetitors(int $generatorId, array $rows): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM sl_competitors WHERE generator_id = ?")->execute([$generatorId]);

            foreach ($rows as $row) {
                $disciplineIds = $row['_discipline_ids'] ?? [];
                unset($row['_discipline_ids']);

                $cols  = implode('`, `', array_keys($row));
                $holds = implode(', ', array_fill(0, count($row), '?'));
                $stmt  = $this->db->prepare(
                    "INSERT INTO `sl_competitors` (`{$cols}`) VALUES ({$holds})"
                );
                $stmt->execute(array_values($row));
                $competitorId = (int)$this->db->lastInsertId();

                foreach ($disciplineIds as $did) {
                    $this->db->prepare(
                        "INSERT INTO sl_competitor_disciplines (competitor_id, discipline_id) VALUES (?, ?)"
                    )->execute([$competitorId, (int)$did]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteCompetitors(int $generatorId): int
    {
        $stmt = $this->db->prepare("DELETE FROM sl_competitors WHERE generator_id = ?");
        $stmt->execute([$generatorId]);
        return (int)$stmt->rowCount();
    }

    // ── Relays (generated schedule) ──────────────────────────────────────────

    public function clearRelays(int $generatorId): void
    {
        $this->db->prepare("DELETE FROM sl_relays WHERE generator_id = ?")->execute([$generatorId]);
    }

    /**
     * Bulk-insert relay slots.
     *
     * @param array $relays  Each item: [generator_id, discipline_id, combo_id,
     *                                   slot_index, start_datetime, end_datetime]
     */
    public function insertRelays(array $relays): void
    {
        if (empty($relays)) return;
        $stmt = $this->db->prepare(
            "INSERT INTO sl_relays (generator_id, discipline_id, combo_id, slot_index, start_datetime, end_datetime)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($relays as $r) {
            $stmt->execute([
                $r['generator_id'],
                $r['discipline_id'],
                $r['combo_id'] ?? null,
                $r['slot_index'],
                $r['start_datetime'],
                $r['end_datetime'],
            ]);
        }
    }

    /**
     * Bulk-insert relay entries.
     *
     * @param array $entries  Each item: [relay_id, competitor_id,
     *                                    actual_discipline_id, lane]
     */
    public function insertRelayEntries(array $entries): void
    {
        if (empty($entries)) return;
        $stmt = $this->db->prepare(
            "INSERT INTO sl_relay_entries (relay_id, competitor_id, actual_discipline_id, lane)
             VALUES (?, ?, ?, ?)"
        );
        foreach ($entries as $e) {
            $stmt->execute([
                $e['relay_id'],
                $e['competitor_id'],
                $e['actual_discipline_id'],
                $e['lane'] ?? null,
            ]);
        }
    }

    public function getRelays(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, d.name AS discipline_name, d.code AS discipline_code,
                    d.gender_mode, d.lanes_count
             FROM sl_relays r
             JOIN sl_disciplines d ON d.id = r.discipline_id
             WHERE r.generator_id = ?
             ORDER BY r.start_datetime, r.id"
        );
        $stmt->execute([$generatorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return schedule grouped by discipline for the preview view.
     * Each group: ['discipline' => [...], 'relays' => [['relay' => [...], 'entries' => [...]], ...]]
     */
    public function getRelaysGroupedByDiscipline(int $generatorId): array
    {
        $relays  = $this->getRelays($generatorId);
        $entries = $this->getAllRelayEntries($generatorId);

        // Index entries by relay_id
        $byRelay = [];
        foreach ($entries as $e) {
            $byRelay[$e['relay_id']][] = $e;
        }

        // Group relays by discipline
        $groups = [];
        foreach ($relays as $relay) {
            $did = $relay['discipline_id'];
            if (!isset($groups[$did])) {
                $groups[$did] = [
                    'discipline' => [
                        'id'          => $relay['discipline_id'],
                        'name'        => $relay['discipline_name'],
                        'code'        => $relay['discipline_code'],
                        'gender_mode' => $relay['gender_mode'],
                        'lanes_count' => $relay['lanes_count'],
                    ],
                    'relays' => [],
                ];
            }
            $groups[$did]['relays'][] = [
                'relay'   => $relay,
                'entries' => $byRelay[$relay['id']] ?? [],
            ];
        }

        return array_values($groups);
    }

    /**
     * Return all relay entries for conflict detection.
     * Each row has: competitor_id, first_name, last_name, relay_id,
     *               relay_start_datetime, relay_end_datetime, discipline_name
     */
    public function getAllRelayEntries(int $generatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT re.*, c.first_name, c.last_name, c.gender,
                    r.start_datetime AS relay_start_datetime,
                    r.end_datetime   AS relay_end_datetime,
                    d.name           AS discipline_name,
                    d.code           AS discipline_code
             FROM sl_relay_entries re
             JOIN sl_competitors c ON c.id = re.competitor_id
             JOIN sl_relays r      ON r.id = re.relay_id
             JOIN sl_disciplines d ON d.id = re.actual_discipline_id
             WHERE r.generator_id = ?
             ORDER BY re.competitor_id, r.start_datetime"
        );
        $stmt->execute([$generatorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Full schedule for PDF: disciplines → relays → entries (sorted for printing).
     */
    public function getScheduleForPdf(int $generatorId): array
    {
        return $this->getRelaysGroupedByDiscipline($generatorId);
    }

    // ── Competition selector ─────────────────────────────────────────────────

    public function getCompetitionOptions(): array
    {
        $clubId = ClubContext::current();
        if ($clubId === null) {
            $stmt = $this->db->query(
                "SELECT id, name, competition_date FROM competitions
                 ORDER BY competition_date DESC LIMIT 100"
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, name, competition_date FROM competitions
                 WHERE club_id = ? ORDER BY competition_date DESC LIMIT 100"
            );
            $stmt->execute([$clubId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
