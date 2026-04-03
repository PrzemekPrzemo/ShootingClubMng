<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\CompetitionModel;
use App\Models\MemberModel;
use App\Models\DisciplineModel;

class CompetitionsController extends BaseController
{
    private CompetitionModel $competitionModel;
    private MemberModel $memberModel;
    private DisciplineModel $disciplineModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->competitionModel = new CompetitionModel();
        $this->memberModel      = new MemberModel();
        $this->disciplineModel  = new DisciplineModel();
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
            'title'       => $competition['name'],
            'competition' => $competition,
            'entries'     => $this->competitionModel->getEntries((int)$id),
            'groups'      => $this->competitionModel->getGroups((int)$id),
            'results'     => $this->competitionModel->getResults((int)$id),
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
                'group_id'       => $_POST['group_id'] ?: null,
                'class'          => $_POST['class'] ?: null,
                'status'         => 'zgloszony',
                'registered_by'  => Auth::id(),
            ]);
            Session::flash('success', 'Zawodnik został zgłoszony.');
        } catch (\Throwable) {
            Session::flash('error', 'Ten zawodnik jest już zgłoszony.');
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

    // ----------------------------------------------------------------

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
