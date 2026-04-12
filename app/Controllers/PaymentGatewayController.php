<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\Przelewy24;
use App\Helpers\Session;
use App\Models\ClubSettingsModel;

/**
 * Przelewy24 payment gateway — member portal + P24 webhook.
 *
 * Routes:
 *   POST /portal/payment/initiate    — member initiates payment
 *   GET  /portal/payment/return      — P24 redirects user back
 *   POST /portal/payment/notify      — P24 webhook (no member session)
 *   GET  /admin/online-payments      — master admin view
 */
class PaymentGatewayController extends BaseController
{
    private \PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::pdo();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEMBER PORTAL — Initiate payment
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * POST /portal/payment/initiate
     * Starts a P24 transaction and redirects the member to P24 payment page.
     */
    public function initiate(): void
    {
        Session::start();
        MemberAuth::requireLogin();
        Csrf::verify();

        $memberId  = MemberAuth::id();
        $clubId    = (int)($this->db->query(
            "SELECT club_id FROM members WHERE id = $memberId LIMIT 1"
        )->fetchColumn() ?: 0);

        $p24 = Przelewy24::forClub($clubId);
        if (!$p24) {
            Session::flash('error', 'Płatność online nie jest skonfigurowana dla tego klubu.');
            $this->redirect('portal/fees');
        }

        $type        = $_POST['payment_type'] ?? 'fee';
        $referenceId = (int)($_POST['reference_id'] ?? 0);
        $amount      = (int)round((float)($_POST['amount'] ?? 0) * 100); // grosze
        $description = trim($_POST['description'] ?? 'Opłata klubowa');

        if ($amount <= 0) {
            Session::flash('error', 'Nieprawidłowa kwota płatności.');
            $this->redirect('portal/fees');
        }

        // Fetch member data for P24
        $member = $this->db->prepare("SELECT first_name, last_name, email FROM members WHERE id = ?");
        $member->execute([$memberId]);
        $memberData = $member->fetch() ?: [];

        // Unique session ID for P24
        $sessionId = 'SCM-' . $clubId . '-' . $memberId . '-' . time() . '-' . bin2hex(random_bytes(4));

        // Persist pending record BEFORE calling P24 (so we can reference it in webhook)
        $this->db->prepare("
            INSERT INTO online_payments
                (club_id, member_id, payment_type, reference_id, description, amount, currency, p24_session_id, payer_email, payer_name)
            VALUES (?, ?, ?, ?, ?, ?, 'PLN', ?, ?, ?)
        ")->execute([
            $clubId,
            $memberId,
            $type,
            $referenceId ?: null,
            $description,
            $amount / 100,
            $sessionId,
            $memberData['email'] ?? '',
            trim(($memberData['first_name'] ?? '') . ' ' . ($memberData['last_name'] ?? '')),
        ]);

