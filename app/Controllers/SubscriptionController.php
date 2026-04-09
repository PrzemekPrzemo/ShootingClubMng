<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\SubscriptionModel;

/**
 * Moduł subskrypcji/planów SaaS.
 *
 * GET  /admin/subscriptions               — lista wszystkich subskrypcji (super admin)
 * GET  /admin/subscriptions/:id/edit      — edytuj subskrypcję
 * POST /admin/subscriptions/:id           — zapisz subskrypcję
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

    // ── Widok dla zarządu klubu ───────────────────────────────────────

    public function clubView(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $this->requireClubContext();
        $clubId = ClubContext::current();
        $sub    = $this->model->getForClub($clubId);

        $this->render('subscriptions/club_view', [
            'title' => 'Plan subskrypcji',
            'sub'   => $sub,
            'plans' => SubscriptionModel::$PLANS,
        ]);
    }
}
