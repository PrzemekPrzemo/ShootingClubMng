<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\PaymentModel;
use App\Models\MemberModel;

class FinancesController extends BaseController
{
    private PaymentModel $paymentModel;
    private MemberModel $memberModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->paymentModel = new PaymentModel();
        $this->memberModel  = new MemberModel();
    }

    public function index(): void
    {
        $year = (int)($_GET['year'] ?? date('Y'));
        $filters = [
            'q'               => trim($_GET['q'] ?? ''),
            'member_id'       => $_GET['member_id'] ?? '',
            'year'            => $year,
            'payment_type_id' => $_GET['payment_type_id'] ?? '',
        ];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->paymentModel->search($filters, $page);

        $this->render('finances/index', [
            'title'       => 'Finanse',
            'result'      => $result,
            'filters'     => $filters,
            'totalYear'   => $this->paymentModel->getTotalByYear($year),
            'summaryByType' => $this->paymentModel->getSummaryByType($year),
            'paymentTypes'  => $this->paymentModel->getPaymentTypes(),
            'currentYear'   => $year,
        ]);
    }

    public function debts(): void
    {
        $year = (int)($_GET['year'] ?? date('Y'));
        $this->render('finances/debts', [
            'title'   => 'Zaległości składkowe',
            'debtors' => $this->paymentModel->getDebtors($year),
            'year'    => $year,
        ]);
    }

    public function create(): void
    {
        $preselectedMember = null;
        if (!empty($_GET['member_id'])) {
            $preselectedMember = $this->memberModel->findById((int)$_GET['member_id']);
        }

        $this->render('finances/form', [
            'title'        => 'Dodaj wpłatę',
            'payment'      => null,
            'mode'         => 'create',
            'members'      => $this->memberModel->getAllActive(),
            'paymentTypes' => $this->paymentModel->getPaymentTypes(),
            'preselected'  => $preselectedMember,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('finances/create');
        }

        $this->paymentModel->create($data);
        Session::flash('success', 'Wpłata została zarejestrowana.');
        $this->redirect('finances');
    }

    public function edit(string $id): void
    {
        $payment = $this->paymentModel->getWithDetails((int)$id);
        if (!$payment) {
            Session::flash('error', 'Wpłata nie istnieje.');
            $this->redirect('finances');
        }

        $this->render('finances/form', [
            'title'        => 'Edytuj wpłatę',
            'payment'      => $payment,
            'mode'         => 'edit',
            'members'      => $this->memberModel->getAllActive(),
            'paymentTypes' => $this->paymentModel->getPaymentTypes(),
            'preselected'  => null,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("finances/{$id}/edit");
        }

        $this->paymentModel->updatePayment((int)$id, $data);
        Session::flash('success', 'Wpłata została zaktualizowana.');
        $this->redirect('finances');
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $this->paymentModel->delete((int)$id);
        Session::flash('success', 'Wpłata została usunięta.');
        $this->redirect('finances');
    }

    public function memberSearch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');

        if (mb_strlen($q) < 3) {
            echo json_encode(['members' => [], 'error' => 'Wpisz min. 3 znaki.']);
            exit;
        }

        $clubId = \App\Helpers\ClubContext::current();
        $db     = \App\Helpers\Database::pdo();

        $where  = ['m.status = ?'];
        $params = ['aktywny'];

        if ($clubId !== null) {
            $where[]  = 'm.club_id = ?';
            $params[] = $clubId;
        }

        if (ctype_digit($q)) {
            $where[]  = 'm.pesel LIKE ?';
            $params[] = $q . '%';
        } else {
            $where[]  = 'm.last_name LIKE ?';
            $params[] = $q . '%';
        }

        $whereClause = implode(' AND ', $where);
        $stmt = $db->prepare(
            "SELECT m.id, CONCAT(m.last_name, ' ', m.first_name) AS full_name,
                    m.member_number, m.member_class_id, m.birth_date
             FROM members m
             WHERE {$whereClause}
             ORDER BY m.last_name, m.first_name
             LIMIT 15"
        );
        $stmt->execute($params);

        echo json_encode(['members' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
        exit;
    }

    // ----------------------------------------------------------------

    private function collectData(): array
    {
        return [
            'member_id'       => (int)($_POST['member_id'] ?? 0),
            'payment_type_id' => (int)($_POST['payment_type_id'] ?? 0),
            'amount'          => (float)str_replace(',', '.', $_POST['amount'] ?? '0'),
            'payment_date'    => $_POST['payment_date'] ?? date('Y-m-d'),
            'period_year'     => (int)($_POST['period_year'] ?? date('Y')),
            'period_month'    => $_POST['period_month'] ?: null,
            'method'          => $_POST['method'] ?? 'gotówka',
            'reference'       => trim($_POST['reference'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'created_by'      => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['member_id']))       $errors[] = 'Wybierz zawodnika.';
        if (empty($data['payment_type_id'])) $errors[] = 'Wybierz typ opłaty.';
        if ($data['amount'] <= 0)            $errors[] = 'Kwota musi być większa od 0.';
        if (empty($data['payment_date']))    $errors[] = 'Data wpłaty jest wymagana.';
        if (empty($data['period_year']))     $errors[] = 'Rok okresu jest wymagany.';
        return $errors;
    }
}
