<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MedicalExamModel;
use App\Models\MedicalExamTypeModel;
use App\Models\MemberModel;

class MedicalExamsController extends BaseController
{
    private MedicalExamModel $examModel;
    private MedicalExamTypeModel $examTypeModel;
    private MemberModel $memberModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->examModel      = new MedicalExamModel();
        $this->examTypeModel  = new MedicalExamTypeModel();
        $this->memberModel    = new MemberModel();
    }

    public function index(string $member_id): void
    {
        $member = $this->getMember((int)$member_id);
        $this->render('members/exams/index', [
            'title'      => 'Badania lekarskie — ' . $member['last_name'] . ' ' . $member['first_name'],
            'member'     => $member,
            'exams'      => $this->examModel->getForMember((int)$member_id),
            'examMatrix' => $this->examModel->getExamMatrix((int)$member_id),
        ]);
    }

    public function create(string $member_id): void
    {
        $member = $this->getMember((int)$member_id);
        $this->render('members/exams/form', [
            'title'     => 'Dodaj badanie',
            'member'    => $member,
            'exam'      => null,
            'mode'      => 'create',
            'examTypes' => $this->examTypeModel->getActive(),
        ]);
    }

    public function store(string $member_id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $member = $this->getMember((int)$member_id);

        $data   = $this->collectData((int)$member_id);
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("members/{$member_id}/exams/create");
        }

        // Handle file upload
        $filePath = $this->handleFileUpload($member_id);
        if ($filePath !== null) {
            $data['file_path'] = $filePath;
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
            'title'     => 'Edytuj badanie',
            'member'    => $member,
            'exam'      => $exam,
            'mode'      => 'edit',
            'examTypes' => $this->examTypeModel->getActive(),
        ]);
    }

    public function update(string $member_id, string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $exam   = $this->examModel->findById((int)$id);
        $data   = $this->collectData((int)$member_id);
        $errors = $this->validate($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("members/{$member_id}/exams/{$id}/edit");
        }

        // Handle file upload (keep old if no new file)
        $filePath = $this->handleFileUpload($member_id);
        if ($filePath !== null) {
            $data['file_path'] = $filePath;
            // Remove old file if present
            if (!empty($exam['file_path']) && file_exists(ROOT_PATH . '/storage/medical/' . $exam['file_path'])) {
                @unlink(ROOT_PATH . '/storage/medical/' . $exam['file_path']);
            }
        }

        $this->examModel->updateExam((int)$id, $data);
        Session::flash('success', 'Badanie zostało zaktualizowane.');
        $this->redirect("members/{$member_id}/exams");
    }

    public function destroy(string $member_id, string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);
        $exam = $this->examModel->findById((int)$id);
        if ($exam && !empty($exam['file_path'])) {
            @unlink(ROOT_PATH . '/storage/medical/' . $exam['file_path']);
        }
        $this->examModel->delete((int)$id);
        Session::flash('success', 'Badanie zostało usunięte.');
        $this->redirect("members/{$member_id}/exams");
    }

    public function downloadFile(string $member_id, string $id): void
    {
        $exam = $this->examModel->findById((int)$id);
        if (!$exam || empty($exam['file_path'])) {
            Session::flash('error', 'Brak pliku do pobrania.');
            $this->redirect("members/{$member_id}/exams");
        }

        $fullPath = ROOT_PATH . '/storage/medical/' . $exam['file_path'];
        if (!file_exists($fullPath)) {
            Session::flash('error', 'Plik nie istnieje na serwerze.');
            $this->redirect("members/{$member_id}/exams");
        }

        $ext     = strtolower(pathinfo($exam['file_path'], PATHINFO_EXTENSION));
        $mimeMap = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $mime    = $mimeMap[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="badanie_' . $id . '.' . $ext . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
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

    private function collectData(int $memberId): array
    {
        return [
            'member_id'    => $memberId,
            'exam_type_id' => $_POST['exam_type_id'] ?: null,
            'exam_date'    => $_POST['exam_date'] ?? '',
            'valid_until'  => $_POST['valid_until'] ?? '',
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
            'created_by'   => Auth::id(),
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

    /**
     * Handles file upload from $_FILES['file'].
     * Returns the stored filename (relative to storage/medical/) or null if no file.
     */
    private function handleFileUpload(string $memberId): ?string
    {
        if (empty($_FILES['file']['name'])) {
            return null;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            Session::flash('error', 'Dozwolone formaty: PDF, JPG, PNG.');
            return null;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            Session::flash('error', 'Plik nie może przekraczać 5 MB.');
            return null;
        }

        $storageDir = ROOT_PATH . '/storage/medical';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'member' . $memberId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        $dest     = $storageDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Nie udało się zapisać pliku.');
            return null;
        }

        return $filename;
    }
}
