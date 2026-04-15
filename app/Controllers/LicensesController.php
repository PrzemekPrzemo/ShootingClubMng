<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\LicenseModel;
use App\Models\LicenseTypeModel;
use App\Models\MemberModel;
use App\Models\DisciplineModel;

class LicensesController extends BaseController
{
    private LicenseModel $licenseModel;
    private LicenseTypeModel $licenseTypeModel;
    private MemberModel $memberModel;
    private DisciplineModel $disciplineModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->licenseModel     = new LicenseModel();
        $this->licenseTypeModel = new LicenseTypeModel();
        $this->memberModel      = new MemberModel();
        $this->disciplineModel  = new DisciplineModel();
    }

    public function index(): void
    {
        // Judge licenses are stored in a separate table — redirect to dedicated page
        $type = strtolower(trim($_GET['license_type'] ?? ''));
        if (in_array($type, ['sedziowska', 'sędziowska', 'sed', 'sedzia', 'sędzia'], true)) {
            $this->redirect('judges');
            return;
        }

        $filters = [
            'q'             => trim($_GET['q'] ?? ''),
            'license_type'  => $_GET['license_type'] ?? '',
            'status'        => $_GET['status'] ?? '',
            'member_id'     => $_GET['member_id'] ?? '',
            'expiring_days' => isset($_GET['expiring_days']) ? (int)$_GET['expiring_days'] : 0,
            'expired'       => !empty($_GET['expired']) ? 1 : 0,
        ];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->licenseModel->search($filters, $page);

        $this->render('licenses/index', [
            'title'        => 'Licencje PZSS',
            'result'       => $result,
            'filters'      => $filters,
            'licenseTypes' => $this->licenseTypeModel->getActive(),
        ]);
    }

    public function create(): void
    {
        $preselectedMember = null;
        if (!empty($_GET['member_id'])) {
            $preselectedMember = $this->memberModel->findById((int)$_GET['member_id']);
        }

        $this->render('licenses/form', [
            'title'               => 'Dodaj licencję',
            'license'             => null,
            'mode'                => 'create',
            'members'             => $this->memberModel->getAllActive(),
            'disciplines'         => $this->disciplineModel->getActive(),
            'licenseTypes'        => $this->licenseTypeModel->getActive(),
            'preselected'         => $preselectedMember,
            'selectedDisciplines' => [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        // Handle confirm-page actions first (extend / replace)
        $action = $_POST['force_action'] ?? '';
        if ($action === 'extend' && !empty($_POST['existing_id'])) {
            $this->doExtend((int)$_POST['existing_id']);
            return;
        }
        if ($action === 'replace' && !empty($_POST['existing_id'])) {
            $data = $this->collectData();
            $this->doReplace((int)$_POST['existing_id'], $data);
            return;
        }

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('licenses/create');
        }

        // Check for existing license of the same type
        $existing = $this->licenseModel->findExisting($data['member_id'], $data['license_type_id']);
        if ($existing) {
            Session::set('pending_license', $data);
            $this->redirect('licenses/confirm-duplicate?existing_id=' . $existing['id']);
            return;
        }

        $disciplineIds = $data['_discipline_ids'];
        unset($data['_no_expiry'], $data['_discipline_ids']);
        $newId = $this->licenseModel->create($data);
        $this->licenseModel->saveDisciplines($newId, $disciplineIds);
        Session::flash('success', 'Licencja została dodana.');
        if (!empty($data['member_id'])) {
            $this->redirect('members/' . $data['member_id'] . '/edit');
        } else {
            $this->redirect('licenses');
        }
    }

    public function confirmDuplicate(): void
    {
        $existingId = (int)($_GET['existing_id'] ?? 0);
        $existing   = $this->licenseModel->getWithMember($existingId);
        $pending    = Session::get('pending_license');

        if (!$existing || !$pending) {
            Session::flash('error', 'Brak danych do potwierdzenia.');
            $this->redirect('licenses/create');
        }

        $this->render('licenses/confirm_duplicate', [
            'title'    => 'Licencja już istnieje',
            'existing' => $existing,
            'pending'  => $pending,
        ]);
    }

    private function doExtend(int $existingId): void
    {
        $existing = $this->licenseModel->findById($existingId);
        if (!$existing) {
            Session::flash('error', 'Licencja nie istnieje.');
            $this->redirect('licenses');
            return;
        }

        // Extend to end of current year (or next year if already past)
        $currentEnd = $existing['valid_until'] ? strtotime($existing['valid_until']) : 0;
        $endOfYear  = strtotime(date('Y') . '-12-31');
        $newEnd     = $currentEnd >= $endOfYear
            ? date('Y', strtotime('+1 year')) . '-12-31'
            : date('Y') . '-12-31';

        $this->licenseModel->updateLicense($existingId, [
            'valid_until' => $newEnd,
            'status'      => 'aktywna',
        ]);

        Session::remove('pending_license');
        Session::flash('success', 'Licencja została przedłużona do ' . date('d.m.Y', strtotime($newEnd)) . '.');
        if (!empty($existing['member_id'])) {
            $this->redirect('members/' . $existing['member_id'] . '/edit');
        } else {
            $this->redirect('licenses');
        }
    }

    private function doReplace(int $existingId, array $data): void
    {
        $existing = $this->licenseModel->findById($existingId);
        if (!$existing) {
            Session::flash('error', 'Licencja nie istnieje.');
            $this->redirect('licenses');
            return;
        }

        $disciplineIds = $data['_discipline_ids'];
        unset($data['_no_expiry'], $data['_discipline_ids'], $data['created_by']);
        $this->licenseModel->updateLicense($existingId, $data);
        $this->licenseModel->saveDisciplines($existingId, $disciplineIds);

        Session::remove('pending_license');
        Session::flash('success', 'Licencja została zastąpiona nowymi danymi.');
        if (!empty($data['member_id'])) {
            $this->redirect('members/' . $data['member_id'] . '/edit');
        } else {
            $this->redirect('licenses');
        }
    }

    public function edit(string $id): void
    {
        $license = $this->licenseModel->getWithMember((int)$id);
        if (!$license) {
            Session::flash('error', 'Licencja nie istnieje.');
            $this->redirect('licenses');
        }

        $this->render('licenses/form', [
            'title'              => 'Edytuj licencję',
            'license'            => $license,
            'mode'               => 'edit',
            'members'            => $this->memberModel->getAllActive(),
            'disciplines'        => $this->disciplineModel->getActive(),
            'licenseTypes'       => $this->licenseTypeModel->getActive(),
            'preselected'        => null,
            'selectedDisciplines'=> $this->licenseModel->getDisciplineIds((int)$id),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("licenses/{$id}/edit");
        }

        $disciplineIds = $data['_discipline_ids'];
        unset($data['_no_expiry'], $data['_discipline_ids']);
        $this->licenseModel->updateLicense((int)$id, $data);
        $this->licenseModel->saveDisciplines((int)$id, $disciplineIds);
        Session::flash('success', 'Licencja została zaktualizowana.');
        if (!empty($data['member_id'])) {
            $this->redirect('members/' . $data['member_id'] . '/edit');
        } else {
            $this->redirect('licenses');
        }
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $this->licenseModel->delete((int)$id);
        Session::flash('success', 'Licencja została usunięta.');
        $this->redirect('licenses');
    }

    // ----------------------------------------------------------------

    private function collectData(): array
    {
        $typeId    = ($_POST['license_type_id'] ?? '') !== '' ? (int)$_POST['license_type_id'] : null;
        // Resolve short_code for backward-compat license_type column
        $typeCode    = 'zawodnicza';
        $noExpiry    = false;
        if ($typeId) {
            $lt = (new LicenseTypeModel())->findById($typeId);
            $typeCode = $lt['short_code'] ?? 'zawodnicza';
            $noExpiry = $lt !== null && $lt['validity_months'] === null;

            // Force no-expiry for Patent regardless of DB config
            $codeLc = strtolower($typeCode);
            $nameLc = strtolower($lt['name'] ?? '');
            if ($codeLc === 'patent' || $codeLc === 'pat' || str_contains($nameLc, 'patent')) {
                $noExpiry = true;
            }
        }
        $disciplineIds = array_values(array_filter(
            array_map('intval', (array)($_POST['discipline_ids'] ?? [])),
            fn($id) => $id > 0
        ));
        return [
            'member_id'       => (int)($_POST['member_id'] ?? 0),
            'license_type'    => $typeCode,
            'license_type_id' => $typeId,
            'license_number'  => trim($_POST['license_number'] ?? ''),
            'issue_date'      => $_POST['issue_date'] ?? '',
            'valid_until'     => $noExpiry ? null : ($_POST['valid_until'] ?? ''),
            'pzss_qr_code'    => trim($_POST['pzss_qr_code'] ?? '') ?: null,
            'status'          => $_POST['status'] ?? 'aktywna',
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'created_by'      => Auth::id(),
            '_no_expiry'      => $noExpiry,
            '_discipline_ids' => $disciplineIds,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['member_id']))                         $errors[] = 'Wybierz zawodnika.';
        if (empty($data['license_number']))                    $errors[] = 'Numer licencji jest wymagany.';
        if (empty($data['issue_date']))                        $errors[] = 'Data wydania jest wymagana.';
        if (empty($data['_no_expiry']) && empty($data['valid_until'])) $errors[] = 'Data ważności jest wymagana.';
        return $errors;
    }
}
