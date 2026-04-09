<?php

namespace App\Models;

class SubscriptionModel extends BaseModel
{
    protected string $table = 'club_subscriptions';

    public static array $PLANS = [
        'trial'    => ['label' => 'Próbny (Trial)', 'max_members' => 50,  'price_pln' => 0],
        'basic'    => ['label' => 'Basic',           'max_members' => 50,  'price_pln' => 49],
        'standard' => ['label' => 'Standard',        'max_members' => 200, 'price_pln' => 99],
        'premium'  => ['label' => 'Premium',         'max_members' => null,'price_pln' => 199],
    ];

    public function getForClub(int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT cs.*, c.name AS club_name, c.trial_ends_at
             FROM club_subscriptions cs
             JOIN clubs c ON c.id = cs.club_id
             WHERE cs.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT cs.*, c.name AS club_name, c.email AS club_email,
                    (SELECT COUNT(*) FROM members m WHERE m.club_id = cs.club_id AND m.status = 'aktywny') AS active_members
             FROM club_subscriptions cs
             JOIN clubs c ON c.id = cs.club_id
             ORDER BY cs.status DESC, c.name"
        );
        return $stmt->fetchAll();
    }

    public function upsert(int $clubId, array $data): void
    {
        $existing = $this->getForClub($clubId);
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $data['club_id'] = $clubId;
            $this->insert($data);
        }
    }

    public function isExpired(int $clubId): bool
    {
        $sub = $this->getForClub($clubId);
        if (!$sub) return false;
        if ($sub['status'] !== 'active') return true;
        if ($sub['valid_until'] === null) return false;
        return strtotime($sub['valid_until']) < time();
    }

    public function isOverMemberLimit(int $clubId): bool
    {
        $sub = $this->getForClub($clubId);
        if (!$sub || $sub['max_members'] === null) return false;

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'"
        );
        $stmt->execute([$clubId]);
        return (int)$stmt->fetchColumn() >= (int)$sub['max_members'];
    }

    public function getDaysUntilExpiry(int $clubId): ?int
    {
        $sub = $this->getForClub($clubId);
        if (!$sub || !$sub['valid_until']) return null;
        return (int)ceil((strtotime($sub['valid_until']) - time()) / 86400);
    }
}
