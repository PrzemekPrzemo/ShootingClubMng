<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\JudgeLicenseModel;
use App\Models\MemberModel;
use App\Models\DisciplineModel;

class JudgesController extends BaseController
{
    private JudgeLicenseModel $judgeModel;
    private MemberModel $memberModel;
    private DisciplineModel $disciplineModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->judgeModel      = new JudgeLicenseModel();
        $this->memberModel     = new MemberModel();
        $this->disciplineModel = new DisciplineModel();
    }

    public function index(): void
    {
        $filters = [
            'judge_class' => $_GET['judge_class'] ?? '',
            'status'      => $_GET['status'] ?? '',
            'fee_paid'    => $_GET['fee_paid'] ?? '',
        ];

        $this->render('judges/index', [
            'title'    => 'Rejestr sędziów',
            'judges'   => $this->judgeModel->getAll($filters),
            'filters'  => $filters,
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $preselectedId = (int)($_GET['member_id'] ?? 0);
        $this->render('judges/form', [
            'title'       => 'Dodaj licencję sędziowską',
            'license'     => $preselectedId ? ['member_id' => $preselectedId] : null,
            'mode'        => 'create',
            'members'     => $this->memberModel->getAllActive(),
            'disciplines' => $this->disciplineModel->getActive(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('judges/create');
        }

        $this->judgeModel->create($data);
        Session::flash('success', 'Licencja sędziowska została dodana.');
        $this->redirect('judges');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $license = $this->getLicense((int)$id);
        $this->render('judges/form', [
            'title'       => 'Edytuj licencję sędziowską',
            'license'     => $license,
            'mode'        => 'edit',
            'members'     => $this->memberModel->getAllActive(),
            'disciplines' => $this->disciplineModel->getActive(),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("judges/{$id}/edit");
        }

        $this->judgeModel->updateLicense((int)$id, $data);
        Session::flash('success', 'Licencja sędziowska zaktualizowana.');
        $this->redirect('judges');
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $this->judgeModel->delete((int)$id);
        Session::flash('success', 'Licencja sędziowska usunięta.');
        $this->redirect('judges');
    }

    public function markFeePaid(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $this->judgeModel->markFeePaid((int)$id);
        Session::flash('success', 'Opłata PomZSS oznaczona jako zapłacona.');
        $this->redirect('judges');
    }

    // ----------------------------------------------------------------

    private function getLicense(int $id): array
    {
        $license = $this->judgeModel->findById($id);
        if (!$license) {
            Session::flash('error', 'Licencja nie istnieje.');
            $this->redirect('judges');
        }
        return $license;
    }

    private function collectData(): array
    {
        return [
            'member_id'      => (int)($_POST['member_id'] ?? 0),
            'judge_class'    => $_POST['judge_class'] ?? 'III',
            'discipline_id'  => $_POST['discipline_id'] ?: null,
            'license_number' => trim($_POST['license_number'] ?? '') ?: null,
            'issue_date'     => $_POST['issue_date'] ?? '',
            'valid_until'    => $_POST['valid_until'] ?? '',
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
            'created_by'     => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['member_id']))   $errors[] = 'Wybierz zawodnika.';
        if (empty($data['issue_date']))  $errors[] = 'Data wystawienia jest wymagana.';
        if (empty($data['valid_until'])) $errors[] = 'Data ważności jest wymagana.';
        if (!in_array($data['judge_class'], ['III','II','I','P'])) $errors[] = 'Nieprawidłowa klasa sędziowska.';
        return $errors;
    }
}
