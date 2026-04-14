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
        $filters = [
            'q'            => trim($_GET['q'] ?? ''),
            'license_type' => $_GET['license_type'] ?? '',
            'status'       => $_GET['status'] ?? '',
            'member_id'    => $_GET['member_id'] ?? '',
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

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('licenses/create');
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
