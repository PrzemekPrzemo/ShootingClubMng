<?php

namespace App\Models;

class PaymentModel extends ClubScopedModel
{
    protected string $table = 'payments';

    public function search(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[]  = "(m.last_name LIKE ? OR m.first_name LIKE ? OR p.reference LIKE ?)";
            array_push($params, $q, $q, $q);
        }
        if (!empty($filters['member_id'])) {
            $where[]  = "p.member_id = ?";
            $params[] = $filters['member_id'];
        }
        if (!empty($filters['year'])) {
            $where[]  = "p.period_year = ?";
            $params[] = $filters['year'];
        }
        if (!empty($filters['payment_type_id'])) {
            $where[]  = "p.payment_type_id = ?";
            $params[] = $filters['payment_type_id'];
        }

        $cid = $this->clubId();
        if ($cid !== null) {
            $where[]  = "p.club_id = ?";
            $params[] = $cid;
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT p.*, m.first_name, m.last_name, m.member_number, pt.name AS type_name
                FROM payments p
                JOIN members m ON m.id = p.member_id
                JOIN payment_types pt ON pt.id = p.payment_type_id
                WHERE {$whereClause}
                ORDER BY p.payment_date DESC, p.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function getTotalByYear(int $year): float
    {
        $cid = $this->clubId();
        $sql = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE period_year = ?";
        $params = [$year];
        if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function getTotalByMonth(int $year, int $month): float
    {
        $cid = $this->clubId();
        $sql = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE period_year = ? AND period_month = ?";
        $params = [$year, $month];
        if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function getDebtors(int $year): array
    {
        // Members and how much they owe for annual membership fee this year.
        // First month free rule: if member joined in month M of $year, they pay
        // for months (M+1)..12 = (12 - M) months of the annual fee.
        // If joined in previous year(s) → full annual fee.
        $cid = $this->clubId();
        $memberWhere = '';
        $ptWhere     = '';
        $ptDefaultWhere = '';
        $extraParams = [];
        if ($cid !== null) {
            $memberWhere = " AND m.club_id = ?";
            $ptWhere     = " AND pt.club_id = ?";
            $ptDefaultWhere = " AND club_id = ?";
            $extraParams = [$cid, $cid, $cid];
        }
        $stmt = $this->db->prepare("
            SELECT m.id, m.first_name, m.last_name, m.member_number, m.email, m.phone,
                   m.join_date, m.member_type, m.member_class_id,
                   COALESCE(SUM(p.amount), 0) AS paid_total,
                   pt_default.amount AS default_amount, pt_default.id AS default_type_id
            FROM members m
            LEFT JOIN payments p ON p.member_id = m.id AND p.period_year = ?
                AND p.payment_type_id IN (SELECT id FROM payment_types pt WHERE pt.name LIKE '%składka roczna%' {$ptWhere})
            LEFT JOIN payment_types pt_default ON pt_default.id = (
                SELECT id FROM payment_types WHERE name LIKE '%składka roczna%' AND is_active = 1 {$ptDefaultWhere} ORDER BY id LIMIT 1
            )
            WHERE m.status = 'aktywny'
              AND (YEAR(m.join_date) < ? OR (YEAR(m.join_date) = ? AND MONTH(m.join_date) < 12))
              {$memberWhere}
            GROUP BY m.id
            ORDER BY m.last_name, m.first_name
        ");
        $params = [$year];
        if ($cid !== null) { $params[] = $cid; $params[] = $cid; } // pt subqueries
        $params[] = $year;
        $params[] = $year;
        if ($cid !== null) { $params[] = $cid; } // member WHERE
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $r) {
            $joinYear  = (int)substr((string)$r['join_date'], 0, 4);
            $joinMonth = (int)substr((string)$r['join_date'], 5, 2);

            // Determine expected annual fee via fee_rates matrix (fallback to payment_types.amount)
            $annualFee = $this->resolveAnnualFee(
                (int)($r['default_type_id'] ?? 0),
                $r['member_class_id'] !== null ? (int)$r['member_class_id'] : null,
                $year,
                (float)($r['default_amount'] ?? 0)
            );

            $monthsDue = 12;
            if ($joinYear === $year) {
                // First month free → pay months after join month
                $monthsDue = max(0, 12 - $joinMonth);
            } elseif ($joinYear > $year) {
                $monthsDue = 0;
            }

            $expected = round(($annualFee / 12) * $monthsDue, 2);
            $paid     = (float)$r['paid_total'];
            $outstanding = max(0, round($expected - $paid, 2));

            if ($outstanding > 0.005) {
                $r['expected'] = $expected;
                $r['paid_total'] = $paid;
                $r['outstanding'] = $outstanding;
                $r['months_due'] = $monthsDue;
                $result[] = $r;
            }
        }
        return $result;
    }

    /**
     * Resolve annual fee for a member using fee_rates matrix with fallbacks:
     * 1. Class-specific rate for the year
     * 2. Default rate (NULL class_id) for the year
     * 3. payment_types.amount (given as fallback parameter)
     */
    private function resolveAnnualFee(int $typeId, ?int $classId, int $year, float $fallback): float
    {
        if ($typeId <= 0) return $fallback;
        try {
            // Try class-specific first
            if ($classId !== null) {
                $stmt = $this->db->prepare(
                    "SELECT amount FROM fee_rates WHERE payment_type_id = ? AND member_class_id = ? AND year = ? LIMIT 1"
                );
                $stmt->execute([$typeId, $classId, $year]);
                $row = $stmt->fetch();
                if ($row) return (float)$row['amount'];
            }
            // Fall back to default (NULL class)
            $stmt = $this->db->prepare(
                "SELECT amount FROM fee_rates WHERE payment_type_id = ? AND member_class_id IS NULL AND year = ? LIMIT 1"
            );
            $stmt->execute([$typeId, $year]);
            $row = $stmt->fetch();
            if ($row) return (float)$row['amount'];
        } catch (\PDOException) {
            // fee_rates table may not exist on some deployments
        }
        return $fallback;
    }

    public function getSummaryByType(int $year): array
    {
        $cid = $this->clubId();
        $sql = "SELECT pt.name, SUM(p.amount) AS total, COUNT(*) AS count
                FROM payments p
                JOIN payment_types pt ON pt.id = p.payment_type_id
                WHERE p.period_year = ?";
        $params = [$year];
        if ($cid !== null) { $sql .= " AND p.club_id = ?"; $params[] = $cid; }
        $sql .= " GROUP BY pt.id, pt.name ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        if (!isset($data['club_id']) && $this->clubId() !== null) {
            $data['club_id'] = $this->clubId();
        }
        return $this->insert($data);
    }

    public function updatePayment(int $id, array $data): bool
    {
        unset($data['club_id']);
        return $this->update($id, $data);
    }

    public function getWithDetails(int $id): ?array
    {
        $cid = $this->clubId();
        $sql = "SELECT p.*, m.first_name, m.last_name, m.member_number, pt.name AS type_name
                FROM payments p
                JOIN members m ON m.id = p.member_id
                JOIN payment_types pt ON pt.id = p.payment_type_id
                WHERE p.id = ?";
        $params = [$id];
        if ($cid !== null) { $sql .= " AND p.club_id = ?"; $params[] = $cid; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getPaymentTypes(): array
    {
        $cid = $this->clubId();
        $sql = "SELECT * FROM payment_types WHERE is_active = 1";
        $params = [];
        if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
        $sql .= " ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