        try {
            $result = $p24->registerTransaction([
                'sessionId'   => $sessionId,
                'amount'      => $amount,
                'description' => $description,
                'email'       => $memberData['email'] ?? '',
                'firstName'   => $memberData['first_name'] ?? '',
                'lastName'    => $memberData['last_name'] ?? '',
                'urlReturn'   => url('portal/payment/return?session=' . urlencode($sessionId)),
                'urlStatus'   => url('portal/payment/notify'),
            ]);

            // Save token
            $this->db->prepare("UPDATE online_payments SET p24_token = ? WHERE p24_session_id = ?")
                ->execute([$result['token'], $sessionId]);

            // Redirect to P24 payment page
            header('Location: ' . $result['redirectUrl']);
            exit;

        } catch (\RuntimeException $e) {
            // Mark as failed
            $this->db->prepare("UPDATE online_payments SET status = 'failed' WHERE p24_session_id = ?")
                ->execute([$sessionId]);

            Session::flash('error', 'Błąd inicjowania płatności: ' . $e->getMessage());
            $this->redirect('portal/fees');
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MEMBER PORTAL — Return after payment
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * GET /portal/payment/return?session=SCM-...
     * P24 redirects the user here after attempting payment.
     * The actual payment confirmation comes via notify() webhook.
     */
    public function returnFromPayment(): void
    {
        Session::start();
        MemberAuth::requireLogin();

        $sessionId = $_GET['session'] ?? '';
        $payment   = $this->findPayment($sessionId);

        if (!$payment) {
            Session::flash('error', 'Nie znaleziono transakcji.');
            $this->redirect('portal/fees');
        }

        // Show result page — status may still be 'pending' if webhook hasn't arrived yet
        $memberUser = [
            'full_name' => MemberAuth::name(),
            'email'     => MemberAuth::email(),
        ];

        $view = new \App\Helpers\View();
        echo $view->render('portal/payment_result', [
            'title'      => 'Wynik płatności',
            'payment'    => $payment,
            'memberUser' => $memberUser,
        ], 'portal');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // P24 WEBHOOK — Payment notification (no session, called by P24 servers)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * POST /portal/payment/notify
     * Called by Przelewy24 servers. Must respond 200 OK to confirm receipt.
     */
    public function notify(): void
    {
        // Disable session and output buffering — pure API endpoint
        $raw  = file_get_contents('php://input');
        $post = json_decode($raw, true) ?? $_POST;

        $sessionId = $post['sessionId'] ?? '';
        if ($sessionId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing sessionId']);
            exit;
        }

        $payment = $this->findPayment($sessionId);
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
            exit;
        }

        // Load P24 for the club
        $p24 = Przelewy24::forClub((int)$payment['club_id']);
        if (!$p24) {
            http_response_code(500);
            echo json_encode(['error' => 'P24 not configured']);
            exit;
        }

        // Verify notification signature
        if (!$p24->verifyNotification($post)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }

        $orderId = (int)($post['orderId'] ?? 0);
        $amount  = (int)($post['amount']  ?? 0);

        // Save P24 order ID
        $this->db->prepare("UPDATE online_payments SET p24_order_id = ?, p24_method_id = ? WHERE p24_session_id = ?")
            ->execute([$orderId, $post['methodId'] ?? null, $sessionId]);

        // Verify the transaction with P24 API (double confirmation)
        try {
            $verified = $p24->verifyTransaction($sessionId, $orderId, $amount);
        } catch (\RuntimeException) {
            $verified = false;
        }

        $newStatus = $verified ? 'verified' : 'failed';

        $this->db->prepare("UPDATE online_payments SET status = ?, updated_at = NOW() WHERE p24_session_id = ?")
            ->execute([$newStatus, $sessionId]);

        // If verified — create payment record in payments table automatically
        if ($verified && !empty($payment['member_id'])) {
            $this->bookPayment($payment, $amount);
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // MASTER ADMIN — All online payments across clubs
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * GET /admin/online-payments
     */
    public function adminIndex(): void
    {
        $this->requireSuperAdmin();

        $clubFilter   = (int)($_GET['club_id'] ?? 0);
        $statusFilter = $_GET['status'] ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $perPage      = 50;
        $offset       = ($page - 1) * $perPage;

        // Build WHERE
        $where  = ['1=1'];
        $params = [];
        if ($clubFilter > 0) {
            $where[]  = 'op.club_id = ?';
            $params[] = $clubFilter;
        }
        if (in_array($statusFilter, ['pending', 'verified', 'failed', 'cancelled'])) {
            $where[]  = 'op.status = ?';
            $params[] = $statusFilter;
        }
        $whereSql = implode(' AND ', $where);

        $countRow = $this->db->prepare("
            SELECT COUNT(*) FROM online_payments op WHERE $whereSql
        ");
        $countRow->execute($params);
        $total = (int)$countRow->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT op.*,
                   cl.name AS club_name,
                   CONCAT(m.first_name, ' ', m.last_name) AS member_name
            FROM   online_payments op
            JOIN   clubs   cl ON cl.id = op.club_id
            JOIN   members m  ON m.id  = op.member_id
            WHERE  $whereSql
            ORDER  BY op.created_at DESC
            LIMIT  $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $payments = $stmt->fetchAll();

        // Stats
        $statsStmt = $this->db->prepare("
            SELECT status, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total
            FROM   online_payments
            GROUP  BY status
        ");
        $statsStmt->execute();
        $stats = [];
        foreach ($statsStmt->fetchAll() as $row) {
            $stats[$row['status']] = $row;
        }

        // Club list for filter
        $clubs = $this->db->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name")->fetchAll();

        $this->render('admin/online_payments', [
            'title'        => 'Płatności online (P24)',
            'payments'     => $payments,
            'stats'        => $stats,
            'clubs'        => $clubs,
            'clubFilter'   => $clubFilter,
            'statusFilter' => $statusFilter,
            'page'         => $page,
            'perPage'      => $perPage,
            'total'        => $total,
            'pages'        => (int)ceil($total / $perPage),
        ]);
    }

    /**
     * POST /admin/online-payments/:id/cancel
     * Master admin can cancel a pending transaction.
     */
    public function adminCancel(string $id): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $this->db->prepare("
            UPDATE online_payments SET status = 'cancelled', updated_at = NOW()
            WHERE id = ? AND status = 'pending'
        ")->execute([(int)$id]);

        Session::flash('success', 'Transakcja oznaczona jako anulowana.');
        $this->redirect('admin/online-payments');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    private function findPayment(string $sessionId): array|false
    {
        if ($sessionId === '') {
            return false;
        }
        $stmt = $this->db->prepare("SELECT * FROM online_payments WHERE p24_session_id = ? LIMIT 1");
        $stmt->execute([$sessionId]);
        return $stmt->fetch() ?: false;
    }

    /**
     * After successful P24 payment, insert a record in the payments table
     * (creates a verified, traceable payment record for the club's finance module).
     */
    private function bookPayment(array $onlinePayment, int $amountGrosze): void
    {
        try {
            // Find or create a "Płatność online (P24)" payment type for the club
            $clubId = (int)$onlinePayment['club_id'];
            $stmt   = $this->db->prepare("
                SELECT id FROM payment_types
                WHERE club_id = ? AND LOWER(name) LIKE '%p24%' AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$clubId]);
            $typeId = $stmt->fetchColumn();

            if (!$typeId) {
                $this->db->prepare("
                    INSERT INTO payment_types (club_id, name, amount, is_active)
                    VALUES (?, 'Płatność online (Przelewy24)', 0.00, 1)
                ")->execute([$clubId]);
                $typeId = $this->db->lastInsertId();
            }

            $amount = $amountGrosze / 100;

            $this->db->prepare("
                INSERT INTO payments
                    (member_id, payment_type_id, amount, payment_date, period_year, method, reference, notes, created_by, club_id)
                VALUES (?, ?, ?, CURDATE(), YEAR(CURDATE()), 'przelew', ?, 'Opłacono przez Przelewy24', 0, ?)
            ")->execute([
                $onlinePayment['member_id'],
                $typeId,
                $amount,
                'P24-' . ($onlinePayment['p24_order_id'] ?? $onlinePayment['p24_session_id']),
                $clubId,
            ]);
        } catch (\Throwable) {
            // Non-critical — payment is already verified in online_payments
        }
    }
}
