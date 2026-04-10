<?php

namespace App\Models;

class LicenseTypeModel extends BaseModel
{
    protected string $table = 'license_types';
    public const DICTIONARY_KEY = 'license_types';

    public function getAll(): array
    {
        try {
            $clubId = \App\Helpers\ClubContext::current();
            if ($clubId !== null) {
                $excl = $this->buildExclNotIn($clubId);
                $stmt = $this->db->prepare("SELECT * FROM license_types WHERE (club_id IS NULL{$excl}) OR club_id = ? ORDER BY sort_order, name");
                $stmt->execute([$clubId]);
                return $stmt->fetchAll();
            }
            return $this->db->query(
                "SELECT * FROM license_types ORDER BY sort_order, name"
            )->fetchAll();
        } catch (\PDOException) {
            // migration_v7 not yet run — return legacy types
            return [
                ['id' => null, 'name' => 'Zawodnicza',        'short_code' => 'zawodnicza', 'is_active' => 1, 'validity_months' => 12,   'description' => null, 'sort_order' => 1],
                ['id' => null, 'name' => 'Trenerska',         'short_code' => 'trenerska',  'is_active' => 1, 'validity_months' => 12,   'description' => null, 'sort_order' => 2],
                ['id' => null, 'name' => 'Patent',            'short_code' => 'patent',     'is_active' => 1, 'validity_months' => null, 'description' => null, 'sort_order' => 3],
                ['id' => null, 'name' => 'Licencja sędziowska','short_code' => 'sedziowska','is_active' => 1, 'validity_months' => 12,   'description' => null, 'sort_order' => 4],
            ];
        }
    }

    public function getActive(): array
    {
        try {
            $clubId = \App\Helpers\ClubContext::current();
            if ($clubId !== null) {
                $excl = $this->buildExclNotIn($clubId);
                $stmt = $this->db->prepare("SELECT * FROM license_types WHERE is_active = 1 AND ((club_id IS NULL{$excl}) OR club_id = ?) ORDER BY sort_order, name");
                $stmt->execute([$clubId]);
                return $stmt->fetchAll();
            }
            return $this->db->query(
                "SELECT * FROM license_types WHERE is_active = 1 ORDER BY sort_order, name"
            )->fetchAll();
        } catch (\PDOException) {
            return array_filter($this->getAll(), fn($r) => $r['is_active']);
        }
    }

    public function getExcludedGlobal(int $clubId): array
    {
        $ids = (new ClubDictionaryExclusionModel())->getExcludedIds($clubId, self::DICTIONARY_KEY);
        if (empty($ids)) return [];
        $in  = implode(',', $ids);
        return $this->db->query(
            "SELECT * FROM license_types WHERE id IN ({$in}) AND club_id IS NULL ORDER BY sort_order, name"
        )->fetchAll();
    }

    private function buildExclNotIn(int $clubId): string
    {
        $ids = (new ClubDictionaryExclusionModel())->getExcludedIds($clubId, self::DICTIONARY_KEY);
        return $ids ? ' AND id NOT IN (' . implode(',', $ids) . ')' : '';
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
        $this->db->prepare(
            "UPDATE license_types SET is_active = 1 - is_active WHERE id = ?"
        )->execute([$id]);
    }

    public function isUsed(int $id): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM licenses WHERE license_type_id = ?"
            );
            $stmt->execute([$id]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\PDOException) {
            return false;
        }
    }
}
