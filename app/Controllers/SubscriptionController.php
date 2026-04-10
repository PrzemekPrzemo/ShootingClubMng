<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\SubscriptionModel;

/**
 * Moduł subskrypcji/planów SaaS.
 *
 * GET  /admin/subscriptions               — lista wszystkich subskrypcji (super admin)
 * GET  /admin/subscriptions/:id/edit      — edytuj subskrypcję
 * POST /admin/subscriptions/:id           — zapisz subskrypcję
 * GET  /admin/subscriptions/plans         — zarządzanie planami i cenami
 * POST /admin/subscriptions/plans         — zapisz plany
 * GET  /admin/subscriptions/invoices      — faktury/rozliczenia
 * POST /admin/subscriptions/invoices      — wystaw fakturę
 * GET  /subscription                      — widok planu aktualnego klubu
 */
class SubscriptionController extends BaseController
{
    private SubscriptionModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->model = new SubscriptionModel();
    }

    // ── Super admin — zarządzanie wszystkimi subskrypcjami ────────────

    public function adminIndex(): void
    {
        $this->requireSuperAdmin();
        $this->render('subscriptions/admin_index', [
            'title'         => 'Subskrypcje klubów',
            'subscriptions' => $this->model->getAll(),
            'plans'         => SubscriptionModel::$PLANS,
        ]);
    }

    public function adminEdit(string $id): void
    {
        $this->requireSuperAdmin();
        $sub = $this->model->getForClub((int)$id) ?? ['club_id' => (int)$id, 'plan' => 'trial', 'status' => 'active', 'valid_until' => null, 'max_members' => null, 'notes' => ''];

        $this->render('subscriptions/admin_edit', [
            'title'  => 'Edytuj subskrypcję',
            'sub'    => $sub,
            'plans'  => SubscriptionModel::$PLANS,
            'clubId' => (int)$id,
        ]);
    }

    public function adminSave(string $id): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $plan        = $_POST['plan'] ?? 'trial';
        $validUntil  = $_POST['valid_until'] ?: null;
        $status      = $_POST['status'] ?? 'active';
        $maxMembers  = $_POST['max_members'] !== '' ? (int)$_POST['max_members'] : null;
        $notes       = trim($_POST['notes'] ?? '');

        if (!array_key_exists($plan, SubscriptionModel::$PLANS)) {
            Session::flash('error', 'Nieprawidłowy plan.');
            $this->redirect('admin/subscriptions/' . (int)$id . '/edit');
        }

        $this->model->upsert((int)$id, [
            'plan'        => $plan,
            'valid_until' => $validUntil,
            'status'      => $status,
            'max_members' => $maxMembers,
            'notes'       => $notes ?: null,
        ]);

        Session::flash('success', 'Subskrypcja zaktualizowana.');
        $this->redirect('admin/subscriptions');
    }

    // ── Plans editor (pricing management) ───────────────────────────

    public function adminPlans(): void
    {
        $this->requireSuperAdmin();
        $plans = $this->getDbPlans();

        $this->render('subscriptions/admin_plans', [
            'title' => 'Zarządzanie planami subskrypcji',
            'plans' => $plans,
        ]);
    }

    public function adminSavePlans(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $db     = Database::getInstance();
        $keys   = $_POST['key']   ?? [];
        $labels = $_POST['label'] ?? [];
        $prices = $_POST['price_pln'] ?? [];
        $annual = $_POST['price_annual'] ?? [];
        $maxmem = $_POST['max_members'] ?? [];
        $descs  = $_POST['description'] ?? [];
        $active = $_POST['is_active'] ?? [];

        foreach ($keys as $i => $key) {
            $key = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($key)));
            if ($key === '') continue;

            $db->prepare(
                "INSERT INTO subscription_plans (`key`,label,max_members,price_pln,price_annual,description,is_active,sort_order)
                 VALUES (?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                   label=VALUES(label), max_members=VALUES(max_members),
                   price_pln=VALUES(price_pln), price_annual=VALUES(price_annual),
                   description=VALUES(description), is_active=VALUES(is_active),
                   sort_order=VALUES(sort_order)"
            )->execute([
                $key,
                trim($labels[$i] ?? $key),
                !empty($maxmem[$i]) ? (int)$maxmem[$i] : null,
                (float)($prices[$i] ?? 0),
                (float)($annual[$i] ?? 0),
                trim($descs[$i] ?? '') ?: null,
                isset($active[$i]) ? 1 : 0,
                $i,
            ]);
        }

        Session::flash('success', 'Plany subskrypcji zaktualizowane.');
        $this->redirect('admin/subscriptions/plans');
    }

    // ── Billing invoices ─────────────────────────────────────────────

    public function adminInvoices(): void
    {
        $this->requireSuperAdmin();
        $db       = Database::getInstance();
        $invoices = $db->query(
            "SELECT bi.*, c.name AS club_name
             FROM billing_invoices bi
             JOIN clubs c ON c.id = bi.club_id
             ORDER BY bi.created_at DESC
             LIMIT 200"
        )->fetchAll();

        $clubs = $db->query("SELECT id, name FROM clubs WHERE is_active=1 ORDER BY name")->fetchAll();
        $plans = $this->getDbPlans();

        $this->render('subscriptions/admin_invoices', [
            'title'    => 'Faktury i rozliczenia',
            'invoices' => $invoices,
            'clubs'    => $clubs,
            'plans'    => $plans,
        ]);
    }

    public function adminIssueInvoice(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $clubId     = (int)($_POST['club_id'] ?? 0);
        $planKey    = trim($_POST['plan_key'] ?? '');
        $amount     = (float)($_POST['amount_pln'] ?? 0);
        $periodFrom = $_POST['period_from'] ?? '';
        $periodTo   = $_POST['period_to'] ?? '';

        if (!$clubId || !$planKey || !$amount || !$periodFrom || !$periodTo) {
            Session::flash('error', 'Uzupełnij wszystkie wymagane pola.');
            $this->redirect('admin/subscriptions/invoices');
        }

        $db  = Database::getInstance();
        $num = 'FV/' . date('Y/m/') . str_pad((int)$db->query("SELECT COUNT(*)+1 FROM billing_invoices WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())")->fetchColumn(), 4, '0', STR_PAD_LEFT);

        $db->prepare(
            "INSERT INTO billing_invoices (club_id, plan_key, amount_pln, period_from, period_to, status, invoice_number, notes, issued_at)
             VALUES (?, ?, ?, ?, ?, 'issued', ?, ?, NOW())"
        )->execute([$clubId, $planKey, $amount, $periodFrom, $periodTo, $num, trim($_POST['notes'] ?? '') ?: null]);

        Session::flash('success', "Wystawiono fakturę {$num}.");
        $this->redirect('admin/subscriptions/invoices');
    }

    public function adminMarkInvoicePaid(string $id): void
    {
        Csrf::verify();
        $this->requireSuperAdmin();
        Database::getInstance()->prepare(
            "UPDATE billing_invoices SET status='paid', paid_at=NOW() WHERE id=?"
        )->execute([(int)$id]);
        Session::flash('success', 'Faktura oznaczona jako opłacona.');
        $this->redirect('admin/subscriptions/invoices');
    }

    // ── Widok dla zarządu klubu ───────────────────────────────────────

    public function clubView(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $this->requireClubContext();
        $clubId   = ClubContext::current();
        $sub      = $this->model->getForClub($clubId);
        $invoices = [];
        try {
            $invoices = Database::getInstance()->prepare(
                "SELECT * FROM billing_invoices WHERE club_id=? ORDER BY created_at DESC LIMIT 12"
            )->execute([$clubId]) ? Database::getInstance()->prepare(
                "SELECT * FROM billing_invoices WHERE club_id=? ORDER BY created_at DESC LIMIT 12"
            ) : [];
            $stmt = Database::getInstance()->prepare(
                "SELECT * FROM billing_invoices WHERE club_id=? ORDER BY created_at DESC LIMIT 12"
            );
            $stmt->execute([$clubId]);
            $invoices = $stmt->fetchAll();
        } catch (\Throwable) {}

        $this->render('subscriptions/club_view', [
            'title'    => 'Plan subskrypcji',
            'sub'      => $sub,
            'plans'    => $this->getDbPlans(),
            'invoices' => $invoices,
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────

    private function getDbPlans(): array
    {
        try {
            $rows = Database::getInstance()->query(
                "SELECT * FROM subscription_plans ORDER BY sort_order ASC"
            )->fetchAll();
            if ($rows) {
                $plans = [];
                foreach ($rows as $r) {
                    $plans[$r['key']] = [
                        'label'        => $r['label'],
                        'max_members'  => $r['max_members'],
                        'price_pln'    => (float)$r['price_pln'],
                        'price_annual' => (float)$r['price_annual'],
                        'description'  => $r['description'],
                        'is_active'    => (bool)$r['is_active'],
                    ];
                }
                return $plans;
            }
        } catch (\Throwable) {}
        return SubscriptionModel::$PLANS;
    }
}
