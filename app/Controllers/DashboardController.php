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

    public function stats(): void
    {
        $this->requireRole(['admin', 'zarzad']);

        $memberModel  = new MemberModel();
        $paymentModel = new PaymentModel();
        $compModel    = new CompetitionModel();
        $licModel     = new LicenseModel();
        $year         = (int)date('Y');

        // Member stats by status
        $memberByStatus = $memberModel->countByStatus();

        // Payments by month (current year)
        $paymentsByMonth = [];
        try {
            $stmt = \App\Helpers\Database::pdo()->prepare("
                SELECT MONTH(payment_date) AS m, SUM(amount) AS total
                FROM payments
                WHERE YEAR(payment_date) = ?
                GROUP BY MONTH(payment_date)
                ORDER BY m
            ");
            $stmt->execute([$year]);
            $rows = $stmt->fetchAll();
            $monthTotals = array_column($rows, 'total', 'm');
            for ($m = 1; $m <= 12; $m++) {
                $paymentsByMonth[] = (float)($monthTotals[$m] ?? 0);
            }
        } catch (\Throwable) {
            $paymentsByMonth = array_fill(0, 12, 0);
        }

        // Competitions per discipline (all time)
        $compStats = [];
        try {
            $stmt = \App\Helpers\Database::pdo()->prepare("
                SELECT d.name AS discipline, COUNT(c.id) AS cnt
                FROM competitions c
                JOIN disciplines d ON d.id = c.discipline_id
                GROUP BY d.id, d.name
                ORDER BY cnt DESC
                LIMIT 10
            ");
            $stmt->execute();
            $compStats = $stmt->fetchAll();
        } catch (\Throwable) {}

        // Active licenses by type
        $licStats = [];
        try {
            $stmt = \App\Helpers\Database::pdo()->query("
                SELECT lt.name, COUNT(l.id) AS cnt
                FROM licenses l
                JOIN license_types lt ON lt.id = l.license_type_id
                WHERE l.status = 'aktywna'
                GROUP BY lt.id, lt.name
            ");
            $licStats = $stmt->fetchAll();
        } catch (\Throwable) {}

        $this->render('dashboard/stats', [
            'title'           => 'Statystyki klubu',
            'memberStats'     => $memberByStatus,
            'paymentsByMonth' => $paymentsByMonth,
            'compStats'       => $compStats,
            'licStats'        => $licStats,
            'year'            => $year,
        ]);
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

        $alertLicDays = (int)club_setting('alert_license_days', 60);
        $alertMedDays = (int)club_setting('alert_medical_days', 30);
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
