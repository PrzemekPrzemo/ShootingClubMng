<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
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
        $entries     = $this->competitionModel->getEntries((int)$id);

        // Pre-compute fee per entry (sum of selected events × weapon type - discount)
        $entryFees = [];
        foreach ($entries as $e) {
            $entryFees[$e['id']] = $this->competitionModel->calcEntryFee((int)$e['id']);
        }

        $this->render('competitions/entries', [
            'title'       => 'Zgłoszenia — ' . $competition['name'],
            'competition' => $competition,
            'entries'     => $entries,
            'entryFees'   => $entryFees,
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
        $this->requireRole(['admin', 'zarzad', 'instruktor', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $locked      = in_array($competition['status'], ['zamkniete', 'zakonczone']);
        $canEdit     = !$locked || Auth::role() === 'admin';

        $this->render('competitions/results', [
            'title'             => 'Wyniki — ' . $competition['name'],
            'competition'       => $competition,
            'entriesWithEvents' => $this->competitionModel->getEntriesWithEventResults((int)$id),
            'locked'            => $locked,
            'canEdit'           => $canEdit,
        ]);
    }

    public function saveResults(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor', 'sędzia']);
        $competition = $this->getCompetition((int)$id);

        $locked = in_array($competition['status'], ['zamkniete', 'zakonczone']);
        if ($locked && Auth::role() !== 'admin') {
            Session::flash('error', 'Zawody są zamknięte. Tylko administrator może zmieniać wyniki.');
            $this->redirect("competitions/{$id}/results");
        }

        $memberIds  = (array)($_POST['member_ids'] ?? []);
        $rawResults = (array)($_POST['results'] ?? []);

        foreach ($rawResults as $entryId => $events) {
            $memberId = (int)($memberIds[$entryId] ?? 0);
            if (!$memberId) continue;
            foreach ((array)$events as $eventId => $fields) {
                $score      = trim($fields['score'] ?? '');
                $scoreInner = trim($fields['score_inner'] ?? '');
                $place      = trim($fields['place'] ?? '');
                $notes      = trim($fields['notes'] ?? '');
                if ($score === '' && $place === '' && $notes === '') continue;
                $this->competitionModel->upsertEventResult([
                    'competition_event_id' => (int)$eventId,
                    'member_id'            => $memberId,
                    'score'                => $score !== '' ? (float)$score : null,
                    'score_inner'          => $scoreInner !== '' ? (int)$scoreInner : null,
                    'place'                => $place !== '' ? (int)$place : null,
                    'notes'                => $notes ?: null,
                    'entered_by'           => Auth::id(),
                ]);
            }
        }

        Session::flash('success', 'Wyniki zostały zapisane.');
        $this->redirect("competitions/{$id}/results");
    }

    public function unlockResults(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin']);
        $db = Database::getInstance();
        $db->prepare("UPDATE competitions SET status = 'otwarte' WHERE id = ? AND status IN ('zamkniete','zakonczone')")
           ->execute([(int)$id]);
        Session::flash('success', 'Zawody odblokowane — wyniki można edytować.');
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

        $feeOwn  = trim($_POST['fee_own_weapon']  ?? '');
        $feeClub = trim($_POST['fee_club_weapon'] ?? '');
        $this->competitionModel->addEvent([
            'competition_id'  => (int)$id,
            'name'            => $name,
            'shots_count'     => ($_POST['shots_count'] ?? '') !== '' ? (int)$_POST['shots_count'] : null,
            'scoring_type'    => in_array($_POST['scoring_type'] ?? '', ['decimal','integer','hit_miss'])
                                    ? $_POST['scoring_type'] : 'decimal',
            'sort_order'      => (int)($_POST['sort_order'] ?? 0),
            'fee_own_weapon'  => $feeOwn  !== '' ? (float)$feeOwn  : null,
            'fee_club_weapon' => $feeClub !== '' ? (float)$feeClub : null,
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
            $score = $scores[$i] ?? '';
            $place = $places[$i] ?? '';
            $note  = trim($notes[$i] ?? '');
            // Skip rows with no data at all
            if ($score === '' && $place === '' && $note === '') continue;

            $this->competitionModel->upsertEventResult([
                'competition_event_id' => (int)$eid,
                'member_id'            => (int)$memberId,
                'score'                => $score,
                'score_inner'          => $scoreInners[$i] ?? '',
                'place'                => $place,
                'notes'                => $note ?: null,
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

        $this->render('competitions/scorecard_selector', [
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
        $clubName   = current_club_name('');
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

    // ── Per-competitor event selection ───────────────────────────────

    public function entryEvents(string $id, string $eid): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $competition = $this->getCompetition((int)$id);
        $events      = $this->competitionModel->getEvents((int)$id);

        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ce.*, m.first_name, m.last_name, m.member_number
            FROM competition_entries ce
            JOIN members m ON m.id = ce.member_id
            WHERE ce.id = ? AND ce.competition_id = ?
        ");
        $stmt->execute([(int)$eid, (int)$id]);
        $entry = $stmt->fetch();
        if (!$entry) {
            Session::flash('error', 'Nie znaleziono zgłoszenia.');
            $this->redirect("competitions/{$id}/entries");
        }

        $selectedEventWeapons = $this->competitionModel->getEntryEventIds((int)$eid);

        $this->render('competitions/entry_events', [
            'title'               => 'Konkurencje zawodnika',
            'competition'         => $competition,
            'entry'               => $entry,
            'events'              => $events,
            'selectedEventWeapons' => $selectedEventWeapons,
        ]);
    }

    public function saveEntryEvents(string $id, string $eid): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);

        $entryId    = (int)$eid;
        $eventIds   = array_map('intval', (array)($_POST['event_ids'] ?? []));
        $rawWeapons = (array)($_POST['event_weapon'] ?? []);

        // Build [event_id => weapon_type] map for selected events only
        $eventWeapons = [];
        foreach ($eventIds as $evId) {
            $wt = $rawWeapons[$evId] ?? 'własna';
            $eventWeapons[$evId] = in_array($wt, ['własna', 'klubowa']) ? $wt : 'własna';
        }

        $this->competitionModel->setEntryEvents($entryId, $eventWeapons);

        Session::flash('success', 'Wybór konkurencji zapisany.');
        $this->redirect("competitions/{$id}/entries");
    }

    // ── Portal entry approval ────────────────────────────────────────

    public function approveEntry(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->competitionModel->changeEntryStatus((int)$id, 'potwierdzony');
        Session::flash('success', 'Zgłoszenie potwierdzone.');
        $this->safeRedirectBack('competitions');
    }

    public function rejectEntry(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->competitionModel->changeEntryStatus((int)$id, 'wycofany');
        Session::flash('success', 'Zgłoszenie odrzucone.');
        $this->safeRedirectBack('competitions');
    }

    public function toggleStartFee(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->competitionModel->toggleStartFee((int)$id);
        $this->safeRedirectBack('competitions');
    }

    /**
     * Confirms payment for an entry (called from scorecard payment dialog or entries list).
     * Sets start_fee_paid = 1 AND creates a record in payments table.
     * POST /competitions/entries/:eid/confirm-payment
     */
    public function confirmPayment(string $eid): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);

        $entryId = (int)$eid;
        $db      = Database::getInstance();

        // Load entry and member info
        $stmt = $db->prepare("
            SELECT ce.*, c.name AS competition_name,
                   m.first_name, m.last_name
            FROM competition_entries ce
            JOIN competitions c ON c.id = ce.competition_id
            JOIN members m ON m.id = ce.member_id
            WHERE ce.id = ?
        ");
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch();

        if (!$entry) {
            $this->json(['ok' => false, 'error' => 'Nie znaleziono zgłoszenia.'], 404);
        }

        // Mark as paid
        $db->prepare("UPDATE competition_entries SET start_fee_paid = 1 WHERE id = ?")
           ->execute([$entryId]);

        // Create payment record using per-event fee calculation
        $amount = $this->competitionModel->calcEntryFee($entryId);

        if ($amount > 0) {
            // Find or auto-create payment type "Opłata startowa"
            $ptStmt = $db->prepare("SELECT id FROM payment_types WHERE name = 'Opłata startowa' LIMIT 1");
            $ptStmt->execute();
            $pt = $ptStmt->fetch();

            if (!$pt) {
                $db->prepare("INSERT INTO payment_types (name, amount, is_active) VALUES ('Opłata startowa', 0, 1)")
                   ->execute();
                $ptId = (int)$db->lastInsertId();
            } else {
                $ptId = (int)$pt['id'];
            }

            try {
                $db->prepare("
                    INSERT INTO payments
                        (member_id, payment_type_id, amount, payment_date, period_year, reference, notes, registered_by)
                    VALUES (?, ?, ?, CURDATE(), YEAR(CURDATE()), ?, ?, ?)
                ")->execute([
                    $entry['member_id'],
                    $ptId,
                    $amount,
                    'ZAWODY-' . $entry['competition_id'],
                    'Opłata startowa: ' . $entry['competition_name'],
                    Auth::id(),
                ]);
            } catch (\PDOException) {
                // non-critical, payment still marked as paid
            }
        }

        $this->json(['ok' => true, 'amount' => $amount]);
    }

    /**
     * Set individual discount for an entry.
     * POST /competitions/entries/:eid/discount
     */
    public function setDiscount(string $eid): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $discount = trim($_POST['discount'] ?? '');
        $val      = $discount !== '' ? max(0, (float)$discount) : null;
        try {
            \App\Helpers\Database::getInstance()
                ->prepare("UPDATE competition_entries SET discount = ? WHERE id = ?")
                ->execute([$val, (int)$eid]);
        } catch (\PDOException) {}
        $this->safeRedirectBack('competitions');
    }

    // ── Rankings + Protocol ──────────────────────────────────────────

    public function rankings(string $id): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $rankings    = $this->competitionModel->calcRankings((int)$id);

        $this->render('competitions/rankings', [
            'title'       => 'Rankingi — ' . $competition['name'],
            'competition' => $competition,
            'rankings'    => $rankings,
        ]);
    }

    public function protocol(string $id): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $rankings    = $this->competitionModel->calcRankings((int)$id);
        $judges      = $this->competitionModel->getCompetitionJudges((int)$id);
        $clubName    = current_club_name('');

        $view = new \App\Helpers\View();
        $view->setLayout('print');
        $view->render('competitions/protocol', [
            'title'       => 'Protokół — ' . $competition['name'],
            'competition' => $competition,
            'rankings'    => $rankings,
            'judges'      => $judges,
            'clubName'    => $clubName,
        ]);
        exit;
    }

    public function protocolPdf(string $id): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $rankings    = $this->competitionModel->calcRankings((int)$id);
        $judges      = $this->competitionModel->getCompetitionJudges((int)$id);
        $clubName    = current_club_name('');

        $html = $this->renderToString('pdf/competition_protocol', [
            'competition' => $competition,
            'rankings'    => $rankings,
            'judges'      => $judges,
            'clubName'    => $clubName,
        ]);

        $safe     = preg_replace('/[^a-zA-Z0-9_-]/', '_', $competition['name']);
        $filename = 'protokol_' . $safe . '_' . date('Ymd') . '.pdf';
        \App\Helpers\PdfHelper::send($html, $filename, 'A4');
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
            'is_public'        => !empty($_POST['is_public']) ? 1 : 0,
            'created_by'       => Auth::id(),
        ];
    }

    // -------------------------------------------------------------------------
    // Panel sędziego — serie/strzały z metryczki
    // -------------------------------------------------------------------------

    /**
     * Lista uczestników z informacją ile serii wpisano.
     * GET /competitions/:id/events/:eid/series
     */
    public function seriesIndex(string $id, string $eid): void
    {
        $this->requireRole(['admin', 'zarzad', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $event       = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            Session::flash('error', 'Konkurencja nie istnieje.');
            $this->redirect("competitions/{$id}/events");
        }

        $shots      = (int)($event['shots_count'] ?? 0);
        $isHM       = ($event['scoring_type'] ?? 'decimal') === 'hit_miss';
        $serieSize  = $isHM ? 5 : 10;
        $serieCount = $shots > 0 ? (int)ceil($shots / $serieSize) : 6;

        $entries     = $this->competitionModel->getEntries((int)$id);
        $resultsMap  = $this->competitionModel->getEventResultsMap((int)$eid);
        $seriesStatus = $this->competitionModel->getSeriesStatusForEvent((int)$eid);

        $this->render('competitions/series_index', [
            'title'        => 'Serie: ' . $event['name'],
            'competition'  => $competition,
            'event'        => $event,
            'entries'      => $entries,
            'resultsMap'   => $resultsMap,
            'seriesStatus' => $seriesStatus,
            'serieCount'   => $serieCount,
        ]);
    }

    /**
     * Formularz wpisywania serii dla jednego zawodnika.
     * GET /competitions/:id/events/:eid/series/:mid
     */
    public function seriesEntry(string $id, string $eid, string $mid): void
    {
        $this->requireRole(['admin', 'zarzad', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $event       = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            Session::flash('error', 'Konkurencja nie istnieje.');
            $this->redirect("competitions/{$id}/events");
        }

        $member = $this->memberModel->findById((int)$mid);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect("competitions/{$id}/events/{$eid}/series");
        }

        $shots      = (int)($event['shots_count'] ?? 0);
        $type       = $event['scoring_type'] ?? 'decimal';
        $isHM       = $type === 'hit_miss';
        $serieSize  = $isHM ? 5 : 10;
        $serieCount = $shots > 0 ? (int)ceil($shots / $serieSize) : 6;
        if ($shots === 0) $shots = $serieCount * $serieSize;

        $seriesMap   = $this->competitionModel->getSeriesForMember((int)$eid, (int)$mid);
        $resultsMap  = $this->competitionModel->getEventResultsMap((int)$eid);
        $officialResult = $resultsMap[(int)$mid] ?? null;

        $this->render('competitions/series_entry', [
            'title'          => 'Serie: ' . $event['name'] . ' — ' . $member['first_name'] . ' ' . $member['last_name'],
            'competition'    => $competition,
            'event'          => $event,
            'member'         => $member,
            'seriesMap'      => $seriesMap,
            'officialResult' => $officialResult,
            'serieCount'     => $serieCount,
            'serieSize'      => $serieSize,
            'isHM'           => $isHM,
            'type'           => $type,
        ]);
    }

    /**
     * Zapis serii dla jednego zawodnika.
     * POST /competitions/:id/events/:eid/series/:mid
     */
    public function saveSeriesEntry(string $id, string $eid, string $mid): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'sędzia']);
        $competition = $this->getCompetition((int)$id);
        $event       = $this->competitionModel->getEvent((int)$eid);
        if (!$event || $event['competition_id'] != (int)$id) {
            $this->redirect("competitions/{$id}/events");
        }

        $member = $this->memberModel->findById((int)$mid);
        if (!$member) {
            $this->redirect("competitions/{$id}/events/{$eid}/series");
        }

        $shots      = (int)($event['shots_count'] ?? 0);
        $isHM       = ($event['scoring_type'] ?? 'decimal') === 'hit_miss';
        $serieSize  = $isHM ? 5 : 10;
        $serieCount = $shots > 0 ? (int)ceil($shots / $serieSize) : 6;

        $postShots  = (array)($_POST['shots']        ?? []);
        $postTotals = (array)($_POST['series_total'] ?? []);
        $postX      = (array)($_POST['x_count']      ?? []);

        $totalScore = 0.0;
        $totalX     = 0;

        for ($s = 1; $s <= $serieCount; $s++) {
            $rawShots  = (array)($postShots[$s] ?? []);
            $rawTotal  = trim($postTotals[$s] ?? '');
            $rawX      = (int)($postX[$s] ?? 0);

            // Filter shots to numbers only
            $shotValues = [];
            foreach ($rawShots as $v) {
                $v = trim($v);
                if ($v !== '') {
                    $shotValues[] = ($isHM ? (int)$v : (float)$v);
                } else {
                    $shotValues[] = null;
                }
            }

            $seriesTotal = $rawTotal !== '' ? (float)$rawTotal : 0.0;

            $this->competitionModel->upsertSeries([
                'competition_event_id' => (int)$eid,
                'member_id'            => (int)$mid,
                'series_number'        => $s,
                'shots'                => json_encode($shotValues),
                'series_total'         => $seriesTotal,
                'x_count'              => max(0, $rawX),
                'entered_by'           => Auth::id(),
            ]);

            $totalScore += $seriesTotal;
            $totalX     += max(0, $rawX);
        }

        // Aktualizuj oficjalny wynik jeśli checkbox zaznaczony
        if (!empty($_POST['update_official'])) {
            $this->competitionModel->upsertEventResult([
                'competition_event_id' => (int)$eid,
                'member_id'            => (int)$mid,
                'score'                => round($totalScore, 2),
                'score_inner'          => $totalX,
                'place'                => '',
                'notes'                => null,
                'entered_by'           => Auth::id(),
            ]);
        }

        Session::flash('success', 'Serie zostały zapisane dla ' . $member['first_name'] . ' ' . $member['last_name'] . '.');
        $this->redirect("competitions/{$id}/events/{$eid}/series");
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['name']))             $errors[] = 'Nazwa zawodów jest wymagana.';
        if (empty($data['competition_date'])) $errors[] = 'Data zawodów jest wymagana.';
        return $errors;
    }

    // ── Cross-club: wyszukiwanie zawodnika z innego klubu ────────────────

    /**
     * GET /competitions/:id/entries/search-external?q=PESEL_OR_LICENSE
     *
     * Wyszukuje zawodnika po PESEL lub numerze licencji w CAŁEJ bazie
     * (cross-club). Zwraca JSON. Nie ujawnia PESEL w odpowiedzi.
     * Wymaga roli admin/zarzad w klubie organizującym.
     */
    public function searchExternalMember(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);

        $competition = $this->getCompetition((int)$id);
        $q = trim($_GET['q'] ?? '');

        if (strlen($q) < 3) {
            $this->json(['results' => [], 'message' => 'Wpisz min. 3 znaki.']);
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT DISTINCT m.id, m.first_name, m.last_name, m.club_id,
                    c.name AS club_name, c.short_name AS club_short,
                    l.license_number
             FROM members m
             JOIN clubs c ON c.id = m.club_id
             LEFT JOIN licenses l ON l.member_id = m.id AND l.status = 'aktywna'
             WHERE m.status = 'aktywny'
               AND (m.pesel = ? OR l.license_number = ?)
             LIMIT 10"
        );
        $stmt->execute([$q, $q]);
        $rows = $stmt->fetchAll();

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id'             => (int)$r['id'],
                'name'           => $r['first_name'] . ' ' . $r['last_name'],
                'club_name'      => $r['club_name'],
                'club_short'     => $r['club_short'],
                'license_number' => $r['license_number'] ?? '—',
                'is_external'    => (int)$r['club_id'] !== (int)$competition['club_id'],
            ];
        }

        $this->json(['results' => $results]);
    }

    /**
     * POST /competitions/:id/entries/add-external
     *
     * Dodaje zawodnika spoza klubu do zawodów (cross-club entry).
     */
    public function addExternalEntry(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $competition = $this->getCompetition((int)$id);

        if ($competition['status'] !== 'otwarte') {
            Session::flash('error', 'Zapisy są zamknięte.');
            $this->redirect("competitions/{$id}/entries");
        }

        $memberId = (int)($_POST['member_id'] ?? 0);
        if (!$memberId) {
            Session::flash('error', 'Nie wybrano zawodnika.');
            $this->redirect("competitions/{$id}/entries");
        }

        // Sprawdź że zawodnik istnieje (bez filtra club_id)
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();

        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje lub jest nieaktywny.');
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
            Session::flash('success', "Zawodnik zewnętrzny ({$member['first_name']} {$member['last_name']}) został zgłoszony.");
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || $e->getCode() == 23000) {
                Session::flash('error', 'Ten zawodnik jest już zgłoszony.');
            } else {
                throw $e;
            }
        }

        $this->redirect("competitions/{$id}/entries");
    }
}
