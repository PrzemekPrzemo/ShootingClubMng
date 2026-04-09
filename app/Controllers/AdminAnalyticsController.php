<?php

namespace App\Controllers;

use App\Helpers\Database;

/**
 * Super-admin analytics dashboard.
 *
 * GET /admin/analytics           — overview
 * GET /admin/analytics/revenue   — revenue trends
 * GET /admin/analytics/clubs     — per-club metrics
 * GET /admin/analytics/activity  — system activity log
 */
class AdminAnalyticsController extends BaseController
{
    private \PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
        $this->db = Database::getInstance();
    }

    // ── Overview ──────────────────────────────────────────────────────

    public function index(): void
    {
        $this->render('admin/analytics', [
            'title'    => 'Analityka systemu',
            'overview' => $this->getOverview(),
            'growth'   => $this->getMonthlyGrowth(),
            'plans'    => $this->getPlanDistribution(),
            'topClubs' => $this->getTopClubs(),
            'activity' => $this->getRecentActivity(),
        ]);
    }

    // ── Revenue ───────────────────────────────────────────────────────

    public function revenue(): void
    {
        $this->render('admin/analytics_revenue', [
            'title'     => 'Przychody',
            'monthly'   => $this->getMonthlyRevenue(12),
            'byPlan'    => $this->getRevenueByPlan(),
            'invoices'  => $this->getInvoiceStats(),
        ]);
    }

    // ── Per-club metrics ──────────────────────────────────────────────

    public function clubs(): void
    {
        $this->render('admin/analytics_clubs', [
            'title' => 'Metryki klubów',
            'clubs' => $this->getClubMetrics(),
        ]);
    }

    // ── Activity log ─────────────────────────────────────────────────

    public function activity(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $this->render('admin/analytics_activity', [
            'title'    => 'Log aktywności',
            'rows'     => $this->getActivityLog($page),
            'page'     => $page,
        ]);
    }

    // ── Data queries ──────────────────────────────────────────────────

    private function getOverview(): array
    {
        $r = [];
        try {
            $r['total_clubs']   = (int)$this->db->query("SELECT COUNT(*) FROM clubs WHERE is_active=1")->fetchColumn();
            $r['trial_clubs']   = (int)$this->db->query("SELECT COUNT(*) FROM club_subscriptions WHERE plan='trial'")->fetchColumn();
            $r['paid_clubs']    = (int)$this->db->query("SELECT COUNT(*) FROM club_subscriptions WHERE plan NOT IN ('trial') AND status='active'")->fetchColumn();
            $r['total_members'] = (int)$this->db->query("SELECT COUNT(*) FROM members WHERE status='aktywny'")->fetchColumn();
            $r['total_comps']   = (int)$this->db->query("SELECT COUNT(*) FROM competitions")->fetchColumn();
            $r['revenue_month'] = (float)$this->db->query("SELECT COALESCE(SUM(amount_pln),0) FROM billing_invoices WHERE status='paid' AND MONTH(paid_at)=MONTH(NOW()) AND YEAR(paid_at)=YEAR(NOW())")->fetchColumn();
            $r['revenue_year']  = (float)$this->db->query("SELECT COALESCE(SUM(amount_pln),0) FROM billing_invoices WHERE status='paid' AND YEAR(paid_at)=YEAR(NOW())")->fetchColumn();
            $r['churn_count']   = (int)$this->db->query("SELECT COUNT(*) FROM club_subscriptions WHERE status='cancelled'")->fetchColumn();
        } catch (\Throwable) {}
        return $r;
    }

    private function getMonthlyGrowth(int $months = 6): array
    {
        $result = [];
        try {
            $stmt = $this->db->prepare(
                "SELECT DATE_FORMAT(ts, '%Y-%m') AS month, COUNT(*) AS clubs
                 FROM clubs
                 WHERE ts >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                 GROUP BY month ORDER BY month ASC"
            );
            $stmt->execute([$months]);
            $result['clubs'] = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS members
                 FROM members
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                 GROUP BY month ORDER BY month ASC"
            );
            $stmt->execute([$months]);
            $result['members'] = $stmt->fetchAll();
        } catch (\Throwable) {}
        return $result;
    }

    private function getPlanDistribution(): array
    {
        try {
            return $this->db->query(
                "SELECT plan, COUNT(*) AS cnt FROM club_subscriptions WHERE status='active' GROUP BY plan ORDER BY cnt DESC"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getTopClubs(): array
    {
        try {
            return $this->db->query(
                "SELECT c.name, cs.plan,
                        (SELECT COUNT(*) FROM members m WHERE m.club_id=c.id AND m.status='aktywny') AS members,
                        (SELECT COUNT(*) FROM competitions co WHERE co.club_id=c.id) AS competitions
                 FROM clubs c
                 LEFT JOIN club_subscriptions cs ON cs.club_id=c.id
                 WHERE c.is_active=1
                 ORDER BY members DESC
                 LIMIT 10"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getRecentActivity(): array
    {
        try {
            return $this->db->query(
                "SELECT al.*, u.username, c.name AS club_name
                 FROM activity_log al
                 LEFT JOIN users u ON u.id=al.user_id
                 LEFT JOIN clubs c ON c.id=al.club_id
                 ORDER BY al.id DESC LIMIT 20"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getMonthlyRevenue(int $months): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT DATE_FORMAT(paid_at,'%Y-%m') AS month, SUM(amount_pln) AS total
                 FROM billing_invoices
                 WHERE status='paid' AND paid_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                 GROUP BY month ORDER BY month ASC"
            );
            $stmt->execute([$months]);
            return $stmt->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getRevenueByPlan(): array
    {
        try {
            return $this->db->query(
                "SELECT plan_key, COUNT(*) AS invoices, SUM(amount_pln) AS total
                 FROM billing_invoices WHERE status='paid'
                 GROUP BY plan_key ORDER BY total DESC"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getInvoiceStats(): array
    {
        try {
            return $this->db->query(
                "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(amount_pln),0) AS total
                 FROM billing_invoices GROUP BY status"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getClubMetrics(): array
    {
        try {
            return $this->db->query(
                "SELECT c.id, c.name, cs.plan, cs.status AS sub_status, cs.valid_until,
                        (SELECT COUNT(*) FROM members m WHERE m.club_id=c.id AND m.status='aktywny') AS active_members,
                        (SELECT COUNT(*) FROM competitions co WHERE co.club_id=c.id) AS competitions,
                        (SELECT COUNT(*) FROM trainings tr WHERE tr.club_id=c.id) AS trainings,
                        (SELECT MAX(al.created_at) FROM activity_log al WHERE al.club_id=c.id) AS last_activity
                 FROM clubs c
                 LEFT JOIN club_subscriptions cs ON cs.club_id=c.id
                 WHERE c.is_active=1
                 ORDER BY active_members DESC"
            )->fetchAll();
        } catch (\Throwable) { return []; }
    }

    private function getActivityLog(int $page): array
    {
        try {
            $offset = ($page - 1) * 50;
            $stmt = $this->db->prepare(
                "SELECT al.*, u.username, c.name AS club_name
                 FROM activity_log al
                 LEFT JOIN users u ON u.id=al.user_id
                 LEFT JOIN clubs c ON c.id=al.club_id
                 ORDER BY al.id DESC LIMIT 50 OFFSET ?"
            );
            $stmt->execute([$offset]);
            return $stmt->fetchAll();
        } catch (\Throwable) { return []; }
    }
}
