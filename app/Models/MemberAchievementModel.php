<?php

namespace App\Models;

class MemberAchievementModel extends ClubScopedModel
{
    protected string $table = 'member_achievements';

    /**
     * Achievement type keys → display labels (ordered by prestige).
     */
    public const TYPES = [
        'igrzyska' => 'Igrzyska Olimpijskie / Kwalifikacja na IO',
        'ms_ind'   => 'MŚ / FPŚ / PŚ / IO/MŚ CISM — indywidualnie',
        'ms_dr'    => 'MŚ / FPŚ / PŚ / IO/MŚ CISM — drużynowo',
        'ie_ind'   => 'IE / ME / Uniwersjada / AMŚ — indywidualnie',
        'ie_dr'    => 'IE / ME / Uniwersjada / AMŚ — drużynowo',
        'mp_ind'   => 'MPKiM / MMP / MPJ / MPJMł / FOOM / MPM — indywidualnie',
        'mp_dr'    => 'MPKiM / MMP / MPJ / MPJMł / FOOM / MPM — drużynowo',
    ];

    /** Place labels (1–3). */
    public const PLACES = [
        1 => '1. miejsce',
        2 => '2. miejsce',
        3 => '3. miejsce',
    ];

    /** Medal CSS badge colours per place. */
    public const PLACE_CLASS = [
        1 => 'warning text-dark',   // gold
        2 => 'secondary',           // silver
        3 => 'warning-subtle text-dark', // bronze — fallback to warning
    ];

    public function getForMember(int $memberId): array
    {
        $sql = "SELECT a.*, u.full_name AS created_by_name
                FROM member_achievements a
                LEFT JOIN users u ON u.id = a.created_by
                WHERE a.member_id = ?{$this->clubWhereAliased('a')}
                ORDER BY a.year DESC, a.achievement_type ASC, a.place ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$memberId], $this->clubParams()));
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function getById(int $id): ?array
    {
        // Override parent: use club-scoped lookup (ClubScopedModel::findById already scopes).
        return $this->findById($id);
    }

    private function clubWhereAliased(string $alias): string
    {
        return $this->clubId() !== null ? " AND {$alias}.club_id = ?" : '';
    }
}
