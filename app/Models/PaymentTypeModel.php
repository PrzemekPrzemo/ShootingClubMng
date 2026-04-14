<?php

namespace App\Models;

class PaymentTypeModel extends ClubScopedModel
{
    protected string $table = 'payment_types';

    public const CATEGORIES = [
        'skladka' => 'Składka członkowska',
        'pzss'    => 'Opłata PZSS',
        'pomzss'  => 'Opłata PomZSS',
        'inne'    => 'Inne',
    ];

    /** Default values for columns added in migration_v4 */
    private static array $defaults = [
        'category'    => 'inne',
        'description' => null,
        'is_per_class'=> 0,
        'sort_order'  => 0,
    ];

    private function normalize(array $rows): array
    {
        return array_map(fn($r) => self::$defaults + $r, $rows);
    }

    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM payment_types
                ORDER BY sort_order, category, name
            ");
        } catch (\PDOException) {
            // migration_v4 not yet run — fallback to name only
            $stmt = $this->db->query("SELECT * FROM payment_types ORDER BY name");
        }
        return $this->normalize($stmt->fetchAll());
    }

    public function getActive(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM payment_types WHERE is_active = 1
                ORDER BY sort_order, category, name
            ");
        } catch (\PDOException) {
            $stmt = $this->db->query("SELECT * FROM payment_types WHERE is_active = 1 ORDER BY name");
        }
        return $this->normalize($stmt->fetchAll());
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
        $this->db->prepare("UPDATE payment_types SET is_active = 1 - is_active WHERE id = ?")
            ->execute([$id]);
    }

    public function isUsed(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM payments WHERE payment_type_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ── Fee Rates ────────────────────────────────────────────────────

    /**
     * Returns rate matrix for a given year.
     * Structure: [payment_type_id][class_key] => amount  (class_key=0 means default)
     */
    public function getRateMatrix(int $year): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT payment_type_id,
                       COALESCE(member_class_id, 0) AS class_key,
                       amount
                FROM fee_rates
                WHERE year = ?
            ");
            $stmt->execute([$year]);
        } catch (\PDOException) {
            // fee_rates table missing (migration_v4 not yet run)
            return [];
        }

        $matrix = [];
        foreach ($stmt->fetchAll() as $row) {
            $matrix[(int)$row['payment_type_id']][(int)$row['class_key']] = (float)$row['amount'];
        }
        return $matrix;
    }

    /**
     * Get effective rate for a payment type + member class + year.
     * Falls back to: class-specific → default rate → payment_types.amount
     */
    public function getEffectiveRate(int $paymentTypeId, ?int $memberClassId, int $year): float
    {
        try {
            // Try class-specific rate for this year
            if ($memberClassId) {
                $stmt = $this->db->prepare(
                    "SELECT amount FROM fee_rates WHERE payment_type_id = ? AND member_class_id = ? AND year = ?"
                );
                $stmt->execute([$paymentTypeId, $memberClassId, $year]);
                $row = $stmt->fetch();
                if ($row) return (float)$row['amount'];
            }

            // Try default rate for this year (member_class_id IS NULL)
            $stmt = $this->db->prepare(
                "SELECT amount FROM fee_rates WHERE payment_type_id = ? AND member_class_id IS NULL AND year = ?"
            );
            $stmt->execute([$paymentTypeId, $year]);
            $row = $stmt->fetch();
            if ($row) return (float)$row['amount'];
        } catch (\PDOException) {
            // fee_rates table missing — fall through to payment_types.amount
        }

        // Fall back to payment_types.amount
        $stmt = $this->db->prepare("SELECT amount FROM payment_types WHERE id = ?");
        $stmt->execute([$paymentTypeId]);
        $row = $stmt->fetch();
        return $row ? (float)$row['amount'] : 0.0;
    }

    /**
     * Upsert a single fee rate.
     */
    public function upsertRate(int $paymentTypeId, ?int $memberClassId, int $year, float $amount, int $updatedBy): void
    {
        try {
            $this->db->prepare("
                INSERT INTO fee_rates (payment_type_id, member_class_id, year, amount, updated_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), updated_by = VALUES(updated_by)
            ")->execute([$paymentTypeId, $memberClassId, $year, $amount, $updatedBy]);
        } catch (\PDOException $e) {
            throw new \PDOException(
                $e->getMessage() . ' | Table: fee_rates | Values: payment_type_id=' . $paymentTypeId
                . ', member_class_id=' . ($memberClassId ?? 'NULL')
                . ', year=' . $year . ', amount=' . $amount
                . ', updated_by=' . $updatedBy,
                0, $e
            );
        }
    }

    /**
     * Save full rate matrix for a year from POST data.
     * $rates = [ payment_type_id => [ class_key => amount, ... ], ... ]
     * class_key = 0 means default (NULL in DB)
     */
    public function saveRateMatrix(array $rates, int $year, int $updatedBy): void
    {
        foreach ($rates as $typeId => $clasRates) {
            foreach ($clasRates as $classKey => $amount) {
                $classId = (int)$classKey > 0 ? (int)$classKey : null;
                $this->upsertRate((int)$typeId, $classId, $year, (float)str_replace(',', '.', $amount), $updatedBy);
            }
        }
    }

    /**
     * Returns all effective rates for all active payment types and a member's class for the given year.
     * Used by finances form to suggest amounts.
     */
    public function getRatesForMember(?int $memberClassId, int $year): array
    {
        $types = $this->getActive();
        $result = [];
        foreach ($types as $t) {
            $result[$t['id']] = [
                'name'   => $t['name'],
                'amount' => $this->getEffectiveRate((int)$t['id'], $memberClassId, $year),
            ];
        }
        return $result;
    }
}
