<?php

namespace App\Models;

class MemberClassModel extends BaseModel
{
    protected string $table = 'member_classes';
    public const DICTIONARY_KEY = 'member_classes';

    public function getAll(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM member_classes ORDER BY sort_order, name")->fetchAll();
        }
        $excl = $this->buildExclNotIn($clubId);
        $stmt = $this->db->prepare("SELECT * FROM member_classes WHERE (club_id IS NULL{$excl}) OR club_id = ? ORDER BY sort_order, name");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function getActive(): array
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $this->db->query("SELECT * FROM member_classes WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll();
        }
        $excl = $this->buildExclNotIn($clubId);
        $stmt = $this->db->prepare("SELECT * FROM member_classes WHERE is_active = 1 AND ((club_id IS NULL{$excl}) OR club_id = ?) ORDER BY sort_order, name");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function getExcludedGlobal(int $clubId): array
    {
        $ids = (new ClubDictionaryExclusionModel())->getExcludedIds($clubId, self::DICTIONARY_KEY);
        if (empty($ids)) return [];
        $in  = implode(',', $ids);
        return $this->db->query(
            "SELECT * FROM member_classes WHERE id IN ({$in}) AND club_id IS NULL ORDER BY sort_order, name"
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
}
