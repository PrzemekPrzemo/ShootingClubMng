<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubFeeModel;

class ClubFeesController extends BaseController
{
    private ClubFeeModel $feeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'zarzad']);
        $this->feeModel = new ClubFeeModel();
    }

    public function index(string $year = ''): void
    {
        $year  = $year ? (int)$year : (int)date('Y');
        $fees  = $this->feeModel->getByYear($year);
        $calc  = $this->feeModel->calculateDue($year);
        $years = $this->feeModel->getYears();

        // Ensure current year is in list
        if (!in_array($year, $years)) {
            array_unshift($years, $year);
        }

        $this->render('club_fees/index', [
            'title'      => 'Opłaty PZSS/PomZSS — ' . $year,
            'year'       => $year,
            'fees'       => $fees,
            'calc'       => $calc,
            'years'      => $years,
            'totalDue'   => $this->feeModel->getTotalDue($year),
            'totalPaid'  => $this->feeModel->getTotalPaid($year),
            'feeLabels'  => ClubFeeModel::FEE_LABELS,
        ]);
    }

    public function calculate(): void
    {
        Csrf::verify();

        $year = (int)($_POST['year'] ?? date('Y'));
        $calc = $this->feeModel->calculateDue($year);

        foreach ($calc as $feeType => $data) {
            $this->feeModel->upsert([
                'year'       => $year,
                'fee_type'   => $feeType,
                'amount_due' => $data['amount_due'],
                'due_date'   => $data['due_date'],
            ], Auth::id());
        }

        Session::flash('success', "Zobowiązania na rok {$year} zostały obliczone.");
        $this->redirect("club-fees/{$year}");
    }

    public function markPaid(string $id): void
    {
        Csrf::verify();

        $paidDate  = $_POST['paid_date']   ?? date('Y-m-d');
        $paidAmount= (float)($_POST['paid_amount'] ?? 0);
        $reference = trim($_POST['reference'] ?? '') ?: null;
        $notes     = trim($_POST['notes'] ?? '') ?: null;

        $this->feeModel->markPaid((int)$id, $paidDate, $paidAmount, $reference, $notes);
        Session::flash('success', 'Opłata oznaczona jako zapłacona.');

        $year = $_POST['year'] ?? date('Y');
        $this->redirect("club-fees/{$year}");
    }
}
