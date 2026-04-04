<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\SettingModel;
use App\Models\AgeCategoryModel;
use App\Models\UserModel;
use App\Models\DisciplineModel;
use App\Models\MemberClassModel;
use App\Models\MedicalExamTypeModel;

class ConfigController extends BaseController
{
    private SettingModel $settingModel;
    private AgeCategoryModel $categoryModel;
    private UserModel $userModel;
    private DisciplineModel $disciplineModel;
    private MemberClassModel $memberClassModel;
    private MedicalExamTypeModel $examTypeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'zarzad']);
        $this->settingModel     = new SettingModel();
        $this->categoryModel    = new AgeCategoryModel();
        $this->userModel        = new UserModel();
        $this->disciplineModel  = new DisciplineModel();
        $this->memberClassModel = new MemberClassModel();
        $this->examTypeModel    = new MedicalExamTypeModel();
    }

    public function index(): void
    {
        $this->render('config/index', [
            'title'    => 'Konfiguracja',
            'settings' => $this->settingModel->getAll(),
        ]);
    }

    public function save(): void
    {
        Csrf::verify();

        $allowed = [
            'club_name', 'club_address', 'club_email', 'club_phone',
            'alert_payment_days', 'alert_license_days', 'alert_medical_days',
            'membership_fee_due_month', 'pzss_portal_url',
        ];

        $data = [];
        foreach ($allowed as $key) {
            $data[$key] = trim($_POST[$key] ?? '');
        }

        $this->settingModel->saveMany($data);
        Session::flash('success', 'Konfiguracja została zapisana.');
        $this->redirect('config');
    }

    public function categories(): void
    {
        $this->render('config/categories', [
            'title'      => 'Kategorie wiekowe',
            'categories' => $this->categoryModel->getAll(),
        ]);
    }

    public function saveCategory(): void
    {
        Csrf::verify();

        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'age_from'   => (int)($_POST['age_from'] ?? 0),
            'age_to'     => (int)($_POST['age_to'] ?? 0),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];

        if (empty($data['name'])) {
            Session::flash('error', 'Nazwa kategorii jest wymagana.');
            $this->redirect('config/categories');
        }

        if ($id > 0) {
            $this->categoryModel->saveUpdate($id, $data);
            Session::flash('success', 'Kategoria zaktualizowana.');
        } else {
            $this->categoryModel->save($data);
            Session::flash('success', 'Kategoria dodana.');
        }

        $this->redirect('config/categories');
    }

    public function deleteCategory(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $this->categoryModel->delete((int)$id);
        Session::flash('success', 'Kategoria usunięta.');
        $this->redirect('config/categories');
    }

    // Users management
    public function users(): void
    {
        $this->render('config/users', [
            'title' => 'Użytkownicy systemu',
            'users' => $this->userModel->getAllUsers(),
        ]);
    }

    public function createUser(): void
    {
        $this->requireRole(['admin']);
        $this->render('config/user_form', [
            'title' => 'Dodaj użytkownika',
            'user'  => null,
            'mode'  => 'create',
        ]);
    }

    public function storeUser(): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);

        $data   = $this->collectUserData();
        $errors = $this->validateUser($data, true);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('config/users/create');
        }

        $this->userModel->createUser($data);
        Session::flash('success', 'Użytkownik został dodany.');
        $this->redirect('config/users');
    }

    public function editUser(string $id): void
    {
        $this->requireRole(['admin']);
        $user = $this->userModel->findById((int)$id);
        $this->render('config/user_form', [
            'title' => 'Edytuj użytkownika',
            'user'  => $user,
            'mode'  => 'edit',
        ]);
    }

    public function updateUser(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);

        $data   = $this->collectUserData();
        $errors = $this->validateUser($data, false);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("config/users/{$id}/edit");
        }

        $this->userModel->updateUser((int)$id, $data);
        Session::flash('success', 'Użytkownik zaktualizowany.');
        $this->redirect('config/users');
    }

    public function deleteUser(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $this->userModel->update((int)$id, ['is_active' => 0]);
        Session::flash('success', 'Użytkownik dezaktywowany.');
        $this->redirect('config/users');
    }

    // ── Disciplines ──────────────────────────────────────────────────

    public function disciplines(): void
    {
        $editId   = (int)($_GET['edit'] ?? 0);
        $editItem = $editId ? $this->disciplineModel->findById($editId) : null;

        $this->render('config/disciplines', [
            'title'       => 'Słownik dyscyplin',
            'disciplines' => $this->disciplineModel->getAll(),
            'editItem'    => $editItem,
        ]);
    }

    public function saveDiscipline(): void
    {
        Csrf::verify();

        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_code' => strtoupper(trim($_POST['short_code'] ?? '')),
            'description'=> trim($_POST['description'] ?? ''),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['name']) || empty($data['short_code'])) {
            Session::flash('error', 'Nazwa i kod skrócony są wymagane.');
            $this->redirect('config/disciplines');
        }

        if ($id > 0) {
            $this->disciplineModel->saveUpdate($id, $data);
            Session::flash('success', 'Dyscyplina zaktualizowana.');
        } else {
            $this->disciplineModel->save($data);
            Session::flash('success', 'Dyscyplina dodana.');
        }

        $this->redirect('config/disciplines');
    }

    public function deleteDiscipline(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);

        $intId = (int)$id;
        if ($this->disciplineModel->isUsed($intId)) {
            // Dezaktywuj zamiast usuwać — są powiązane rekordy
            $this->disciplineModel->toggle($intId);
            Session::flash('success', 'Dyscyplina dezaktywowana (ma powiązane dane).');
        } else {
            $this->disciplineModel->delete($intId);
            Session::flash('success', 'Dyscyplina usunięta.');
        }

        $this->redirect('config/disciplines');
    }

    public function toggleDiscipline(string $id): void
    {
        Csrf::verify();
        $this->disciplineModel->toggle((int)$id);
        Session::flash('success', 'Status dyscypliny zmieniony.');
        $this->redirect('config/disciplines');
    }

    // ── Member classes ───────────────────────────────────────────────

    public function memberClasses(): void
    {
        $editId   = (int)($_GET['edit'] ?? 0);
        $editItem = $editId ? $this->memberClassModel->findById($editId) : null;

        $this->render('config/member_classes', [
            'title'    => 'Klasy zawodników',
            'classes'  => $this->memberClassModel->getAll(),
            'editItem' => $editItem,
        ]);
    }

    public function saveMemberClass(): void
    {
        Csrf::verify();

        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_code' => strtoupper(trim($_POST['short_code'] ?? '')),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['name']) || empty($data['short_code'])) {
            Session::flash('error', 'Nazwa i kod skrócony są wymagane.');
            $this->redirect('config/member-classes');
        }

        if ($id > 0) {
            $this->memberClassModel->saveUpdate($id, $data);
            Session::flash('success', 'Klasa zaktualizowana.');
        } else {
            $this->memberClassModel->save($data);
            Session::flash('success', 'Klasa dodana.');
        }

        $this->redirect('config/member-classes');
    }

    public function deleteMemberClass(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $this->memberClassModel->delete((int)$id);
        Session::flash('success', 'Klasa usunięta.');
        $this->redirect('config/member-classes');
    }

    // ── Medical Exam Types ───────────────────────────────────────────

    public function medicalExamTypes(): void
    {
        $editId   = (int)($_GET['edit'] ?? 0);
        $editItem = $editId ? $this->examTypeModel->findById($editId) : null;

        $this->render('config/medical_exam_types', [
            'title'     => 'Typy badań lekarskich',
            'examTypes' => $this->examTypeModel->getAll(),
            'editItem'  => $editItem,
        ]);
    }

    public function saveMedicalExamType(): void
    {
        Csrf::verify();

        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'required_for'    => in_array($_POST['required_for'] ?? '', ['patent','license','both']) ? $_POST['required_for'] : 'both',
            'validity_months' => max(1, (int)($_POST['validity_months'] ?? 12)),
            'sort_order'      => (int)($_POST['sort_order'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['name'])) {
            Session::flash('error', 'Nazwa typu badania jest wymagana.');
            $this->redirect('config/medical-exam-types');
        }

        if ($id > 0) {
            $this->examTypeModel->saveUpdate($id, $data);
            Session::flash('success', 'Typ badania zaktualizowany.');
        } else {
            $this->examTypeModel->save($data);
            Session::flash('success', 'Typ badania dodany.');
        }

        $this->redirect('config/medical-exam-types');
    }

    public function deleteMedicalExamType(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $this->examTypeModel->toggle((int)$id);
        Session::flash('success', 'Status typu badania zmieniony.');
        $this->redirect('config/medical-exam-types');
    }

    // ── Users ────────────────────────────────────────────────────────

    private function collectUserData(): array
    {
        return [
            'username'  => trim($_POST['username'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role'      => $_POST['role'] ?? 'instruktor',
            'password'  => $_POST['password'] ?? '',
            'is_active' => 1,
        ];
    }

    private function validateUser(array $data, bool $isCreate): array
    {
        $errors = [];
        if (empty($data['username']))  $errors[] = 'Login jest wymagany.';
        if (empty($data['email']))     $errors[] = 'E-mail jest wymagany.';
        if (empty($data['full_name'])) $errors[] = 'Imię i nazwisko jest wymagane.';
        if ($isCreate && empty($data['password'])) $errors[] = 'Hasło jest wymagane.';
        if (!empty($data['password']) && strlen($data['password']) < 8) $errors[] = 'Hasło musi mieć co najmniej 8 znaków.';
        if (!in_array($data['role'], ['admin','zarzad','instruktor'])) $errors[] = 'Nieprawidłowa rola.';
        return $errors;
    }
}
