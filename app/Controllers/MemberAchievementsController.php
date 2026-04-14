<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberAchievementModel;
use App\Models\MemberModel;

class MemberAchievementsController extends BaseController
{
    private MemberAchievementModel $achievementModel;
    private MemberModel $memberModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->achievementModel = new MemberAchievementModel();
        $this->memberModel      = new MemberModel();
    }

    /** GET /members/:member_id/achievements/create */
    public function create(string $member_id): void
    {
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $member = $this->getMember((int)$member_id);

        $this->render('members/achievements/form', [
            'title'  => 'Dodaj osiągnięcie — ' . $member['last_name'] . ' ' . $member['first_name'],
            'member' => $member,
            'types'  => MemberAchievementModel::TYPES,
            'places' => MemberAchievementModel::PLACES,
        ]);
    }

    /** POST /members/:member_id/achievements */
    public function store(string $member_id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);

        $member  = $this->getMember((int)$member_id);
        $clubId  = ClubContext::current() ?? (int)($member['club_id'] ?? 0);
        $authUser = Auth::user();

        $type  = trim($_POST['achievement_type'] ?? '');
        $place = $_POST['place'] !== '' ? (int)$_POST['place'] : null;
        $year  = (int)($_POST['year'] ?? date('Y'));
        $name  = trim($_POST['competition_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!array_key_exists($type, MemberAchievementModel::TYPES)) {
            Session::flash('error', 'Wybierz prawidłowy typ osiągnięcia.');
            $this->redirect("members/{$member_id}/achievements/create");
        }

        if ($year < 1900 || $year > (int)date('Y')) {
            Session::flash('error', 'Podaj prawidłowy rok.');
            $this->redirect("members/{$member_id}/achievements/create");
        }

        $this->achievementModel->create([
            'member_id'        => (int)$member_id,
            'club_id'          => $clubId,
            'achievement_type' => $type,
            'place'            => $place,
            'year'             => $year,
            'competition_name' => $name ?: null,
            'notes'            => $notes ?: null,
            'created_by'       => $authUser['id'] ?? null,
        ]);

        $this->autoRecalcFee((int)$member_id, $clubId);

        Session::flash('success', 'Osiągnięcie zostało dodane. Składka zaktualizowana.');
        $this->redirect("members/{$member_id}");
    }

    /** POST /members/:member_id/achievements/:id/delete */
    public function destroy(string $member_id, string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $achievement = $this->achievementModel->getById((int)$id);
        if (!$achievement || (int)$achievement['member_id'] !== (int)$member_id) {
            Session::flash('error', 'Nie znaleziono osiągnięcia.');
            $this->redirect("members/{$member_id}");
        }

        $this->achievementModel->delete((int)$id);
        $this->autoRecalcFee((int)$member_id, ClubContext::current() ?? 0);

        Session::flash('success', 'Osiągnięcie zostało usunięte. Składka zaktualizowana.');
        $this->redirect("members/{$member_id}");
    }

    private function autoRecalcFee(int $memberId, int $clubId): void
    {
        if ($clubId <= 0) return;
        try {
            (new \App\Models\ClubFeeConfigModel())->recalculateOne($memberId, $clubId, (int)date('Y'));
        } catch (\Throwable) {
            // Fee tables may not exist on this deployment — ignore
        }
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function getMember(int $id): array
    {
        $member = $this->memberModel->getWithDetails($id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }
        return $member;
    }
}
