<?php
namespace App\Controllers;

use App\Helpers\Feature;
use App\Models\CompetitionModel;

class CalendarController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }

    public function index(): void
    {
        if (!Feature::enabled('calendar')) {
            $this->redirect('dashboard');
        }

        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        if ($month < 1)  { $month = 12; $year--; }
        if ($month > 12) { $month = 1;  $year++; }

        $firstDay    = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int)date('t', $firstDay);
        $startDow    = (int)date('N', $firstDay); // 1=Mon … 7=Sun

        // Load competitions for this month
        $compModel = new CompetitionModel();
        $rawComps  = $compModel->getForMonth($year, $month);

        // Group by day
        $events = [];
        foreach ($rawComps as $c) {
            $day = (int)date('j', strtotime($c['competition_date']));
            $events[$day][] = $c;
        }

        $prevYear  = $month === 1  ? $year - 1 : $year;
        $prevMonth = $month === 1  ? 12        : $month - 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;
        $nextMonth = $month === 12 ? 1         : $month + 1;

        $polishMonths = [
            1  => 'Styczeń',   2  => 'Luty',      3  => 'Marzec',
            4  => 'Kwiecień',  5  => 'Maj',        6  => 'Czerwiec',
            7  => 'Lipiec',    8  => 'Sierpień',   9  => 'Wrzesień',
            10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień',
        ];

        $this->render('calendar/index', [
            'title'       => 'Kalendarz — ' . $polishMonths[$month] . ' ' . $year,
            'year'        => $year,
            'month'       => $month,
            'daysInMonth' => $daysInMonth,
            'startDow'    => $startDow,
            'events'      => $events,
            'monthName'   => $polishMonths[$month],
            'prevYear'    => $prevYear,
            'prevMonth'   => $prevMonth,
            'nextYear'    => $nextYear,
            'nextMonth'   => $nextMonth,
        ]);
    }
}
