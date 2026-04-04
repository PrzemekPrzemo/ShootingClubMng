<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\PaymentTypeModel;
use App\Models\MemberClassModel;

class FeeConfigController extends BaseController
{
    private PaymentTypeModel $typeModel;
    private MemberClassModel $classModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'zarzad']);
        $this->typeModel  = new PaymentTypeModel();
        $this->classModel = new MemberClassModel();
    }

    public function index(): void
    {
        $year    = (int)($_GET['year'] ?? date('Y'));
        $editId  = (int)($_GET['edit'] ?? 0);
        $editItem = $editId ? $this->typeModel->findById($editId) : null;

        $this->render('config/fee_rates', [
            'title'        => 'Cennik składek',
            'paymentTypes' => $this->typeModel->getAll(),
            'memberClasses'=> $this->classModel->getAll(),
            'rateMatrix'   => $this->typeModel->getRateMatrix($year),
            'year'         => $year,
            'editItem'     => $editItem,
            'categories'   => PaymentTypeModel::CATEGORIES,
        ]);
    }

    public function saveType(): void
    {
        Csrf::verify();

        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'category'    => in_array($_POST['category'] ?? '', array_keys(PaymentTypeModel::CATEGORIES))
                             ? $_POST['category'] : 'inne',
            'description' => trim($_POST['description'] ?? '') ?: null,
            'amount'      => (float)str_replace(',', '.', $_POST['amount'] ?? '0'),
            'is_per_class'=> isset($_POST['is_per_class']) ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['name'])) {
            Session::flash('error', 'Nazwa typu składki jest wymagana.');
            $this->redirect('config/fee-rates');
        }

        if ($id > 0) {
            $this->typeModel->saveUpdate($id, $data);
            Session::flash('success', 'Typ składki zaktualizowany.');
        } else {
            $this->typeModel->save($data);
            Session::flash('success', 'Typ składki dodany.');
        }

        $this->redirect('config/fee-rates');
    }

    public function deleteType(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);

        $intId = (int)$id;
        if ($this->typeModel->isUsed($intId)) {
            $this->typeModel->toggle($intId);
            Session::flash('success', 'Typ składki dezaktywowany (są powiązane wpłaty).');
        } else {
            $this->typeModel->delete($intId);
            Session::flash('success', 'Typ składki usunięty.');
        }

        $this->redirect('config/fee-rates');
    }

    public function saveRates(): void
    {
        Csrf::verify();

        $year  = (int)($_POST['year'] ?? date('Y'));
        $rates = $_POST['rates'] ?? [];

        if (!is_array($rates)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect("config/fee-rates?year={$year}");
        }

        $this->typeModel->saveRateMatrix($rates, $year, Auth::id());
        Session::flash('success', "Stawki na rok {$year} zostały zapisane.");
        $this->redirect("config/fee-rates?year={$year}");
    }

    /**
     * AJAX: returns suggested amount for a given payment_type + member_class + year.
     */
    public function getRate(): void
    {
        $typeId  = (int)($_GET['type_id'] ?? 0);
        $classId = ($_GET['class_id'] ?? '') !== '' ? (int)$_GET['class_id'] : null;
        $year    = (int)($_GET['year'] ?? date('Y'));

        header('Content-Type: application/json');
        if (!$typeId) {
            echo json_encode(['amount' => 0]);
            exit;
        }

        $amount = $this->typeModel->getEffectiveRate($typeId, $classId, $year);
        echo json_encode(['amount' => $amount]);
        exit;
    }
}
