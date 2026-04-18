<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
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
        Csrf::verify();
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

        $cid = \App\Helpers\ClubContext::current();

        // Payments by month (current year) — scoped to current club
        $paymentsByMonth = [];
        try {
            $sql = "SELECT MONTH(payment_date) AS m, SUM(amount) AS total
                    FROM payments
                    WHERE YEAR(payment_date) = ?";
            $params = [$year];
            if ($cid !== null) { $sql .= " AND club_id = ?"; $params[] = $cid; }
            $sql .= " GROUP BY MONTH(payment_date) ORDER BY m";
            $stmt = \App\Helpers\Database::pdo()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $monthTotals = array_column($rows, 'total', 'm');
            for ($m = 1; $m <= 12; $m++) {
                $paymentsByMonth[] = (float)($monthTotals[$m] ?? 0);
            }
        } catch (\Throwable) {
            $paymentsByMonth = array_fill(0, 12, 0);
        }

        // Competitions per discipline (all time) — scoped to current club
        $compStats = [];
        try {
            $sql = "SELECT d.name AS discipline, COUNT(c.id) AS cnt
                    FROM competitions c
                    JOIN disciplines d ON d.id = c.discipline_id";
            $params = [];
            if ($cid !== null) { $sql .= " WHERE c.club_id = ?"; $params[] = $cid; }
            $sql .= " GROUP BY d.id, d.name ORDER BY cnt DESC LIMIT 10";
            $stmt = \App\Helpers\Database::pdo()->prepare($sql);
            $stmt->execute($params);
            $compStats = $stmt->fetchAll();
        } catch (\Throwable) {}

        // Active licenses by type — scoped to current club (via member)
        $licStats = [];
        try {
            $sql = "SELECT lt.name, COUNT(l.id) AS cnt
                    FROM licenses l
                    JOIN license_types lt ON lt.id = l.license_type_id
                    JOIN members m ON m.id = l.member_id
                    WHERE l.status = 'aktywna'";
            $params = [];
            if ($cid !== null) { $sql .= " AND m.club_id = ?"; $params[] = $cid; }
            $sql .= " GROUP BY lt.id, lt.name";
            $stmt = \App\Helpers\Database::pdo()->prepare($sql);
            $stmt->execute($params);
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
        // Zawodnik (staff role) → attempt bridge to member portal
        if (Auth::role() === 'zawodnik') {
            $bridged = \App\Helpers\MemberAuth::check();
            if (!$bridged) {
                $userId  = Auth::id();
                $clubId  = \App\Helpers\ClubContext::current();
                $db      = \App\Helpers\Database::pdo();
                // Fetch staff user record
                $uStmt   = $db->prepare("SELECT email, member_id FROM users WHERE id = ? LIMIT 1");
                $uStmt->execute([$userId]);
                $uRow    = $uStmt->fetch();
                $member  = null;

                // Priority 1: explicit member_id link
                if (!empty($uRow['member_id'])) {
                    $mStmt = $db->prepare(
                        "SELECT * FROM members WHERE id = ? AND status = 'aktywny' LIMIT 1"
                    );
                    $mStmt->execute([(int)$uRow['member_id']]);
                    $member = $mStmt->fetch() ?: null;
                }

                // Priority 2: email + club_id matching (legacy / auto-bridge)
                if (!$member && !empty($uRow['email']) && $clubId) {
                    $mStmt = $db->prepare(
                        "SELECT * FROM members WHERE email = ? AND club_id = ? AND status = 'aktywny' LIMIT 1"
                    );
                    $mStmt->execute([$uRow['email'], $clubId]);
                    $member = $mStmt->fetch() ?: null;
                }

                if ($member) {
                    \App\Helpers\Session::set('member_id',             (int)$member['id']);
                    \App\Helpers\Session::set('member_full_name',      $member['first_name'] . ' ' . $member['last_name']);
                    \App\Helpers\Session::set('member_email',          $member['email'] ?? '');
                    \App\Helpers\Session::set('member_status',         $member['status']);
                    \App\Helpers\Session::set('must_change_password',  false);
                    $bridged = true;
                }
            }
            // Only redirect to portal if bridge succeeded — prevents infinite loop
            // when no matching member record exists for this staff user
            if ($bridged) {
                $this->redirect('portal');
            }
            // Fall through: show dashboard with a notice about missing member record
            \App\Helpers\Session::flash('info', 'Brak powiązanego rekordu zawodnika z tym kontem. Zaloguj się do portalu osobno lub skontaktuj z administratorem.');
        }

        // Non-zawodnik staff with explicit member_id link → bridge portal session silently
        // (allows dual-context: staff panel + member portal in same browser session)
        if (Auth::role() !== 'zawodnik' && !\App\Helpers\MemberAuth::check()) {
            $userId = Auth::id();
            if ($userId) {
                $db    = \App\Helpers\Database::pdo();
                $uStmt = $db->prepare("SELECT member_id FROM users WHERE id = ? AND member_id IS NOT NULL LIMIT 1");
                $uStmt->execute([$userId]);
                $linkedMemberId = (int)$uStmt->fetchColumn();
                if ($linkedMemberId) {
                    $mStmt = $db->prepare("SELECT * FROM members WHERE id = ? AND status = 'aktywny' LIMIT 1");
                    $mStmt->execute([$linkedMemberId]);
                    $linkedMember = $mStmt->fetch();
                    if ($linkedMember) {
                        \App\Helpers\Session::set('member_id',            $linkedMemberId);
                        \App\Helpers\Session::set('member_full_name',     $linkedMember['first_name'] . ' ' . $linkedMember['last_name']);
                        \App\Helpers\Session::set('member_email',         $linkedMember['email'] ?? '');
                        \App\Helpers\Session::set('member_status',        $linkedMember['status']);
                        \App\Helpers\Session::set('must_change_password', false);
                    }
                }
            }
        }

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
            'expiredLicensesCount' => $licenseModel->countExpired(),
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
