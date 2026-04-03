<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\MemberModel;
use App\Models\LicenseModel;
use App\Models\MedicalExamModel;
use App\Models\PaymentModel;
use App\Models\CompetitionModel;
use App\Models\SettingModel;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }

    public function index(): void
    {
        $memberModel    = new MemberModel();
        $licenseModel   = new LicenseModel();
        $examModel      = new MedicalExamModel();
        $paymentModel   = new PaymentModel();
        $competitionModel = new CompetitionModel();
        $settingModel   = new SettingModel();

        $alertLicDays  = (int)$settingModel->get('alert_license_days', 60);
        $alertMedDays  = (int)$settingModel->get('alert_medical_days', 30);
        $year          = (int)date('Y');

        $this->render('dashboard/index', [
            'title'            => 'Dashboard',
            'memberStats'      => $memberModel->countByStatus(),
            'expiringLicenses' => $licenseModel->getExpiring($alertLicDays),
            'expiringMedicals' => $examModel->getExpiring($alertMedDays),
            'debtorsCount'     => count($paymentModel->getDebtors($year)),
            'totalPaymentsYear' => $paymentModel->getTotalByYear($year),
            'upcomingCompetitions' => $competitionModel->getUpcoming(30),
            'currentYear'      => $year,
            'alertLicDays'     => $alertLicDays,
            'alertMedDays'     => $alertMedDays,
        ]);
    }
}
