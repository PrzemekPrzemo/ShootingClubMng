<?php

namespace App\Models;

class PaymentModel extends BaseModel
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
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE period_year = ?");
        $stmt->execute([$year]);
        return (float)$stmt->fetchColumn();
    }

    public function getTotalByMonth(int $year, int $month): float
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE period_year = ? AND period_month = ?");
        $stmt->execute([$year, $month]);
        return (float)$stmt->fetchColumn();
    }

    public function getDebtors(int $year): array
    {
        // Members who haven't paid annual membership fee this year
        $stmt = $this->db->prepare("
            SELECT m.id, m.first_name, m.last_name, m.member_number, m.email, m.phone,
                   COALESCE(SUM(p.amount), 0) AS paid_total
            FROM members m
            LEFT JOIN payments p ON p.member_id = m.id AND p.period_year = ?
                AND p.payment_type_id IN (SELECT id FROM payment_types WHERE name LIKE '%składka roczna%')
            WHERE m.status = 'aktywny'
            GROUP BY m.id
            HAVING paid_total = 0
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }

    public function getSummaryByType(int $year): array
    {
        $stmt = $this->db->prepare("
            SELECT pt.name, SUM(p.amount) AS total, COUNT(*) AS count
            FROM payments p
            JOIN payment_types pt ON pt.id = p.payment_type_id
            WHERE p.period_year = ?
            GROUP BY pt.id, pt.name
            ORDER BY total DESC
        ");
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function updatePayment(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function getWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, m.first_name, m.last_name, m.member_number, pt.name AS type_name
            FROM payments p
            JOIN members m ON m.id = p.member_id
            JOIN payment_types pt ON pt.id = p.payment_type_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getPaymentTypes(): array
    {
        return $this->db->query("SELECT * FROM payment_types WHERE is_active = 1 ORDER BY name")->fetchAll();
    }
}
