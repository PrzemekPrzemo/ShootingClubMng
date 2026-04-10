<?php

namespace App\Models;

class AgeCategoryModel extends BaseModel
{
    protected string $table = 'member_age_categories';
    public const DICTIONARY_KEY = 'categories';

    public function getAll(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM member_age_categories ORDER BY sort_order, age_from")->fetchAll();
        }
        $excl  = $this->buildExclNotIn($clubId);
        $stmt  = $this->db->prepare(
            "SELECT * FROM member_age_categories WHERE (club_id IS NULL{$excl}) OR club_id = ? ORDER BY sort_order, age_from"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function getExcludedGlobal(int $clubId): array
    {
        $ids = (new ClubDictionaryExclusionModel())->getExcludedIds($clubId, self::DICTIONARY_KEY);
        if (empty($ids)) return [];
        $in   = implode(',', $ids);
        return $this->db->query(
            "SELECT * FROM member_age_categories WHERE id IN ({$in}) AND club_id IS NULL ORDER BY sort_order, age_from"
        )->fetchAll();
    }

    private function buildExclNotIn(int $clubId): string
    {
        $ids = (new ClubDictionaryExclusionModel())->getExcludedIds($clubId, self::DICTIONARY_KEY);
        return $ids ? ' AND id NOT IN (' . implode(',', $ids) . ')' : '';
    }

    public function detectCategory(int $age): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM member_age_categories WHERE age_from <= ? AND age_to >= ? ORDER BY sort_order LIMIT 1");
        $stmt->execute([$age, $age]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Szuka kategorii pasującej do wieku, preferując wpisy per-klub nad globalnymi.
     * Kolejność: wpis klubowy > globalny; w obrębie grupy: sort_order ASC.
     */
    public function detectCategoryForClub(int $age, int $clubId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM member_age_categories
            WHERE age_from <= ? AND age_to >= ?
              AND (club_id = ? OR club_id IS NULL)
            ORDER BY (club_id IS NULL) ASC, sort_order ASC
            LIMIT 1
        ");
        $stmt->execute([$age, $age, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Przelicza i aktualizuje age_category_id dla wszystkich zawodników klubu
     * na podstawie birth_date i bieżących kategorii wiekowych.
     *
     * Zwraca tablicę statystyk:
     *   total, updated, no_birth_date, no_match, unchanged
     */
    public function recalculateForClub(int $clubId): array
    {
        $stats = ['total' => 0, 'updated' => 0, 'unchanged' => 0, 'no_birth_date' => 0, 'no_match' => 0];

        $stmt = $this->db->prepare(
            "SELECT id, birth_date, age_category_id FROM members WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        $members = $stmt->fetchAll();
        $stats['total'] = count($members);

        $upd = $this->db->prepare("UPDATE members SET age_category_id = ? WHERE id = ?");
        $today = new \DateTimeImmutable('today');

        foreach ($members as $m) {
            if (empty($m['birth_date'])) {
                $stats['no_birth_date']++;
                continue;
            }

            try {
                $birth = new \DateTimeImmutable($m['birth_date']);
            } catch (\Throwable) {
                $stats['no_birth_date']++;
                continue;
            }

            $age = (int)$birth->diff($today)->y;
            $cat = $this->detectCategoryForClub($age, $clubId);

            if ($cat === null) {
                $stats['no_match']++;
                continue;
            }

            if ((int)($m['age_category_id'] ?? 0) === (int)$cat['id']) {
                $stats['unchanged']++;
                continue;
            }

            $upd->execute([$cat['id'], $m['id']]);
            $stats['updated']++;
        }

        return $stats;
    }

    public function save(array $data): int
    {
        return $this->insert($data);
    }

    public function saveUpdate(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
