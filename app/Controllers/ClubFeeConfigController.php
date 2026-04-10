<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubFeeConfigModel;
use App\Models\MemberAchievementModel;
use App\Models\MemberClassModel;
use App\Models\MemberTypeModel;

class ClubFeeConfigController extends BaseController
{
    private ClubFeeConfigModel $feeModel;
    private MemberClassModel   $classModel;
    private MemberTypeModel    $typeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'zarzad']);
        $this->feeModel   = new ClubFeeConfigModel();
        $this->classModel = new MemberClassModel();
        $this->typeModel  = new MemberTypeModel();
    }

    /** GET /config/fee-config?year=YYYY */
    public function index(): void
    {
        $clubId = ClubContext::current();
        $year   = (int)($_GET['year'] ?? date('Y'));

        $this->render('config/fee_config', [
            'title'           => 'Kalkulator składek',
            'year'            => $year,
            'clubId'          => $clubId,
            'memberTypes'     => $this->typeModel->getAll(),
            'memberClasses'   => $this->classModel->getAll(),
            'achievementTypes'=> MemberAchievementModel::TYPES,
            'feeConfig'       => $clubId ? $this->feeModel->getFeeConfig($clubId, $year)     : [],
            'classDiscounts'  => $clubId ? $this->feeModel->getClassDiscounts($clubId, $year) : [],
            'achDiscounts'    => $clubId ? $this->feeModel->getAchieveDiscounts($clubId, $year) : [],
            'recalcStats'     => $clubId ? $this->feeModel->getRecalcStats($clubId, $year)   : [],
        ]);
    }

    /** POST /config/fee-config/save */
    public function save(): void
    {
        Csrf::verify();

        $clubId = ClubContext::current();
        if ($clubId === null) {
            Session::flash('error', 'Konfiguracja składek dostępna tylko w kontekście klubu.');
            $this->redirect('config/fee-config');
        }

        $year = (int)($_POST['year'] ?? date('Y'));
        if ($year < 2020 || $year > 2100) {
            Session::flash('error', 'Nieprawidłowy rok.');
            $this->redirect('config/fee-config');
        }

        $this->feeModel->saveFeeConfig(
            $clubId,
            $year,
            (array)($_POST['fee_config'] ?? [])
        );

        $this->feeModel->saveClassDiscounts(
            $clubId,
            $year,
            (array)($_POST['discount_class'] ?? [])
        );

        $this->feeModel->saveAchieveDiscounts(
            $clubId,
            $year,
            (array)($_POST['discount_achieve'] ?? [])
        );

        Session::flash('success', "Konfiguracja składek na rok {$year} została zapisana.");
        $this->redirect("config/fee-config?year={$year}");
    }

    /** POST /config/fee-config/recalculate */
    public function recalculate(): void
    {
        Csrf::verify();

        $clubId = ClubContext::current();
        if ($clubId === null) {
            Session::flash('error', 'Przeliczanie dostępne tylko w kontekście klubu.');
            $this->redirect('config/fee-config');
        }

        $year = (int)($_POST['year'] ?? date('Y'));
        $stats = $this->feeModel->recalculateAll($clubId, $year);

        $totalFormatted = number_format($stats['total_annual'], 2, ',', ' ');
        Session::flash(
            'success',
            "Przeliczono składki za rok {$year}: <strong>{$stats['processed']}</strong> zawodników. "
            . "Łączna składka roczna: <strong>{$totalFormatted} PLN</strong>."
        );

        $this->redirect("config/fee-config?year={$year}");
    }
}
