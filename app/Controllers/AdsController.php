<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AdModel;
use App\Models\ClubModel;

/**
 * Ad management — super admin only.
 *
 * GET  /admin/ads             — list all ads
 * GET  /admin/ads/create      — new ad form
 * POST /admin/ads             — save new ad
 * GET  /admin/ads/:id/edit    — edit form
 * POST /admin/ads/:id         — save ad
 * POST /admin/ads/:id/toggle  — activate/deactivate
 * POST /admin/ads/:id/delete  — delete
 */
class AdsController extends BaseController
{
    private AdModel   $model;
    private ClubModel $clubModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
        $this->model     = new AdModel();
        $this->clubModel = new ClubModel();
    }

    public function index(): void
    {
        $this->render('admin/ads', [
            'title' => 'Zarządzanie reklamami',
            'ads'   => $this->model->getAll(),
        ]);
    }

    public function create(): void
    {
        $clubs = $this->clubModel->findAll('name');
        $this->render('admin/ads_form', [
            'title'  => 'Nowa reklama',
            'ad'     => null,
            'clubs'  => $clubs,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->collectData();
        $this->model->create($data);
        Session::flash('success', 'Reklama dodana.');
        $this->redirect('admin/ads');
    }

    public function edit(string $id): void
    {
        $ad = $this->model->findById((int)$id);
        if (!$ad) { $this->redirect('admin/ads'); }
        $clubs = $this->clubModel->findAll('name');
        $this->render('admin/ads_form', [
            'title' => 'Edytuj reklamę',
            'ad'    => $ad,
            'clubs' => $clubs,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->collectData();
        $this->model->update((int)$id, $data);
        Session::flash('success', 'Reklama zaktualizowana.');
        $this->redirect('admin/ads');
    }

    public function toggle(string $id): void
    {
        $this->model->toggle((int)$id);
        $this->redirect('admin/ads');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        $this->model->delete((int)$id);
        Session::flash('success', 'Reklama usunięta.');
        $this->redirect('admin/ads');
    }

    private function collectData(): array
    {
        $targets  = (array)($_POST['target'] ?? []);
        $planKeys = array_filter((array)($_POST['plan_keys'] ?? []));

        return [
            'title'      => trim($_POST['title'] ?? ''),
            'content'    => trim($_POST['content'] ?? ''),
            'image_path' => trim($_POST['image_path'] ?? '') ?: null,
            'link_url'   => trim($_POST['link_url'] ?? '') ?: null,
            'target'     => implode(',', array_filter($targets)),
            'club_id'    => !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null,
            'plan_keys'  => !empty($planKeys) ? implode(',', $planKeys) : null,
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'starts_at'  => $_POST['starts_at'] ?: null,
            'ends_at'    => $_POST['ends_at'] ?: null,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
    }
}
