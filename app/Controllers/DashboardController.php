<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\MemberModel;
use App\Models\LicenseModel;
use App\Models\MedicalExamModel;
use App\Models\JudgeLicenseModel;
use App\Models\ClubFeeModel;
use App\Models\PaymentModel;
use App\Models\CompetitionModel;
use App\Models\SettingModel;
use App\Models\NotificationModel;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }

    public function markNotificationsRead(): void
    {
        $role = Auth::role() ?? '';
        if (in_array($role, ['admin', 'zarzad'])) {
            (new NotificationModel())->markAllRead([$role]);
        }
        header('Location: ' . url('dashboard'));
        exit;
    }

    public function index(): void
    {
        $memberModel      = new MemberModel();
        $licenseModel     = new LicenseModel();
        $examModel        = new MedicalExamModel();
        $judgeModel       = new JudgeLicenseModel();
        $feeModel         = new ClubFeeModel();
        $paymentModel     = new PaymentModel();
        $competitionModel = new CompetitionModel();
        $settingModel     = new SettingModel();
        $notifModel       = new NotificationModel();

        $alertLicDays = (int)$settingModel->get('alert_license_days', 60);
        $alertMedDays = (int)$settingModel->get('alert_medical_days', 30);
        $year         = (int)date('Y');

        // Club fees summary
        $clubFeesTotalDue  = $feeModel->getTotalDue($year);
        $clubFeesTotalPaid = $feeModel->getTotalPaid($year);

        // Notifications for admin/zarząd
        $role          = Auth::role() ?? '';
        $notifRoles    = in_array($role, ['admin', 'zarzad']) ? [$role] : [];
        $notifications = $notifRoles ? $notifModel->getUnreadForRoles($notifRoles, 15) : [];
        $notifCount    = $notifRoles ? $notifModel->countUnreadForRoles($notifRoles) : 0;

        $this->render('dashboard/index', [
            'title'                => 'Dashboard',
            'memberStats'          => $memberModel->countByStatus(),
            'expiringLicenses'     => $licenseModel->getExpiring($alertLicDays),
            'expiringMedicals'     => $examModel->getExpiring($alertMedDays),
            'expiringJudgeLic'     => $judgeModel->getExpiring($alertLicDays),
            'clubFeesTotalDue'     => $clubFeesTotalDue,
            'clubFeesTotalPaid'    => $clubFeesTotalPaid,
            'clubFeesPending'      => max(0, $clubFeesTotalDue - $clubFeesTotalPaid),
            'debtorsCount'         => count($paymentModel->getDebtors($year)),
            'totalPaymentsYear'    => $paymentModel->getTotalByYear($year),
            'upcomingCompetitions' => $competitionModel->getUpcoming(30),
            'currentYear'          => $year,
            'alertLicDays'         => $alertLicDays,
            'alertMedDays'         => $alertMedDays,
            'notifications'        => $notifications,
            'notifCount'           => $notifCount,
        ]);
    }
}
