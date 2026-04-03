<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\LicenseModel;
use App\Models\MemberModel;
use App\Models\DisciplineModel;

class LicensesController extends BaseController
{
    private LicenseModel $licenseModel;
    private MemberModel $memberModel;
    private DisciplineModel $disciplineModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->licenseModel    = new LicenseModel();
        $this->memberModel     = new MemberModel();
        $this->disciplineModel = new DisciplineModel();
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
            'title'   => 'Licencje PZSS',
            'result'  => $result,
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $preselectedMember = null;
        if (!empty($_GET['member_id'])) {
            $preselectedMember = $this->memberModel->findById((int)$_GET['member_id']);
        }

        $this->render('licenses/form', [
            'title'       => 'Dodaj licencję',
            'license'     => null,
            'mode'        => 'create',
            'members'     => $this->memberModel->getAllActive(),
            'disciplines' => $this->disciplineModel->getActive(),
            'preselected' => $preselectedMember,
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

        $this->licenseModel->create($data);
        Session::flash('success', 'Licencja została dodana.');
        $this->redirect('licenses');
    }

    public function edit(string $id): void
    {
        $license = $this->licenseModel->getWithMember((int)$id);
        if (!$license) {
            Session::flash('error', 'Licencja nie istnieje.');
            $this->redirect('licenses');
        }

        $this->render('licenses/form', [
            'title'       => 'Edytuj licencję',
            'license'     => $license,
            'mode'        => 'edit',
            'members'     => $this->memberModel->getAllActive(),
            'disciplines' => $this->disciplineModel->getActive(),
            'preselected' => null,
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

        $this->licenseModel->updateLicense((int)$id, $data);
        Session::flash('success', 'Licencja została zaktualizowana.');
        $this->redirect('licenses');
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
        return [
            'member_id'      => (int)($_POST['member_id'] ?? 0),
            'license_type'   => $_POST['license_type'] ?? 'zawodnicza',
            'license_number' => trim($_POST['license_number'] ?? ''),
            'discipline_id'  => $_POST['discipline_id'] ?: null,
            'issue_date'     => $_POST['issue_date'] ?? '',
            'valid_until'    => $_POST['valid_until'] ?? '',
            'pzss_qr_code'   => trim($_POST['pzss_qr_code'] ?? '') ?: null,
            'status'         => $_POST['status'] ?? 'aktywna',
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
            'created_by'     => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['member_id']))      $errors[] = 'Wybierz zawodnika.';
        if (empty($data['license_number'])) $errors[] = 'Numer licencji jest wymagany.';
        if (empty($data['issue_date']))     $errors[] = 'Data wydania jest wymagana.';
        if (empty($data['valid_until']))    $errors[] = 'Data ważności jest wymagana.';
        return $errors;
    }
}
