<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Feature;
use App\Helpers\Session;
use App\Models\CalendarEventModel;
use App\Models\CompetitionModel;

class CalendarController extends BaseController
{
    private CalendarEventModel $eventModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->eventModel = new CalendarEventModel();
    }

    // ── Main calendar view ────────────────────────────────────────────

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

        // System competitions
        $compModel = new CompetitionModel();
        $rawComps  = $compModel->getForMonth($year, $month);

        // Custom events
        $rawCustom = $this->eventModel->getForMonth($year, $month);

        // Group by day — competitions
        $events = [];
        foreach ($rawComps as $c) {
            $day = (int)date('j', strtotime($c['competition_date']));
            $c['_source'] = 'competition';
            $events[$day][] = $c;
        }

        // Group by day — custom events (may span multiple days)
        foreach ($rawCustom as $e) {
            $start    = strtotime($e['event_date']);
            $end      = $e['event_date_end'] ? strtotime($e['event_date_end']) : $start;
            $monthTs  = mktime(0, 0, 0, $month, 1, $year);
            $monthEnd = mktime(0, 0, 0, $month, $daysInMonth, $year);

            // Clamp to current month
            $rangeStart = max($start, $monthTs);
            $rangeEnd   = min($end, $monthEnd);

            for ($ts = $rangeStart; $ts <= $rangeEnd; $ts = strtotime('+1 day', $ts)) {
                $d = (int)date('j', $ts);
                $e['_source'] = 'custom';
                $events[$d][] = $e;
            }
        }

        // Sort each day's events: competitions first, then custom
        foreach ($events as &$dayEvs) {
            usort($dayEvs, fn($a, $b) => strcmp($a['_source'] ?? '', $b['_source'] ?? ''));
        }
        unset($dayEvs);

        $polishMonths = [
            1 => 'Styczeń',   2 => 'Luty',      3 => 'Marzec',
            4 => 'Kwiecień',  5 => 'Maj',        6 => 'Czerwiec',
            7 => 'Lipiec',    8 => 'Sierpień',   9 => 'Wrzesień',
            10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień',
        ];

        $prevYear  = $month === 1  ? $year - 1 : $year;
        $prevMonth = $month === 1  ? 12        : $month - 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;
        $nextMonth = $month === 12 ? 1         : $month + 1;

        $role = Auth::role() ?? '';
        $canManage = in_array($role, ['admin', 'zarzad']);

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
            'canManage'   => $canManage,
        ]);
    }

    // ── Custom event CRUD ─────────────────────────────────────────────

    public function createEvent(): void
    {
        $this->requireRole(['admin', 'zarzad']);

        $prefillDate = $_GET['date'] ?? date('Y-m-d');

        $this->render('calendar/event_form', [
            'title'      => 'Dodaj wydarzenie',
            'mode'       => 'create',
            'event'      => ['event_date' => $prefillDate],
            'typeLabels' => self::typeLabels(),
            'colorOpts'  => self::colorOptions(),
        ]);
    }

    public function storeEvent(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        Csrf::verify();

        $data   = $this->collectEventData();
        $errors = $this->validateEvent($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('calendar/events/create');
        }

        $data['created_by'] = Auth::id();
        $this->eventModel->create($data);

        Session::flash('success', 'Wydarzenie zostało dodane.');
        $this->redirect('calendar?year=' . date('Y', strtotime($data['event_date'])) . '&month=' . date('n', strtotime($data['event_date'])));
    }

    public function editEvent(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);

        $event = $this->eventModel->findById((int)$id);
        if (!$event) {
            Session::flash('error', 'Wydarzenie nie istnieje.');
            $this->redirect('calendar');
        }

        $this->render('calendar/event_form', [
            'title'      => 'Edytuj wydarzenie',
            'mode'       => 'edit',
            'event'      => $event,
            'typeLabels' => self::typeLabels(),
            'colorOpts'  => self::colorOptions(),
        ]);
    }

    public function updateEvent(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        Csrf::verify();

        $event = $this->eventModel->findById((int)$id);
        if (!$event) {
            Session::flash('error', 'Wydarzenie nie istnieje.');
            $this->redirect('calendar');
        }

        $data   = $this->collectEventData();
        $errors = $this->validateEvent($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('calendar/events/' . $id . '/edit');
        }

        $this->eventModel->updateEvent((int)$id, $data);

        Session::flash('success', 'Wydarzenie zostało zaktualizowane.');
        $this->redirect('calendar?year=' . date('Y', strtotime($data['event_date'])) . '&month=' . date('n', strtotime($data['event_date'])));
    }

    public function destroyEvent(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        Csrf::verify();

        $event = $this->eventModel->findById((int)$id);
        if ($event) {
            $this->eventModel->delete((int)$id);
            Session::flash('success', 'Wydarzenie zostało usunięte.');
        }

        $this->safeRedirectBack('calendar');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function collectEventData(): array
    {
        $end = trim($_POST['event_date_end'] ?? '');
        return [
            'title'         => trim($_POST['title'] ?? ''),
            'event_date'    => $_POST['event_date'] ?? '',
            'event_date_end'=> $end !== '' ? $end : null,
            'type'          => $_POST['type'] ?? 'inne',
            'location'      => trim($_POST['location'] ?? '') ?: null,
            'description'   => trim($_POST['description'] ?? '') ?: null,
            'url'           => trim($_POST['url'] ?? '') ?: null,
            'color'         => $_POST['color'] ?? 'secondary',
            'is_public'     => isset($_POST['is_public']) ? 1 : 0,
        ];
    }

    private function validateEvent(array $data): array
    {
        $errors = [];
        if (empty($data['title']))      $errors[] = 'Tytuł jest wymagany.';
        if (empty($data['event_date'])) $errors[] = 'Data rozpoczęcia jest wymagana.';
        if ($data['event_date_end'] && $data['event_date_end'] < $data['event_date']) {
            $errors[] = 'Data zakończenia nie może być wcześniejsza niż data rozpoczęcia.';
        }
        return $errors;
    }

    public static function typeLabels(): array
    {
        return [
            'zawody_zewnetrzne' => 'Zawody zewnętrzne',
            'spotkanie'         => 'Zebranie / spotkanie',
            'szkolenie'         => 'Szkolenie / kurs',
            'wyjazd'            => 'Wyjazd',
            'inne'              => 'Inne',
        ];
    }

    public static function colorOptions(): array
    {
        return [
            'secondary' => 'Szary (domyślny)',
            'info'      => 'Niebieski (info)',
            'success'   => 'Zielony',
            'warning'   => 'Żółty',
            'danger'    => 'Czerwony',
            'dark'      => 'Ciemny',
        ];
    }
}
