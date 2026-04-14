<?php

namespace App\Models;

use App\Helpers\Database;

class ClubFeeConfigModel extends BaseModel
{
    protected string $table = 'club_fee_config';

    // ── Getters ──────────────────────────────────────────────────────────────

    /**
     * Base fees per member type.
     * @return array<string, array{max_annual_fee: float, early_payment_fee: float}>
     */
    public function getFeeConfig(int $clubId, int $year): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT member_type, max_annual_fee, early_payment_fee
                 FROM club_fee_config WHERE club_id = ? AND year = ?"
            );
            $stmt->execute([$clubId, $year]);
            $out = [];
            foreach ($stmt->fetchAll() as $row) {
                $out[$row['member_type']] = [
                    'max_annual_fee'    => (float)$row['max_annual_fee'],
                    'early_payment_fee' => (float)$row['early_payment_fee'],
                ];
            }
            return $out;
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Class-based discounts keyed by discipline_class_id.
     * @return array<int, float>
     */
    public function getClassDiscounts(int $clubId, int $year): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT discipline_class_id, discount_amount
                 FROM club_fee_discount_class WHERE club_id = ? AND year = ?"
            );
            $stmt->execute([$clubId, $year]);
            $out = [];
            foreach ($stmt->fetchAll() as $row) {
                $out[(int)$row['discipline_class_id']] = (float)$row['discount_amount'];
            }
            return $out;
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Achievement-based discounts.
     * @return array<string, float>  keyed by achievement_type
     */
    public function getAchieveDiscounts(int $clubId, int $year): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT achievement_type, discount_amount
                 FROM club_fee_discount_achieve WHERE club_id = ? AND year = ?"
            );
            $stmt->execute([$clubId, $year]);
            $out = [];
            foreach ($stmt->fetchAll() as $row) {
                $out[$row['achievement_type']] = (float)$row['discount_amount'];
            }
            return $out;
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Last recalculation stats for a club+year.
     */
    public function getRecalcStats(int $clubId, int $year): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS cnt, SUM(final_annual_fee) AS total_annual,
                        MAX(calculated_at) AS last_at
                 FROM member_fee_assignments WHERE club_id = ? AND year = ?"
            );
            $stmt->execute([$clubId, $year]);
            return $stmt->fetch() ?: [];
        } catch (\PDOException) {
            return [];
        }
    }

    /**
     * Calculated fee assignment for one member.
     */
    public function getAssignment(int $memberId, int $year): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM member_fee_assignments WHERE member_id = ? AND year = ?"
            );
            $stmt->execute([$memberId, $year]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException) {
            return null;
        }
    }

    // ── Savers (upsert) ──────────────────────────────────────────────────────

    public function saveFeeConfig(int $clubId, int $year, array $rows): void
    {
        $sql = "INSERT INTO club_fee_config (club_id, year, member_type, max_annual_fee, early_payment_fee)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE max_annual_fee = VALUES(max_annual_fee),
                                        early_payment_fee = VALUES(early_payment_fee)";
        $stmt = $this->db->prepare($sql);
        foreach ($rows as $memberType => $amounts) {
            $stmt->execute([
                $clubId,
                $year,
                (string)$memberType,
                max(0, (float)($amounts['max_annual'] ?? 0)),
                max(0, (float)($amounts['early_payment'] ?? 0)),
            ]);
        }
    }

    public function saveClassDiscounts(int $clubId, int $year, array $rows): void
    {
        $sql = "INSERT INTO club_fee_discount_class (club_id, year, discipline_class_id, discount_amount)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE discount_amount = VALUES(discount_amount)";
        $stmt = $this->db->prepare($sql);
        foreach ($rows as $classId => $amount) {
            $stmt->execute([$clubId, $year, (int)$classId, max(0, (float)$amount)]);
        }
    }

    public function saveAchieveDiscounts(int $clubId, int $year, array $rows): void
    {
        $sql = "INSERT INTO club_fee_discount_achieve (club_id, year, achievement_type, discount_amount)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE discount_amount = VALUES(discount_amount)";
        $stmt = $this->db->prepare($sql);
        foreach ($rows as $achType => $amount) {
            $stmt->execute([$clubId, $year, (string)$achType, max(0, (float)$amount)]);
        }
    }

    // ── Calculator ───────────────────────────────────────────────────────────

    /**
     * Calculate fee breakdown for one member.
     *
     * @param array $member  Row from members table (needs: member_type, id)
     * @return array{base: float, discount_class: float, discount_achieve: float,
     *               final_annual: float, monthly: float, early_payment_final: float}
     */
    public function calculateMemberFee(array $member, int $clubId, int $year): array
    {
        $feeConfig      = $this->getFeeConfig($clubId, $year);
        $classDiscounts = $this->getClassDiscounts($clubId, $year);
        $achDiscounts   = $this->getAchieveDiscounts($clubId, $year);

        $memberType = $member['member_type'] ?? '';

        $base  = $feeConfig[$memberType]['max_annual_fee']    ?? 0.0;
        $early = $feeConfig[$memberType]['early_payment_fee'] ?? 0.0;

        // Sum discount across all disciplines the member has a class in.
        // Each discipline with a matching class contributes its discount.
        // Classes may be club-specific (club_id = X) or global (club_id IS NULL).
        $discountClass = 0.0;
        if (!empty($classDiscounts)) {
            try {
                $stmt = $this->db->prepare(
                    "SELECT dc.id, md.discipline_id
                     FROM member_disciplines md
                     JOIN discipline_classes dc ON dc.name = md.class
                        AND (dc.club_id = ? OR dc.club_id IS NULL)
                     WHERE md.member_id = ? AND md.class IS NOT NULL AND md.class <> ''"
                );
                $stmt->execute([$clubId, (int)$member['id']]);
                // Deduplicate by discipline (one class per discipline counts once).
                // If member has class in multiple matching discipline_classes rows
                // (e.g., club-specific + global with same name), pick the highest rank.
                $perDiscipline = [];
                foreach ($stmt->fetchAll() as $row) {
                    $did = (int)$row['discipline_id'];
                    $dcId = (int)$row['id'];
                    if (isset($classDiscounts[$dcId])) {
                        $disc = $classDiscounts[$dcId];
                        if (!isset($perDiscipline[$did]) || $disc > $perDiscipline[$did]) {
                            $perDiscipline[$did] = $disc;
                        }
                    }
                }
                $discountClass = array_sum($perDiscipline);
            } catch (\PDOException) {
                // table not yet created — skip
            }
        }

        // Sum discounts for each achievement the member holds.
        // Each achievement row counts — multiple medals of same type stack.
        $discountAchieve = 0.0;
        if (!empty($achDiscounts)) {
            try {
                $stmt = $this->db->prepare(
                    "SELECT achievement_type FROM member_achievements WHERE member_id = ?"
                );
                $stmt->execute([(int)$member['id']]);
                foreach ($stmt->fetchAll() as $row) {
                    $type = $row['achievement_type'];
                    if (isset($achDiscounts[$type])) {
                        $discountAchieve += $achDiscounts[$type];
                    }
                }
            } catch (\PDOException) {
                // table not yet created — skip
            }
        }

        $totalDiscount     = $discountClass + $discountAchieve;
        $finalAnnual       = max(0.0, $base  - $totalDiscount);
        $monthly           = round($finalAnnual / 12, 2);
        $earlyPaymentFinal = max(0.0, $early - $totalDiscount);

        return [
            'base'               => $base,
            'discount_class'     => $discountClass,
            'discount_achieve'   => $discountAchieve,
            'final_annual'       => $finalAnnual,
            'monthly'            => $monthly,
            'early_payment_final'=> $earlyPaymentFinal,
        ];
    }

    /**
     * Recalculate fees for all active members in a club.
     * Returns stats array.
     */
    public function recalculateAll(int $clubId, int $year): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, member_type FROM members
             WHERE club_id = ? AND status = 'aktywny'"
        );
        $stmt->execute([$clubId]);
        $members = $stmt->fetchAll();

        $upsert = $this->db->prepare(
            "INSERT INTO member_fee_assignments
               (member_id, club_id, year, base_annual_fee, discount_class, discount_achieve,
                final_annual_fee, monthly_fee, early_payment_fee)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               base_annual_fee  = VALUES(base_annual_fee),
               discount_class   = VALUES(discount_class),
               discount_achieve = VALUES(discount_achieve),
               final_annual_fee = VALUES(final_annual_fee),
               monthly_fee      = VALUES(monthly_fee),
               early_payment_fee= VALUES(early_payment_fee),
               calculated_at    = CURRENT_TIMESTAMP"
        );

        $processed = 0;
        foreach ($members as $member) {
            $calc = $this->calculateMemberFee($member, $clubId, $year);
            $upsert->execute([
                (int)$member['id'],
                $clubId,
                $year,
                $calc['base'],
                $calc['discount_class'],
                $calc['discount_achieve'],
                $calc['final_annual'],
                $calc['monthly'],
                $calc['early_payment_final'],
            ]);
            $processed++;
        }

        $statsStmt = $this->db->prepare(
            "SELECT SUM(final_annual_fee) AS total FROM member_fee_assignments
             WHERE club_id = ? AND year = ?"
        );
        $statsStmt->execute([$clubId, $year]);
        $total = (float)($statsStmt->fetchColumn() ?? 0);

        return ['processed' => $processed, 'total_annual' => $total];
    }
}
