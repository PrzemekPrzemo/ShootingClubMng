<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Feature;
use App\Helpers\Session;
use App\Models\AnnouncementModel;

class AnnouncementsController extends BaseController
{
    private AnnouncementModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad']);
        $this->model = new AnnouncementModel();
    }

    public function index(): void
    {
        if (!Feature::enabled('announcements')) {
            $this->redirect('dashboard');
        }

        $announcements = $this->model->getAll();

        $this->render('announcements/index', [
            'title'         => 'Ogłoszenia',
            'announcements' => $announcements,
        ]);
    }

    public function create(): void
    {
        if (!Feature::enabled('announcements')) {
            $this->redirect('dashboard');
        }

        $this->render('announcements/form', [
            'title'        => 'Nowe ogłoszenie',
            'announcement' => null,
            'mode'         => 'create',
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        if (!Feature::enabled('announcements')) {
            $this->redirect('dashboard');
        }

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('announcements/create');
        }

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $id = $this->model->create($data);
        if (!$id) {
            Session::flash('error', 'Błąd podczas tworzenia ogłoszenia. Sprawdź czy tabele zostały utworzone (migracja v18).');
            $this->redirect('announcements/create');
        }

        Session::flash('success', 'Ogłoszenie zostało utworzone.');
        $this->redirect('announcements');
    }

    public function edit(string $id): void
    {
        if (!Feature::enabled('announcements')) {
            $this->redirect('dashboard');
        }

        $announcement = $this->getAnnouncement((int)$id);

        $this->render('announcements/form', [
            'title'        => 'Edytuj ogłoszenie',
            'announcement' => $announcement,
            'mode'         => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        if (!Feature::enabled('announcements')) {
            $this->redirect('dashboard');
        }

        $this->getAnnouncement((int)$id);
        $data   = $this->collectData();
        $errors = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("announcements/{$id}/edit");
        }

        $this->model->updateAnnouncement((int)$id, $data);
        Session::flash('success', 'Ogłoszenie zostało zaktualizowane.');
        $this->redirect('announcements');
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->getAnnouncement((int)$id);
        $this->model->delete((int)$id);
        Session::flash('success', 'Ogłoszenie zostało usunięte.');
        $this->redirect('announcements');
    }

    public function togglePublish(string $id): void
    {
        Csrf::verify();
        $ann = $this->getAnnouncement((int)$id);
        $this->model->togglePublish((int)$id);
        $msg = $ann['is_published'] ? 'Ogłoszenie zostało ukryte.' : 'Ogłoszenie zostało opublikowane.';
        Session::flash('success', $msg);
        $this->redirect('announcements');
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function getAnnouncement(int $id): array
    {
        $a = $this->model->findById($id);
        if (!$a) {
            Session::flash('error', 'Ogłoszenie nie istnieje.');
            $this->redirect('announcements');
        }
        return $a;
    }

    private function collectData(): array
    {
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        return [
            'title'        => trim($_POST['title']    ?? ''),
            'body'         => trim($_POST['body']     ?? ''),
            'priority'     => in_array($_POST['priority'] ?? '', ['normal','wazne','pilne'])
                                ? $_POST['priority'] : 'normal',
            'is_published' => $isPublished,
            'published_at' => $isPublished ? (date('Y-m-d H:i:s')) : null,
            'expires_at'   => ($_POST['expires_at'] ?? '') ?: null,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['title'])) $errors[] = 'Tytuł ogłoszenia jest wymagany.';
        if (empty($data['body']))  $errors[] = 'Treść ogłoszenia jest wymagana.';
        return $errors;
    }
}
