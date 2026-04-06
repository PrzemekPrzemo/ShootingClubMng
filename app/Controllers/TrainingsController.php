<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Feature;
use App\Helpers\Session;
use App\Models\TrainingsModel;
use App\Models\MemberModel;
use App\Models\UserModel;

class TrainingsController extends BaseController
{
    private TrainingsModel $model;
    private MemberModel    $memberModel;
    private UserModel      $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->model       = new TrainingsModel();
        $this->memberModel = new MemberModel();
        $this->userModel   = new UserModel();
    }

    public function index(): void
    {
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $filters = [
            'month'  => $_GET['month']  ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $trainings = $this->model->getAll($filters);

        $this->render('trainings/index', [
            'title'     => 'Treningi',
            'trainings' => $trainings,
            'filters'   => $filters,
        ]);
    }

    public function create(): void
    {
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $this->render('trainings/form', [
            'title'    => 'Utwórz trening',
            'training' => null,
            'mode'     => 'create',
            'users'    => $this->userModel->getAllUsers(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('trainings/create');
        }

        $data['created_by'] = Auth::id();
        $id = $this->model->create($data);

        if (!$id) {
            Session::flash('error', 'Błąd podczas tworzenia treningu. Sprawdź czy tabele zostały utworzone (migracja v18).');
            $this->redirect('trainings/create');
        }

        Session::flash('success', 'Trening został utworzony.');
        $this->redirect("trainings/{$id}");
    }

    public function show(string $id): void
    {
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $training = $this->getTraining((int)$id);
        $attendees = $this->model->getAttendees((int)$id);

        $this->render('trainings/show', [
            'title'     => $training['title'],
            'training'  => $training,
            'attendees' => $attendees,
        ]);
    }

    public function edit(string $id): void
    {
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $training = $this->getTraining((int)$id);

        $this->render('trainings/form', [
            'title'    => 'Edytuj trening',
            'training' => $training,
            'mode'     => 'edit',
            'users'    => $this->userModel->getAllUsers(),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $training = $this->getTraining((int)$id);
        $data     = $this->collectData();
        $errors   = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("trainings/{$id}/edit");
        }

        $this->model->updateTraining((int)$id, $data);
        Session::flash('success', 'Trening został zaktualizowany.');
        $this->redirect("trainings/{$id}");
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->getTraining((int)$id);
        $this->model->delete((int)$id);
        Session::flash('success', 'Trening został usunięty.');
        $this->redirect('trainings');
    }

    public function attendance(string $id): void
    {
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $training  = $this->getTraining((int)$id);
        $attendees = $this->model->getAttendees((int)$id);
        $members   = $this->memberModel->getAllActive();

        // Build map of currently enrolled members
        $enrolledIds  = array_column($attendees, 'member_id');
        $attendedIds  = array_column(
            array_filter($attendees, fn($a) => $a['attended']),
            'member_id'
        );

        $this->render('trainings/attendance', [
            'title'       => 'Lista obecności — ' . $training['title'],
            'training'    => $training,
            'members'     => $members,
            'enrolledIds' => array_map('intval', $enrolledIds),
            'attendedIds' => array_map('intval', $attendedIds),
        ]);
    }

    public function saveAttendance(string $id): void
    {
        Csrf::verify();
        if (!Feature::enabled('trainings')) {
            $this->redirect('dashboard');
        }

        $this->getTraining((int)$id);

        $memberIds = array_map('intval', (array)($_POST['member_ids'] ?? []));
        $attended  = array_map('intval', (array)($_POST['attended']   ?? []));

        $this->model->saveAttendees((int)$id, $memberIds, $attended);

        Session::flash('success', 'Lista obecności została zapisana.');
        $this->redirect("trainings/{$id}");
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function getTraining(int $id): array
    {
        $t = $this->model->findWithDetails($id);
        if (!$t) {
            Session::flash('error', 'Trening nie istnieje.');
            $this->redirect('trainings');
        }
        return $t;
    }

    private function collectData(): array
    {
        return [
            'title'            => trim($_POST['title'] ?? ''),
            'training_date'    => $_POST['training_date'] ?? '',
            'time_start'       => ($_POST['time_start'] ?? '') ?: null,
            'time_end'         => ($_POST['time_end']   ?? '') ?: null,
            'lane'             => trim($_POST['lane']   ?? '') ?: null,
            'instructor_id'    => ($_POST['instructor_id'] ?? '') ?: null,
            'max_participants' => ($_POST['max_participants'] ?? '') ?: null,
            'status'           => in_array($_POST['status'] ?? '', ['planowany','odbyl_sie','odwolany'])
                                    ? $_POST['status'] : 'planowany',
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['title']))         $errors[] = 'Tytuł treningu jest wymagany.';
        if (empty($data['training_date'])) $errors[] = 'Data treningu jest wymagana.';
        return $errors;
    }
}
