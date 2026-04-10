<?php

namespace App\Models;

class MedicalExamTypeModel extends BaseModel
{
    protected string $table = 'medical_exam_types';
    public const DICTIONARY_KEY = 'medical_exam_types';

    public function getAll(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM medical_exam_types ORDER BY sort_order, id")->fetchAll();
        }
        $excl = $this->buildExclNotIn($clubId);
        $stmt = $this->db->prepare("SELECT * FROM medical_exam_types WHERE (club_id IS NULL{$excl}) OR club_id = ? ORDER BY sort_order, id");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM medical_exam_types WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll();
        }
        $excl = $this->buildExclNotIn($clubId);
        $stmt = $this->db->prepare("SELECT * FROM medical_exam_types WHERE is_active = 1 AND ((club_id IS NULL{$excl}) OR club_id = ?) ORDER BY sort_order, id");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function getExcludedGlobal(int $clubId): array
    {
        $ids = (new ClubDictionaryExclusionModel())->getExcludedIds($clubId, self::DICTIONARY_KEY);
        if (empty($ids)) return [];
        $in  = implode(',', $ids);
        return $this->db->query(
            "SELECT * FROM medical_exam_types WHERE id IN ({$in}) AND club_id IS NULL ORDER BY sort_order, id"
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
        $this->db->prepare("UPDATE medical_exam_types SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }
}
