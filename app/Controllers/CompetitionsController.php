<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\CompetitionModel;
use App\Models\MemberModel;
use App\Models\DisciplineModel;
use App\Models\JudgeLicenseModel;
use App\Models\LicenseModel;
use App\Models\SettingModel;

class CompetitionsController extends BaseController
{
    private CompetitionModel $competitionModel;
    private MemberModel $memberModel;
    private DisciplineModel $disciplineModel;
    private JudgeLicenseModel $judgeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->competitionModel = new CompetitionModel();
        $this->memberModel      = new MemberModel();
        $this->disciplineModel  = new DisciplineModel();
        $this->judgeModel       = new JudgeLicenseModel();
    }

    public function index(): void
    {
        $filters = [
            'q'      => trim($_GET['q'] ?? ''),
            'status' => $_GET['status'] ?? '',
            'year'   => $_GET['year'] ?? date('Y'),
        ];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->competitionModel->getAll($filters, $page);

        $this->render('competitions/index', [
            'title'   => 'Zawody',
            'result'  => $result,
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->render('competitions/form', [
            'title'       => 'Utwórz zawody',
            'competition' => null,
            'mode'        => 'create',
            'disciplines' => $this->disciplineModel->getActive(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('competitions/create');
        }

        $id = $this->competitionModel->create($data);
        Session::flash('success', 'Zawody zostały utworzone.');
        $this->redirect("competitions/{$id}");
    }

    public function show(string $id): void
    {
        $competition = $this->getCompetition((int)$id);
        $this->render('competitions/show', [
            'title'        => $competition['name'],
            'competition'  => $competition,
            'entries'      => $this->competitionModel->getEntries((int)$id),
            'groups'       => $this->competitionModel->getGroups((int)$id),
            'results'      => $this->competitionModel->getResults((int)$id),
            'events'       => $this->competitionModel->getEvents((int)$id),
            'judges'       => $this->competitionModel->getCompetitionJudges((int)$id),
            'activeJudges' => $this->judgeModel->getActiveJudges(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $competition = $this->getCompetition((int)$id);
        $this->render('competitions/form', [
            'title'       => 'Edytuj zawody',
            'competition' => $competition,
            'mode'        => 'edit',
            'disciplines' => $this->disciplineModel->getActive(),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $data   = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("competitions/{$id}/edit");
        }

        $this->competitionModel->updateCompetition((int)$id, $data);
        Session::flash('success', 'Zawody zostały zaktualizowane.');
        $this->redirect("competitions/{$id}");
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $this->competitionModel->delete((int)$id);
        Session::flash('success', 'Zawody zostały usunięte.');
        $this->redirect('competitions');
    }

    public function entries(string $id): void
    {
        $competition = $this->getCompetition((int)$id);
        $this->render('competitions/entries', [
            'title'       => 'Zgłoszenia — ' . $competition['name'],
            'competition' => $competition,
            'entries'     => $this->competitionModel->getEntries((int)$id),
            'groups'      => $this->competitionModel->getGroups((int)$id),
            'members'     => $this->memberModel->getAllActive(),
        ]);
    }

    public function addEntry(string $id): void
    {
        Csrf::verify();
        $competition = $this->getCompetition((int)$id);

        if ($competition['status'] !== 'otwarte') {
            Session::flash('error', 'Zapisy są zamknięte.');
            $this->redirect("competitions/{$id}/entries");
        }

        $memberId = (int)($_POST['member_id'] ?? 0);
        if (!$memberId) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect("competitions/{$id}/entries");
        }

        // Verify member has active license
        $member = $this->memberModel->findById($memberId);
        if (!$member || $member['status'] !== 'aktywny') {
            Session::flash('error', 'Zawodnik nie jest aktywny.');
            $this->redirect("competitions/{$id}/entries");
        }

        try {
            $this->competitionModel->addEntry([
                'competition_id' => (int)$id,
                'member_id'      => $memberId,
                'group_id'       => ($_POST['group_id'] ?? '') ?: null,
                'class'          => ($_POST['class'] ?? '') ?: null,
                'status'         => 'zgloszony',
                'registered_by'  => Auth::id(),
            ]);
            Session::flash('success', 'Zawodnik został zgłoszony.');
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || $e->getCode() == 23000) {
                Session::flash('error', 'Ten zawodnik jest już zgłoszony.');
            } else {
                throw $e;
            }
        }

        $this->redirect("competitions/{$id}/entries");
    }

    public function removeEntry(string $id, string $eid): void
    {
        Csrf::verify();
        $this->competitionModel->removeEntry((int)$eid);
        Session::flash('success', 'Zgłoszenie zostało usunięte.');
        $this->redirect("competitions/{$id}/entries");
    }

    public function results(string $id): void
    {
        $competition = $this->getCompetition((int)$id);
        $this->render('competitions/results', [
            'title'       => 'Wyniki — ' . $competition['name'],
            'competition' => $competition,
            'entries'     => $this->competitionModel->getEntries((int)$id),
            'results'     => $this->competitionModel->getResults((int)$id),
            'groups'      => $this->competitionModel->getGroups((int)$id),
        ]);
    }

    public function saveResults(string $id): void
    {
        Csrf::verify();
        $competition = $this->getCompetition((int)$id);

        $memberIds = $_POST['member_id'] ?? [];
        $scores    = $_POST['score'] ?? [];
        $places    = $_POST['place'] ?? [];
        $notes     = $_POST['notes'] ?? [];
        $groupIds  = $_POST['group_id'] ?? [];

        foreach ($memberIds as $i => $memberId) {
            if (!$memberId) continue;
            $this->competitionModel->upsertResult([
                'competition_id' => (int)$id,
                'member_id'      => (int)$memberId,
                'group_id'       => $groupIds[$i] ?: null,
                'score'          => $scores[$i] !== '' ? (float)$scores[$i] : null,
                'place'          => $places[$i] !== '' ? (int)$places[$i] : null,
                'notes'          => trim($notes[$i] ?? '') ?: null,
                'entered_by'     => Auth::id(),
            ]);
        }

        Session::flash('success', 'Wyniki zostały zapisane.');
        $this->redirect("competitions/{$id}/results");
    }

    // ── Competition Events ───────────────────────────────────────────

    public function events(string $id): void
    {
        $competition = $this->getCompetition((int)$id);
        $this->render('competitions/events', [
            'title'            => 'Konkurencje — ' . $competition['name'],
            'competition'      => $competition,
            'events'           => $this->competitionModel->getEvents((int)$id),
            'templateGroups'   => $this->disciplineModel->getAllTemplatesGrouped(),
        ]);
    }

    public function addEvent(string $id): void
    {
        Csrf::verify();
        $this->getCompetition((int)$id);

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            Session::flash('error', 'Nazwa konkurencji jest wymagana.');
            $this->redirect("competitions/{$id}/events");
        }

        $this->competitionModel->addEvent([
            'competition_id' => (int)$id,
            'name'           => $name,
            'shots_count'    => $_POST['shots_count'] !== '' ? (int)$_POST['shots_count'] : null,
            'scoring_type'   => in_array($_POST['scoring_type'] ?? '', ['decimal','integer','hit_miss'])
                                    ? $_POST['scoring_type'] : 'decimal',
            'sort_order'     => (int)($_POST['sort_order'] ?? 0),
        ]);

        Session::flash('success', 'Konkurencja dodana.');
        $this->redirect("competitions/{$id}/events");
    }

    public function deleteEvent(string $id, string $eid): void
    {
        Csrf::verify();
        $this->getCompetition((int)$id);
        $this->competitionModel->deleteEvent((int)$eid);
        Session::flash('success', 'Konkurencja usunięta.');
        $this->redirect("competitions/{$id}/events");
    }

    public function eventResults(string $id, string $eid): void
    {
        $competition = $this->getCompetition((int)$id);
        $event       = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            Session::flash('error', 'Konkurencja nie istnieje.');
            $this->redirect("competitions/{$id}/events");
        }

        $this->render('competitions/event_results', [
            'title'       => 'Wyniki: ' . $event['name'],
            'competition' => $competition,
            'event'       => $event,
            'entries'     => $this->competitionModel->getEntries((int)$id),
            'resultsMap'  => $this->competitionModel->getEventResultsMap((int)$eid),
            'members'     => $this->memberModel->getAllActive(),
        ]);
    }

    public function saveEventResults(string $id, string $eid): void
    {
        Csrf::verify();
        $this->getCompetition((int)$id);
        $event = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            $this->redirect("competitions/{$id}/events");
        }

        $memberIds   = $_POST['member_id']   ?? [];
        $scores      = $_POST['score']       ?? [];
        $scoreInners = $_POST['score_inner'] ?? [];
        $places      = $_POST['place']       ?? [];
        $notes       = $_POST['notes']       ?? [];

        foreach ($memberIds as $i => $memberId) {
            if (!$memberId) continue;
            // Skip rows with no data at all
            if ($scores[$i] === '' && $places[$i] === '' && trim($notes[$i] ?? '') === '') continue;

            $this->competitionModel->upsertEventResult([
                'competition_event_id' => (int)$eid,
                'member_id'            => (int)$memberId,
                'score'                => $scores[$i],
                'score_inner'          => $scoreInners[$i] ?? '',
                'place'                => $places[$i],
                'notes'                => trim($notes[$i] ?? '') ?: null,
                'entered_by'           => Auth::id(),
            ]);
        }

        Session::flash('success', 'Wyniki zapisane.');
        $this->redirect("competitions/{$id}/events/{$eid}/results");
    }

    public function startCard(string $id, string $eid): void
    {
        $competition = $this->getCompetition((int)$id);
        $event       = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            Session::flash('error', 'Konkurencja nie istnieje.');
            $this->redirect("competitions/{$id}/events");
        }

        $entries = $this->competitionModel->getEntries((int)$id);

        // Render without layout
        $view = new \App\Helpers\View();
        $view->setLayout('print');
        $view->render('competitions/startcard', [
            'title'       => 'Metryczka — ' . $event['name'],
            'competition' => $competition,
            'event'       => $event,
            'entries'     => $entries,
        ]);
        exit;
    }

    /**
     * Per-competitor A5 scorecards — one card per entrant, with shot-by-shot score grid.
     */
    public function memberScorecard(string $id, string $eid): void
    {
        $competition = $this->getCompetition((int)$id);
        $event       = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            Session::flash('error', 'Konkurencja nie istnieje.');
            $this->redirect("competitions/{$id}/events");
        }

        $entries    = $this->competitionModel->getEntries((int)$id);
        $resultsMap = $this->competitionModel->getEventResultsMap((int)$eid);

        $view = new \App\Helpers\View();
        $view->setLayout('print_a5');
        $view->render('competitions/member_scorecard', [
            'title'       => 'Metryczki — ' . $event['name'],
            'competition' => $competition,
            'event'       => $event,
            'entries'     => $entries,
            'resultsMap'  => $resultsMap,
        ]);
        exit;
    }

    // ── Scorecard Selector + Print (A5 landscape) ───────────────────

    /**
     * Selector: choose members and events to print scorecards for.
     * GET /competitions/:id/scorecards
     */
    public function scorecardSelector(string $id): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $competition = $this->getCompetition((int)$id);

        $entries = $this->competitionModel->getEntries((int)$id);
        $events  = $this->competitionModel->getEvents((int)$id);

        $view = new \App\Helpers\View();
        $view->setLayout('main');
        $view->render('competitions/scorecard_selector', [
            'title'       => 'Generuj metryczki — ' . $competition['name'],
            'competition' => $competition,
            'entries'     => $entries,
            'events'      => $events,
        ]);
    }

    /**
     * Print: render A5 landscape scorecard for each selected member × event.
     * GET /competitions/:id/scorecards/print?m[]=&e[]=
     */
    public function scorecardPrint(string $id): void
    {
        $competition = $this->getCompetition((int)$id);

        $memberIds = array_map('intval', (array)($_GET['m'] ?? []));
        $eventIds  = array_map('intval', (array)($_GET['e'] ?? []));

        if (empty($memberIds) || empty($eventIds)) {
            Session::flash('error', 'Wybierz zawodników i konkurencje.');
            $this->redirect("competitions/{$id}/scorecards");
        }

        // Load all entries for selected members (to get class, group, etc.)
        $allEntries = $this->competitionModel->getEntries((int)$id);
        $entriesMap = [];
        foreach ($allEntries as $entry) {
            $entriesMap[(int)$entry['member_id']] = $entry;
        }

        // Filter to requested members preserving order from entries
        $selectedEntries = array_filter($allEntries, fn($e) => in_array((int)$e['member_id'], $memberIds));

        // Load selected events
        $allEvents    = $this->competitionModel->getEvents((int)$id);
        $selectedEvts = array_filter($allEvents, fn($ev) => in_array((int)$ev['id'], $eventIds));

        // Build results maps per event
        $resultsMaps = [];
        foreach ($selectedEvts as $ev) {
            $resultsMaps[(int)$ev['id']] = $this->competitionModel->getEventResultsMap((int)$ev['id']);
        }

        // Load club name and license numbers
        $clubName   = (new SettingModel())->get('club_name', '');
        $licenseMap = (new LicenseModel())->getLicenseMapForMembers($memberIds);

        // Build cards: member × event (member outer, event inner)
        $cards = [];
        foreach ($selectedEntries as $entry) {
            foreach ($selectedEvts as $ev) {
                $cards[] = [
                    'member'         => $entry,
                    'event'          => $ev,
                    'result'         => $resultsMaps[(int)$ev['id']][(int)$entry['member_id']] ?? null,
                    'license_number' => $licenseMap[(int)$entry['member_id']] ?? '',
                ];
            }
        }

        $view = new \App\Helpers\View();
        $view->setLayout('print_scorecard');
        $view->render('competitions/scorecard_print', [
            'title'       => 'Metryczki — ' . $competition['name'],
            'competition' => $competition,
            'clubName'    => $clubName,
            'cards'       => $cards,
        ]);
        exit;
    }

    // ── Competition Judges ───────────────────────────────────────────

    public function addJudge(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->getCompetition((int)$id);

        $memberId = (int)($_POST['member_id'] ?? 0);
        $role     = $_POST['role'] ?? 'liniowy';
        if (!$memberId) {
            Session::flash('error', 'Wybierz sędziego.');
            $this->redirect("competitions/{$id}");
        }

        try {
            $this->competitionModel->addJudge([
                'competition_id' => (int)$id,
                'member_id'      => $memberId,
                'role'           => in_array($role, ['glowny','liniowy','obliczeniowy','bezpieczenstwa','protokolant']) ? $role : 'liniowy',
                'notes'          => trim($_POST['notes'] ?? '') ?: null,
            ]);
            Session::flash('success', 'Sędzia został przypisany.');
        } catch (\Throwable) {
            Session::flash('error', 'Ten sędzia jest już przypisany w tej roli.');
        }

        $this->redirect("competitions/{$id}");
    }

    public function removeJudge(string $id, string $jid): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->competitionModel->removeJudge((int)$jid);
        Session::flash('success', 'Sędzia usunięty z zawodów.');
        $this->redirect("competitions/{$id}");
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function getCompetition(int $id): array
    {
        $c = $this->competitionModel->getWithDetails($id);
        if (!$c) {
            Session::flash('error', 'Zawody nie istnieją.');
            $this->redirect('competitions');
        }
        return $c;
    }

    private function collectData(): array
    {
        return [
            'name'             => trim($_POST['name'] ?? ''),
            'discipline_id'    => $_POST['discipline_id'] ?: null,
            'competition_date' => $_POST['competition_date'] ?? '',
            'location'         => trim($_POST['location'] ?? '') ?: null,
            'description'      => trim($_POST['description'] ?? '') ?: null,
            'status'           => $_POST['status'] ?? 'planowane',
            'max_entries'      => $_POST['max_entries'] ?: null,
            'created_by'       => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['name']))             $errors[] = 'Nazwa zawodów jest wymagana.';
        if (empty($data['competition_date'])) $errors[] = 'Data zawodów jest wymagana.';
        return $errors;
    }
}
