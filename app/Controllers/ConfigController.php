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
use App\Models\LicenseTypeModel;
use App\Models\RolePermissionModel;

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
        $permModel = new RolePermissionModel();
        $this->render('config/users', [
            'title'      => 'Użytkownicy systemu',
            'users'      => $this->userModel->getAllUsers(),
            'permMatrix' => $permModel->getMatrix(),
            'modules'    => RolePermissionModel::MODULES,
            'roles'      => RolePermissionModel::ROLES,
        ]);
    }

    public function saveRolePermissions(): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);

        // Build matrix: [role => [module, ...]] from checkbox POST
        $posted = $_POST['perm'] ?? [];   // perm[role][module] = '1'
        $matrix = [];
        foreach (RolePermissionModel::ROLES as $role => $_) {
            $matrix[$role] = [];
            foreach (RolePermissionModel::MODULES as $mod => $_) {
                if (!empty($posted[$role][$mod])) {
                    $matrix[$role][] = $mod;
                }
            }
        }

        (new RolePermissionModel())->saveMatrix($matrix);
        Session::flash('success', 'Uprawnienia ról zostały zapisane.');
        $this->redirect('config/users#permissions');
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

    // ── Discipline event templates ───────────────────────────────────

    public function disciplineTemplates(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $discipline = $this->disciplineModel->findById((int)$id);
        if (!$discipline) {
            Session::flash('error', 'Dyscyplina nie istnieje.');
            $this->redirect('config/disciplines');
        }

        $editTid  = (int)($_GET['edit'] ?? 0);
        $editItem = $editTid ? $this->disciplineModel->findTemplate($editTid) : null;

        $this->render('config/discipline_templates', [
            'title'      => 'Szablony konkurencji — ' . $discipline['name'],
            'discipline' => $discipline,
            'templates'  => $this->disciplineModel->getEventTemplates((int)$id),
            'editItem'   => $editItem,
        ]);
    }

    public function saveTemplate(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $tid  = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            Session::flash('error', 'Nazwa szablonu jest wymagana.');
            $this->redirect("config/disciplines/{$id}/templates");
        }

        $feeOwn  = trim($_POST['fee_own_weapon']  ?? '');
        $feeClub = trim($_POST['fee_club_weapon'] ?? '');
        $data = [
            'discipline_id'  => (int)$id,
            'name'           => $name,
            'shots_count'    => ($_POST['shots_count'] ?? '') !== '' ? (int)$_POST['shots_count'] : null,
            'scoring_type'   => in_array($_POST['scoring_type'] ?? '', ['decimal','integer','hit_miss'])
                                ? $_POST['scoring_type'] : 'decimal',
            'max_score'      => ($_POST['max_score'] ?? '') !== ''
                                ? (float)str_replace(',', '.', $_POST['max_score']) : null,
            'fee_own_weapon'  => $feeOwn  !== '' ? (float)str_replace(',', '.', $feeOwn)  : null,
            'fee_club_weapon' => $feeClub !== '' ? (float)str_replace(',', '.', $feeClub) : null,
            'description'    => trim($_POST['description'] ?? '') ?: null,
            'sort_order'     => (int)($_POST['sort_order'] ?? 0),
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($tid > 0) {
            $this->disciplineModel->updateTemplate($tid, $data);
            Session::flash('success', 'Szablon zaktualizowany.');
        } else {
            $this->disciplineModel->saveTemplate($data);
            Session::flash('success', 'Szablon dodany.');
        }

        $this->redirect("config/disciplines/{$id}/templates");
    }

    public function deleteTemplate(string $id, string $tid): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $this->disciplineModel->deleteTemplate((int)$tid);
        Session::flash('success', 'Szablon usunięty.');
        $this->redirect("config/disciplines/{$id}/templates");
    }

    public function toggleTemplate(string $id, string $tid): void
    {
        Csrf::verify();
        $this->disciplineModel->toggleTemplate((int)$tid);
        $this->redirect("config/disciplines/{$id}/templates");
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

    // ── License types ───────────────────────────────────────────────

    public function licenseTypes(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $model   = new LicenseTypeModel();
        $editId  = (int)($_GET['edit'] ?? 0);
        $editItem = $editId ? $model->findById($editId) : null;

        $this->render('config/license_types', [
            'title'        => 'Typy licencji',
            'licenseTypes' => $model->getAll(),
            'editItem'     => $editItem,
        ]);
    }

    public function saveLicenseType(): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $model = new LicenseTypeModel();
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $code  = strtolower(trim(preg_replace('/\s+/', '_', $_POST['short_code'] ?? '')));

        if (empty($name) || empty($code)) {
            Session::flash('error', 'Nazwa i kod skrócony są wymagane.');
            $this->redirect('config/license-types');
        }

        $data = [
            'name'            => $name,
            'short_code'      => $code,
            'description'     => trim($_POST['description'] ?? '') ?: null,
            'validity_months' => ($_POST['validity_months'] ?? '') !== '' ? (int)$_POST['validity_months'] : null,
            'sort_order'      => (int)($_POST['sort_order'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id > 0) {
            $model->saveUpdate($id, $data);
            Session::flash('success', 'Typ licencji zaktualizowany.');
        } else {
            $model->save($data);
            Session::flash('success', 'Typ licencji dodany.');
        }

        $this->redirect('config/license-types');
    }

    public function deleteLicenseType(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $model = new LicenseTypeModel();
        $intId = (int)$id;

        if ($model->isUsed($intId)) {
            $model->toggle($intId);
            Session::flash('success', 'Typ licencji dezaktywowany (ma powiązane licencje).');
        } else {
            $model->delete($intId);
            Session::flash('success', 'Typ licencji usunięty.');
        }

        $this->redirect('config/license-types');
    }

    public function toggleLicenseType(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        (new LicenseTypeModel())->toggle((int)$id);
        $this->redirect('config/license-types');
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

    // ── Event templates overview ─────────────────────────────────────

    public function eventTemplates(): void
    {
        $this->requireRole(['admin', 'zarzad']);

        $disciplines = $this->disciplineModel->getAll();

        // Build [discipline_id => templates[]] for all active templates
        $byDiscipline = [];
        foreach ($this->disciplineModel->getAllTemplatesGrouped() as $g) {
            $byDiscipline[$g['discipline']['id']] = $g['templates'];
        }

        $this->render('config/event_templates', [
            'title'        => 'Szablony konkurencji',
            'disciplines'  => $disciplines,
            'byDiscipline' => $byDiscipline,
        ]);
    }
}
