<?php

namespace App\Controllers;

use App\Models\MemberModel;
use App\Models\LicenseModel;
use App\Models\PaymentModel;
use App\Models\CompetitionModel;
use App\Models\WeaponModel;
use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\PdfHelper;

class ReportsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }

    public function index(): void
    {
        $this->render('reports/index', ['title' => 'Raporty']);
    }

    public function members(): void
    {
        $model   = new MemberModel();
        $filters = [
            'status'      => $_GET['status'] ?? '',
            'member_type' => $_GET['member_type'] ?? '',
            'q'           => $_GET['q'] ?? '',
        ];
        $result = $model->search($filters, 1, 9999);

        if ($this->isCsvRequest()) {
            $this->sendCsv(
                'zawodnicy_' . date('Y-m-d') . '.csv',
                ['Nr', 'Nazwisko', 'Imię', 'Typ', 'Status', 'Kategoria', 'Nr karty', 'Data wstąpienia', 'E-mail', 'Telefon'],
                array_map(fn($m) => [
                    $m['member_number'], $m['last_name'], $m['first_name'],
                    $m['member_type'], $m['status'], $m['age_category_name'] ?? '',
                    $m['card_number'] ?? '', $m['join_date'], $m['email'] ?? '', $m['phone'] ?? '',
                ], $result['data'])
            );
        }

        $this->render('reports/members', [
            'title'   => 'Raport — Zawodnicy',
            'result'  => $result,
            'filters' => $filters,
        ]);
    }

    public function finances(): void
    {
        $model  = new PaymentModel();
        $year   = (int)($_GET['year'] ?? date('Y'));
        $type   = $_GET['type'] ?? 'payments';

        if ($type === 'debts') {
            $data = $model->getDebtors($year);
            if ($this->isCsvRequest()) {
                $this->sendCsv(
                    'zaleglosci_' . $year . '.csv',
                    ['Nr', 'Nazwisko', 'Imię', 'E-mail', 'Telefon'],
                    array_map(fn($d) => [
                        $d['member_number'], $d['last_name'], $d['first_name'],
                        $d['email'] ?? '', $d['phone'] ?? '',
                    ], $data)
                );
            }
            $this->render('reports/finances', [
                'title'     => 'Raport — Zaległości ' . $year,
                'type'      => 'debts',
                'data'      => $data,
                'year'      => $year,
                'summary'   => [],
            ]);
            return;
        }

        $filters = ['year' => $year];
        $result  = $model->search($filters, 1, 9999);
        $summary = $model->getSummaryByType($year);

        if ($this->isCsvRequest()) {
            $this->sendCsv(
                'wplaty_' . $year . '.csv',
                ['Data', 'Zawodnik', 'Nr', 'Typ', 'Kwota', 'Metoda', 'Rok', 'Miesiąc', 'Ref'],
                array_map(fn($p) => [
                    $p['payment_date'], $p['last_name'] . ' ' . $p['first_name'],
                    $p['member_number'], $p['type_name'], $p['amount'],
                    $p['method'], $p['period_year'], $p['period_month'] ?? '',
                    $p['reference'] ?? '',
                ], $result['data'])
            );
        }

        $this->render('reports/finances', [
            'title'   => 'Raport finansowy ' . $year,
            'type'    => 'payments',
            'data'    => $result['data'],
            'year'    => $year,
            'summary' => $summary,
            'total'   => $model->getTotalByYear($year),
        ]);
    }

    public function licenses(): void
    {
        $model   = new LicenseModel();
        $filters = ['status' => $_GET['status'] ?? '', 'license_type' => $_GET['license_type'] ?? ''];
        $result  = $model->search($filters, 1, 9999);

        if ($this->isCsvRequest()) {
            $this->sendCsv(
                'licencje_' . date('Y-m-d') . '.csv',
                ['Nr licencji', 'Typ', 'Zawodnik', 'Nr członkowski', 'Dyscyplina', 'Wydana', 'Ważna do', 'Status'],
                array_map(fn($l) => [
                    $l['license_number'], $l['license_type'],
                    $l['last_name'] . ' ' . $l['first_name'], $l['member_number'],
                    $l['discipline_name'] ?? '', $l['issue_date'], $l['valid_until'], $l['status'],
                ], $result['data'])
            );
        }

        $this->render('reports/licenses', [
            'title'   => 'Raport — Licencje',
            'data'    => $result['data'],
            'filters' => $filters,
        ]);
    }

    public function competitions(): void
    {
        $model  = new CompetitionModel();
        $year   = $_GET['year'] ?? date('Y');
        $filters = ['year' => $year];
        $result  = $model->getAll($filters, 1, 9999);

        if ($this->isCsvRequest()) {
            $this->sendCsv(
                'zawody_' . $year . '.csv',
                ['Nazwa', 'Data', 'Dyscyplina', 'Miejsce', 'Status', 'Zgłoszeń'],
                array_map(fn($c) => [
                    $c['name'], $c['competition_date'], $c['discipline_name'] ?? '',
                    $c['location'] ?? '', $c['status'], $c['entry_count'],
                ], $result['data'])
            );
        }

        $this->render('reports/competitions', [
            'title' => 'Raport — Zawody ' . $year,
            'data'  => $result['data'],
            'year'  => $year,
        ]);
    }

    public function pzss(): void
    {
        $memberModel = new MemberModel();
        $type = $_GET['type'] ?? 'members';

        if ($type === 'members') {
            $result = $memberModel->search(['status' => 'aktywny'], 1, 9999);
            if ($this->isCsvRequest()) {
                $this->sendCsv(
                    'pzss_zawodnicy_' . date('Y-m-d') . '.csv',
                    ['Nr członk.', 'Nazwisko', 'Imię', 'Data ur.', 'Klasa', 'Nr licencji', 'Ważna do', 'Dyscypliny'],
                    array_map(fn($m) => [
                        $m['member_number'], $m['last_name'], $m['first_name'],
                        $m['birth_date'] ?? '', $m['member_class_name'] ?? '',
                        '', '', '', // license and disciplines would need additional join
                    ], $result['data'])
                );
            }
            $this->render('reports/pzss', [
                'title'   => 'Raport PZSS — Zawodnicy',
                'type'    => 'members',
                'data'    => $result['data'],
            ]);
            return;
        }

        // Competition results
        $compModel = new CompetitionModel();
        $year      = $_GET['year'] ?? date('Y');
        $result    = $compModel->getAll(['year' => $year], 1, 9999);

        if ($this->isCsvRequest()) {
            $this->sendCsv(
                'pzss_wyniki_' . $year . '.csv',
                ['Zawody', 'Data', 'Dyscyplina', 'Miejsce', 'Status', 'Zgłoszeń'],
                array_map(fn($c) => [
                    $c['name'], $c['competition_date'], $c['discipline_name'] ?? '',
                    $c['location'] ?? '', $c['status'], $c['entry_count'],
                ], $result['data'])
            );
        }

        $this->render('reports/pzss', [
            'title' => 'Raport PZSS — Wyniki ' . $year,
            'type'  => 'results',
            'data'  => $result['data'],
            'year'  => $year,
        ]);
    }

    public function equipment(): void
    {
        try {
            $model   = new WeaponModel();
            $result  = $model->getAll([], 1, 9999);
            $weapons = $result['data'] ?? $result;

            if ($this->isCsvRequest()) {
                $this->sendCsv(
                    'sprzet_' . date('Y-m-d') . '.csv',
                    ['ID', 'Nazwa', 'Typ', 'Numer seryjny', 'Kaliber', 'Producent', 'Stan', 'Przypisany do'],
                    array_map(fn($w) => [
                        $w['id'], $w['name'], $w['type'],
                        $w['serial_number'] ?? '', $w['caliber'] ?? '', $w['manufacturer'] ?? '',
                        $w['condition'],
                        trim(($w['assigned_to_last'] ?? '') . ' ' . ($w['assigned_to_first'] ?? '')),
                    ], $weapons)
                );
            }

            $this->render('reports/equipment', [
                'title'   => 'Raport — Sprzęt',
                'weapons' => $weapons,
            ]);
        } catch (\Throwable) {
            $this->render('reports/equipment', [
                'title'   => 'Raport — Sprzęt',
                'weapons' => [],
            ]);
        }
    }

    // ----------------------------------------------------------------

    private function isCsvRequest(): bool
    {
        return ($_GET['format'] ?? '') === 'csv';
    }

    private function sendCsv(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }

    // ── Financial PDF report ──────────────────────────────────────────

    public function financePdf(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $this->requireClubContext();

        $clubId = ClubContext::current();
        $year   = (int)($_GET['year'] ?? date('Y'));
        $db     = Database::getInstance();

        try {
            // Monthly summary
            $stmt = $db->prepare(
                "SELECT MONTH(payment_date) AS month,
                        SUM(amount) AS total,
                        COUNT(*) AS count
                 FROM payments
                 WHERE club_id = ? AND YEAR(payment_date) = ?
                 GROUP BY MONTH(payment_date)
                 ORDER BY MONTH(payment_date)"
            );
            $stmt->execute([$clubId, $year]);
            $monthly = $stmt->fetchAll();

            // By type
            $stmt = $db->prepare(
                "SELECT pt.name AS type_name, SUM(p.amount) AS total, COUNT(*) AS count
                 FROM payments p
                 JOIN payment_types pt ON pt.id = p.payment_type_id
                 WHERE p.club_id = ? AND YEAR(p.payment_date) = ?
                 GROUP BY pt.name
                 ORDER BY total DESC"
            );
            $stmt->execute([$clubId, $year]);
            $byType = $stmt->fetchAll();

            // Club name
            $clubName = $db->prepare("SELECT name FROM clubs WHERE id=?");
            $clubName->execute([$clubId]);
            $clubName = $clubName->fetchColumn() ?: 'Klub';

            $yearTotal = array_sum(array_column($monthly, 'total'));
        } catch (\Throwable $e) {
            \App\Helpers\Session::flash('error', 'Błąd generowania raportu: ' . $e->getMessage());
            $this->redirect('reports');
        }

        $html = $this->renderToString('pdf/finance_report', [
            'clubName' => $clubName,
            'year'     => $year,
            'monthly'  => $monthly,
            'byType'   => $byType,
            'total'    => $yearTotal,
        ]);

        PdfHelper::send($html, "raport_finansowy_{$year}.pdf", 'A4', false);
    }
}
