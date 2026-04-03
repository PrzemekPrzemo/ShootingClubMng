<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MedicalExamModel;
use App\Models\MemberModel;

class MedicalExamsController extends BaseController
{
    private MedicalExamModel $examModel;
    private MemberModel $memberModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->examModel   = new MedicalExamModel();
        $this->memberModel = new MemberModel();
    }

    public function index(string $member_id): void
    {
        $member = $this->getMember((int)$member_id);
        $this->render('members/exams/index', [
            'title'  => 'Badania sportowe — ' . $member['last_name'] . ' ' . $member['first_name'],
            'member' => $member,
            'exams'  => $this->examModel->getForMember((int)$member_id),
        ]);
    }

    public function create(string $member_id): void
    {
        $member = $this->getMember((int)$member_id);
        $this->checkCompetitive($member);

        $this->render('members/exams/form', [
            'title'  => 'Dodaj badanie',
            'member' => $member,
            'exam'   => null,
            'mode'   => 'create',
        ]);
    }

    public function store(string $member_id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $member = $this->getMember((int)$member_id);
        $this->checkCompetitive($member);

        $data   = $this->collectData((int)$member_id);
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("members/{$member_id}/exams/create");
        }

        $this->examModel->create($data);
        Session::flash('success', 'Badanie zostało dodane.');
        $this->redirect("members/{$member_id}/exams");
    }

    public function edit(string $member_id, string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $member = $this->getMember((int)$member_id);
        $exam   = $this->examModel->findById((int)$id);

        $this->render('members/exams/form', [
            'title'  => 'Edytuj badanie',
            'member' => $member,
            'exam'   => $exam,
            'mode'   => 'edit',
        ]);
    }

    public function update(string $member_id, string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $data   = $this->collectData((int)$member_id);
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("members/{$member_id}/exams/{$id}/edit");
        }

        $this->examModel->updateExam((int)$id, $data);
        Session::flash('success', 'Badanie zostało zaktualizowane.');
        $this->redirect("members/{$member_id}/exams");
    }

    public function destroy(string $member_id, string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $this->examModel->delete((int)$id);
        Session::flash('success', 'Badanie zostało usunięte.');
        $this->redirect("members/{$member_id}/exams");
    }

    // ----------------------------------------------------------------

    private function getMember(int $id): array
    {
        $member = $this->memberModel->findById($id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }
        return $member;
    }

    private function checkCompetitive(array $member): void
    {
        if ($member['member_type'] !== 'wyczynowy') {
            Session::flash('warning', 'Badania sportowe dotyczą tylko zawodników wyczynowych.');
            $this->redirect('members/' . $member['id']);
        }
    }

    private function collectData(int $memberId): array
    {
        return [
            'member_id'   => $memberId,
            'exam_date'   => $_POST['exam_date'] ?? '',
            'valid_until' => $_POST['valid_until'] ?? '',
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
            'created_by'  => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['exam_date']))   $errors[] = 'Data badania jest wymagana.';
        if (empty($data['valid_until'])) $errors[] = 'Data ważności jest wymagana.';
        if ($data['exam_date'] && $data['valid_until'] && $data['valid_until'] < $data['exam_date']) {
            $errors[] = 'Data ważności musi być późniejsza niż data badania.';
        }
        return $errors;
    }
}
