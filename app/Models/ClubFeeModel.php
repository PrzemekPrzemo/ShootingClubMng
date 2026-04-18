<?php

namespace App\Models;

class ClubFeeModel extends ClubScopedModel
{
    protected string $table = 'club_fees';

    /** Stałe kwoty opłat PZSS/PomZSS */
    public const FEE_RATES = [
        'licencja_pzss'        => 1000.00,
        'czlonek_pzss'         => 500.00,
        'czlonek_pomzss'       => 350.00,
        'licencje_zawodnicze'  => 25.00,   // per zawodnik
        'licencje_sedziowskie' => 50.00,   // per sędzia
    ];

    public const FEE_LABELS = [
        'licencja_pzss'        => 'Licencja klubowa PZSS',
        'czlonek_pzss'         => 'Składka członkowska PZSS',
        'czlonek_pomzss'       => 'Składka PomZSS',
        'licencje_zawodnicze'  => 'Licencje zawodnicze (× 25 PLN/os.)',
        'licencje_sedziowskie' => 'Licencje sędziowskie (× 50 PLN/os.)',
    ];

    public function getByYear(int $year): array
    {
        $cid = $this->clubId();
        $sql = "SELECT cf.*, u.full_name AS created_by_name
                FROM club_fees cf
                LEFT JOIN users u ON u.id = cf.created_by
                WHERE cf.year = ?";
        $params = [$year];
        if ($cid !== null) { $sql .= " AND cf.club_id = ?"; $params[] = $cid; }
        $sql .= " ORDER BY FIELD(cf.fee_type,'licencja_pzss','czlonek_pzss','czlonek_pomzss','licencje_zawodnicze','licencje_sedziowskie')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Index by fee_type for easy lookup
        $result = [];
        foreach ($rows as $row) {
            $result[$row['fee_type']] = $row;
        }
        return $result;
    }

    public function upsert(array $data, int $createdBy): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO club_fees (year, fee_type, amount_due, due_date, created_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                amount_due = VALUES(amount_due),
                due_date   = VALUES(due_date)
        ");
        $stmt->execute([
            $data['year'],
            $data['fee_type'],
            $data['amount_due'],
            $data['due_date'],
            $createdBy,
        ]);
    }

    public function markPaid(int $id, string $paidDate, float $paidAmount, ?string $reference, ?string $notes): void
    {
        $this->db->prepare("
            UPDATE club_fees
            SET paid_date = ?, paid_amount = ?, reference = ?, notes = ?
            WHERE id = ?
        ")->execute([$paidDate, $paidAmount, $reference, $notes, $id]);
    }

    /**
     * Calculate amounts due for a given year based on active members/judges.
     * Returns array of fee_type => ['amount_due', 'count', 'due_date']
     */
    public function calculateDue(int $year): array
    {
        $dueDate = $year . '-03-31';
        $cid = $this->clubId();

        // Count active members with a license for the year
        $sql = "SELECT COUNT(*) FROM members WHERE status = 'aktywny'";
        $params = [];
        if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
        $stmtMembers = $this->db->prepare($sql);
        $stmtMembers->execute($params);
        $activeMembers = (int)$stmtMembers->fetchColumn();

        // Count active judge licenses valid in given year
        $sql = "SELECT COUNT(DISTINCT jl.member_id) FROM judge_licenses jl
                JOIN members m ON m.id = jl.member_id
                WHERE jl.valid_until >= ? AND jl.valid_until <= ?";
        $params = [$year . '-01-01', $year . '-12-31'];
        if ($cid !== null) { $sql .= " AND m.club_id = ?"; $params[] = $cid; }
        $stmtJudges = $this->db->prepare($sql);
        $stmtJudges->execute($params);
        $activeJudges = (int)$stmtJudges->fetchColumn();

        return [
            'licencja_pzss' => [
                'amount_due' => self::FEE_RATES['licencja_pzss'],
                'count'      => 1,
                'due_date'   => $dueDate,
            ],
            'czlonek_pzss' => [
                'amount_due' => self::FEE_RATES['czlonek_pzss'],
                'count'      => 1,
                'due_date'   => $dueDate,
            ],
            'czlonek_pomzss' => [
                'amount_due' => self::FEE_RATES['czlonek_pomzss'],
                'count'      => 1,
                'due_date'   => $dueDate,
            ],
            'licencje_zawodnicze' => [
                'amount_due' => $activeMembers * self::FEE_RATES['licencje_zawodnicze'],
                'count'      => $activeMembers,
                'due_date'   => $dueDate,
            ],
            'licencje_sedziowskie' => [
                'amount_due' => $activeJudges * self::FEE_RATES['licencje_sedziowskie'],
                'count'      => $activeJudges,
                'due_date'   => $year . '-06-30',
            ],
        ];
    }

    public function getTotalDue(int $year): float
    {
        $cid = $this->clubId();
        $sql = "SELECT COALESCE(SUM(amount_due),0) FROM club_fees WHERE year = ?";
        $params = [$year];
        if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function getTotalPaid(int $year): float
    {
        $cid = $this->clubId();
        $sql = "SELECT COALESCE(SUM(paid_amount),0) FROM club_fees WHERE year = ? AND paid_date IS NOT NULL";
        $params = [$year];
        if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function getYears(): array
    {
        $cid = $this->clubId();
        $sql = "SELECT DISTINCT year FROM club_fees";
        $params = [];
        if ($cid !== null) { $sql .= " WHERE club_id = ?"; $params[] = $cid; }
        $sql .= " ORDER BY year DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
