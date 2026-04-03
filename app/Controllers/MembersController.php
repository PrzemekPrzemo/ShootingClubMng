<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Models\AgeCategoryModel;
use App\Models\DisciplineModel;
use App\Models\UserModel;

class MembersController extends BaseController
{
    private MemberModel $memberModel;
    private AgeCategoryModel $categoryModel;
    private DisciplineModel $disciplineModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->memberModel     = new MemberModel();
        $this->categoryModel   = new AgeCategoryModel();
        $this->disciplineModel = new DisciplineModel();
        $this->userModel       = new UserModel();
    }

    public function index(): void
    {
        $filters = [
            'q'               => trim($_GET['q'] ?? ''),
            'status'          => $_GET['status'] ?? '',
            'member_type'     => $_GET['member_type'] ?? '',
            'age_category_id' => $_GET['age_category_id'] ?? '',
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $result  = $this->memberModel->search($filters, $page);

        $this->render('members/index', [
            'title'      => 'Zawodnicy',
            'result'     => $result,
            'filters'    => $filters,
            'categories' => $this->categoryModel->getAll(),
        ]);
    }

    public function create(): void
    {
        $this->render('members/form', [
            'title'       => 'Dodaj zawodnika',
            'member'      => null,
            'categories'  => $this->categoryModel->getAll(),
            'disciplines' => $this->disciplineModel->getActive(),
            'instructors' => $this->userModel->getInstructors(),
            'mode'        => 'create',
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $data = $this->collectFormData();
        $errors = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            Session::flash('_old_input', $_POST);
            $this->redirect('members/create');
        }

        $id = $this->memberModel->createMember($data);

        // Handle disciplines
        $this->saveDisciplines($id);

        Session::flash('success', 'Zawodnik został dodany.');
        $this->redirect("members/{$id}");
    }

    public function show(string $id): void
    {
        $member = $this->memberModel->getWithDetails((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $this->render('members/show', [
            'title'       => $member['first_name'] . ' ' . $member['last_name'],
            'member'      => $member,
            'disciplines' => $this->memberModel->getDisciplines((int)$id),
            'medical'     => $this->memberModel->getLatestMedical((int)$id),
            'license'     => $this->memberModel->getLatestLicense((int)$id),
            'payment'     => $this->memberModel->getPaymentStatus((int)$id, (int)date('Y')),
        ]);
    }

    public function edit(string $id): void
    {
        $member = $this->memberModel->getWithDetails((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $this->render('members/form', [
            'title'       => 'Edytuj zawodnika',
            'member'      => $member,
            'categories'  => $this->categoryModel->getAll(),
            'disciplines' => $this->disciplineModel->getActive(),
            'instructors' => $this->userModel->getInstructors(),
            'memberDiscs' => $this->memberModel->getDisciplines((int)$id),
            'mode'        => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();

        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $data = $this->collectFormData();
        $errors = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("members/{$id}/edit");
        }

        $this->memberModel->updateMember((int)$id, $data);
        Session::flash('success', 'Dane zawodnika zostały zaktualizowane.');
        $this->redirect("members/{$id}");
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        // Soft delete — change status instead of delete
        $this->memberModel->updateMember((int)$id, ['status' => 'wykreslony']);
        Session::flash('success', 'Zawodnik został wykreślony.');
        $this->redirect('members');
    }

    // ----------------------------------------------------------------

    private function collectFormData(): array
    {
        return [
            'first_name'      => trim($_POST['first_name'] ?? ''),
            'last_name'       => trim($_POST['last_name'] ?? ''),
            'pesel'           => trim($_POST['pesel'] ?? '') ?: null,
            'birth_date'      => $_POST['birth_date'] ?: null,
            'gender'          => $_POST['gender'] ?: null,
            'age_category_id' => $_POST['age_category_id'] ?: null,
            'member_type'     => $_POST['member_type'] ?? 'rekreacyjny',
            'card_number'     => trim($_POST['card_number'] ?? '') ?: null,
            'email'           => trim($_POST['email'] ?? '') ?: null,
            'phone'           => trim($_POST['phone'] ?? '') ?: null,
            'address_street'  => trim($_POST['address_street'] ?? '') ?: null,
            'address_city'    => trim($_POST['address_city'] ?? '') ?: null,
            'address_postal'  => trim($_POST['address_postal'] ?? '') ?: null,
            'join_date'       => $_POST['join_date'] ?: date('Y-m-d'),
            'status'          => $_POST['status'] ?? 'aktywny',
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'created_by'      => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['first_name'])) $errors[] = 'Imię jest wymagane.';
        if (empty($data['last_name']))  $errors[] = 'Nazwisko jest wymagane.';
        if (empty($data['join_date']))  $errors[] = 'Data wstąpienia jest wymagana.';
        if (!in_array($data['member_type'], ['rekreacyjny','wyczynowy'])) {
            $errors[] = 'Nieprawidłowy typ członkostwa.';
        }
        return $errors;
    }

    private function saveDisciplines(int $memberId): void
    {
        $disciplineIds  = $_POST['discipline_ids'] ?? [];
        $classes        = $_POST['discipline_classes'] ?? [];
        $instructorIds  = $_POST['discipline_instructors'] ?? [];
        $joinedAt       = $_POST['discipline_joined'] ?? [];

        foreach ($disciplineIds as $i => $discId) {
            if (!$discId) continue;
            try {
                $this->memberModel->addDiscipline([
                    'member_id'     => $memberId,
                    'discipline_id' => (int)$discId,
                    'class'         => $classes[$i] ?: null,
                    'instructor_id' => $instructorIds[$i] ?: null,
                    'joined_at'     => $joinedAt[$i] ?: date('Y-m-d'),
                ]);
            } catch (\Throwable) {
                // duplicate — skip
            }
        }
    }
}
