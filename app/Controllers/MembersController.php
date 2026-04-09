<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\MemberModel;
use App\Models\SettingModel;
use App\Models\AgeCategoryModel;
use App\Models\DisciplineModel;
use App\Models\MemberClassModel;
use App\Models\MedicalExamModel;
use App\Models\UserModel;
use App\Models\DisciplineClassModel;
use App\Models\MemberTypeModel;

class MembersController extends BaseController
{
    private MemberModel $memberModel;
    private AgeCategoryModel $categoryModel;
    private DisciplineModel $disciplineModel;
    private MemberClassModel $memberClassModel;
    private MedicalExamModel $examModel;
    private UserModel $userModel;
    private ActivityLogModel $activityLog;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->memberModel      = new MemberModel();
        $this->categoryModel    = new AgeCategoryModel();
        $this->disciplineModel  = new DisciplineModel();
        $this->memberClassModel = new MemberClassModel();
        $this->examModel        = new MedicalExamModel();
        $this->userModel        = new UserModel();
        $this->activityLog      = new ActivityLogModel();
    }

    // ----------------------------------------------------------------
    // Import CSV
    // ----------------------------------------------------------------

    public function importForm(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $this->render('members/import', ['title' => 'Import zawodników z CSV']);
    }

    public function importTemplate(): void
    {
        $this->requireRole(['admin', 'zarzad']);
        $bom = "\xEF\xBB\xBF";
        $header = ['last_name','first_name','pesel','birth_date','gender','email','phone',
                   'member_type','join_date','status','address_street','address_city',
                   'address_postal','notes'];
        $example = ['Kowalski','Jan','85010112345','1985-01-01','M','jan@przyklad.pl','500123456',
                    'wyczynowy','2020-01-15','aktywny','ul. Sportowa 1','Warszawa','00-001',''];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="szablon_import_zawodnikow.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, $bom);
        fputcsv($out, $header, ';');
        fputcsv($out, $example, ';');
        fclose($out);
        exit;
    }

    public function importProcess(): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $action  = $_POST['action'] ?? 'preview';
        $hasHdr  = !empty($_POST['has_header']);
        $defType = in_array($_POST['default_type'] ?? '', ['rekreacyjny','wyczynowy'])
                    ? $_POST['default_type'] : 'rekreacyjny';

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Błąd przesyłania pliku.');
            $this->redirect('members/import');
        }

        $rawDelim = $_POST['delimiter'] ?? 'auto';
        $rows     = $this->parseCsv($file['tmp_name'], $rawDelim, $hasHdr);

        if (empty($rows)) {
            Session::flash('error', 'Plik CSV jest pusty lub nie można go odczytać.');
            $this->redirect('members/import');
        }

        $preview      = [];
        $imported     = 0;
        $skipped      = 0;
        $importResult = null;

        foreach ($rows as $row) {
            $entry = $this->mapCsvRow($row, $defType);
            $errors = [];
            if (empty($entry['first_name'])) $errors[] = 'brak imienia';
            if (empty($entry['last_name']))  $errors[] = 'brak nazwiska';
            $entry['_error'] = $errors ? implode(', ', $errors) : '';

            if ($action === 'import' && !$entry['_error']) {
                try {
                    $newId = $this->memberModel->createMember([
                        'first_name'     => $entry['first_name'],
                        'last_name'      => $entry['last_name'],
                        'pesel'          => $entry['pesel'] ?: null,
                        'birth_date'     => $entry['birth_date'] ?: null,
                        'gender'         => $entry['gender'] ?: null,
                        'email'          => $entry['email'] ?: null,
                        'phone'          => $entry['phone'] ?: null,
                        'member_type'    => $entry['member_type'],
                        'join_date'      => $entry['join_date'] ?: date('Y-m-d'),
                        'status'         => in_array($entry['status'] ?? '', ['aktywny','zawieszony','wykreslony'])
                                            ? $entry['status'] : 'aktywny',
                        'address_street' => $entry['address_street'] ?: null,
                        'address_city'   => $entry['address_city'] ?: null,
                        'address_postal' => $entry['address_postal'] ?: null,
                        'notes'          => $entry['notes'] ?: null,
                        'created_by'     => Auth::id(),
                    ]);
                    $imported++;
                    $entry['_imported'] = true;
                    $newMember = $this->memberModel->findById($newId);
                    $entry['member_number'] = $newMember['member_number'] ?? '';
                } catch (\Throwable $e) {
                    $entry['_error'] = 'Błąd DB: ' . $e->getMessage();
                    $skipped++;
                }
            } elseif ($entry['_error']) {
                $skipped++;
            }

            $preview[] = $entry;
        }

        if ($action === 'import') {
            $importResult = ['imported' => $imported, 'skipped' => $skipped];
            if ($imported > 0) {
                Session::flash('success', "Zaimportowano {$imported} zawodników."
                    . ($skipped > 0 ? " Pominięto: {$skipped}." : ''));
            }
        }

        $this->render('members/import', [
            'title'        => 'Import zawodników z CSV',
            'preview'      => $preview,
            'importResult' => $importResult,
        ]);
    }

    private function parseCsv(string $filePath, string $delimiterHint, bool $hasHeader): array
    {
        // Detect encoding and convert to UTF-8 if needed
        $raw = file_get_contents($filePath);
        if ($raw === false) return [];

        // Strip UTF-8 BOM
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        // Try to detect encoding (Windows-1250 is common in Polish Excel files)
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1250');
        }

        // Auto-detect delimiter
        if ($delimiterHint === 'auto') {
            $firstLine = strtok($raw, "\n");
            $delimiterHint = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        }
        $delimiter = $delimiterHint === '\t' ? "\t" : $delimiterHint;

        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_import_');
        file_put_contents($tmpFile, $raw);

        $handle = fopen($tmpFile, 'r');
        if (!$handle) {
            @unlink($tmpFile);
            return [];
        }

        $headers = null;
        $rows    = [];

        while (($line = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if ($hasHeader && $headers === null) {
                // Normalize headers: lowercase, trim
                $headers = array_map(fn($h) => strtolower(trim($h)), $line);
                continue;
            }
            if ($headers === null) {
                // No header row — use numeric indices
                $rows[] = $line;
            } else {
                $assoc = [];
                foreach ($headers as $i => $key) {
                    $assoc[$key] = $line[$i] ?? '';
                }
                $rows[] = $assoc;
            }
        }

        fclose($handle);
        @unlink($tmpFile);
        return $rows;
    }

    private function mapCsvRow(array $row, string $defaultType): array
    {
        $get = function (array $r, array $keys): string {
            foreach ($keys as $k) {
                if (isset($r[$k]) && trim($r[$k]) !== '') return trim($r[$k]);
            }
            return '';
        };

        $type = $get($row, ['member_type','typ','type']);
        if (!in_array($type, ['rekreacyjny','wyczynowy'])) $type = $defaultType;

        return [
            'last_name'      => $get($row, ['last_name','nazwisko','surname']),
            'first_name'     => $get($row, ['first_name','imie','imię','name']),
            'pesel'          => $get($row, ['pesel']),
            'birth_date'     => $get($row, ['birth_date','data_urodzenia','birthdate']),
            'gender'         => strtoupper($get($row, ['gender','plec','płeć'])) ?: null,
            'email'          => $get($row, ['email','e-mail','mail']),
            'phone'          => $get($row, ['phone','telefon','tel']),
            'member_type'    => $type,
            'join_date'      => $get($row, ['join_date','data_wstapienia','joined']),
            'status'         => $get($row, ['status']) ?: 'aktywny',
            'address_street' => $get($row, ['address_street','ulica','street']),
            'address_city'   => $get($row, ['address_city','miasto','city']),
            'address_postal' => $get($row, ['address_postal','kod_pocztowy','postal']),
            'notes'          => $get($row, ['notes','uwagi']),
        ];
    }

    // ----------------------------------------------------------------

    public function index(): void
    {
        $filters = [
            'q'               => trim($_GET['q'] ?? ''),
            'status'          => $_GET['status'] ?? '',
            'member_type'     => $_GET['member_type'] ?? '',
            'age_category_id' => $_GET['age_category_id'] ?? '',
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $result  = $this->memberModel->search($filters, $page);

        $this->render('members/index', [
            'title'      => 'Zawodnicy',
            'result'     => $result,
            'filters'    => $filters,
            'categories' => $this->categoryModel->getAll(),
        ]);
    }

    public function create(): void
    {
        $this->render('members/form', [
            'title'         => 'Dodaj zawodnika',
            'member'        => null,
            'categories'    => $this->categoryModel->getAll(),
            'memberClasses' => $this->memberClassModel->getActive(),
            'disciplines'   => $this->disciplineModel->getActive(),
            'instructors'       => ($clubId = \App\Helpers\ClubContext::current())
                                    ? $this->userModel->getInstructorsForClub($clubId)
                                    : $this->userModel->getInstructors(),
            'disciplineClasses' => (new DisciplineClassModel())->getActive(),
            'memberTypes'       => (new MemberTypeModel())->getActive(),
            'mode'              => 'create',
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $data = $this->collectFormData();
        $errors = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            Session::flash('_old_input', $_POST);
            $this->redirect('members/create');
        }

        $id = $this->memberModel->createMember($data);

        // Handle disciplines
        $this->saveDisciplines($id);

        // Photo upload (two-step: need ID first)
        $photoPath = $this->handlePhotoUpload($id);
        if ($photoPath !== null) {
            $this->memberModel->updateMember($id, ['photo_path' => $photoPath]);
        }

        // Audit log
        $this->activityLog->log('member_create', 'member', $id, null);

        Session::flash('success', 'Zawodnik został dodany.');
        $this->redirect("members/{$id}");
    }

    public function show(string $id): void
    {
        $member = $this->memberModel->getWithDetails((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $this->render('members/show', [
            'title'       => $member['first_name'] . ' ' . $member['last_name'],
            'member'      => $member,
            'disciplines' => $this->memberModel->getDisciplines((int)$id),
            'medical'     => $this->memberModel->getLatestMedical((int)$id),
            'examMatrix'  => $this->examModel->getExamMatrix((int)$id),
            'license'     => $this->memberModel->getLatestLicense((int)$id),
            'payment'     => $this->memberModel->getPaymentStatus((int)$id, (int)date('Y')),
        ]);
    }

    public function edit(string $id): void
    {
        $member = $this->memberModel->getWithDetails((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $this->render('members/form', [
            'title'         => 'Edytuj zawodnika',
            'member'        => $member,
            'categories'    => $this->categoryModel->getAll(),
            'memberClasses' => $this->memberClassModel->getActive(),
            'disciplines'   => $this->disciplineModel->getActive(),
            'instructors'       => ($clubId = \App\Helpers\ClubContext::current())
                                    ? $this->userModel->getInstructorsForClub($clubId)
                                    : $this->userModel->getInstructors(),
            'disciplineClasses' => (new DisciplineClassModel())->getActive(),
            'memberTypes'       => (new MemberTypeModel())->getActive(),
            'memberDiscs'       => $this->memberModel->getDisciplines((int)$id),
            'mode'          => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();

        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $data = $this->collectFormData();
        $errors = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("members/{$id}/edit");
        }

        // Photo upload — update before diff so photo_path change appears in log
        $oldPhoto  = $member['photo_path'] ?? null;
        $photoPath = $this->handlePhotoUpload((int)$id);
        if ($photoPath !== null) {
            $data['photo_path'] = $photoPath;
            if ($oldPhoto && file_exists(ROOT_PATH . '/storage/photos/' . $oldPhoto)) {
                @unlink(ROOT_PATH . '/storage/photos/' . $oldPhoto);
            }
        }

        // Compute diff for audit log
        $skipFields = ['created_at', 'updated_at', 'created_by'];
        $changed = [];
        foreach ($data as $field => $newVal) {
            if (in_array($field, $skipFields, true)) continue;
            $oldVal = $member[$field] ?? null;
            if ((string)($oldVal ?? '') !== (string)($newVal ?? '')) {
                $changed[] = ['field' => $field, 'old' => $oldVal, 'new' => $newVal];
            }
        }

        $this->memberModel->updateMember((int)$id, $data);

        if ($changed) {
            $this->activityLog->log('member_update', 'member', (int)$id,
                json_encode(['changed' => $changed], JSON_UNESCAPED_UNICODE));
        }

        // Separate status change event
        foreach ($changed as $c) {
            if ($c['field'] === 'status') {
                $this->activityLog->log('member_status_change', 'member', (int)$id,
                    json_encode(['old' => $c['old'], 'new' => $c['new']], JSON_UNESCAPED_UNICODE));
                break;
            }
        }

        Session::flash('success', 'Dane zawodnika zostały zaktualizowane.');
        $this->redirect("members/{$id}");
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->requireRole(['admin', 'zarzad']);

        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        // Soft delete — change status instead of delete
        $oldStatus = $member['status'] ?? null;
        $this->memberModel->updateMember((int)$id, ['status' => 'wykreslony']);
        $this->activityLog->log('member_status_change', 'member', (int)$id,
            json_encode(['old' => $oldStatus, 'new' => 'wykreslony', 'action' => 'wykreslenie'],
                JSON_UNESCAPED_UNICODE));

        Session::flash('success', 'Zawodnik został wykreślony.');
        $this->redirect('members');
    }

    // ── History ───────────────────────────────────────────────────────

    public function history(string $id): void
    {
        $this->requireRole(['admin', 'zarzad']);

        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $entries = $this->activityLog->getForMember((int)$id);

        $this->render('members/history', [
            'title'   => 'Historia zmian — ' . $member['last_name'] . ' ' . $member['first_name'],
            'member'  => $member,
            'entries' => $entries,
        ]);
    }

    // ── Photo upload + serve ──────────────────────────────────────────

    private function handlePhotoUpload(int $memberId): ?string
    {
        if (empty($_FILES['photo']['name'])) {
            return null;
        }
        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $allowedMimes = ['image/jpeg', 'image/png'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            Session::flash('error', 'Dozwolone formaty zdjęcia: JPG, PNG.');
            return null;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            Session::flash('error', 'Zdjęcie nie może przekraczać 2 MB.');
            return null;
        }
        $storageDir = ROOT_PATH . '/storage/photos';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
        $filename = 'member' . $memberId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = $storageDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Nie udało się zapisać zdjęcia.');
            return null;
        }
        return $filename;
    }

    // servePhoto is on PhotoController (separate, no requireLogin in constructor)

    // ----------------------------------------------------------------

    private function collectFormData(): array
    {
        return [
            'first_name'      => trim($_POST['first_name'] ?? ''),
            'last_name'       => trim($_POST['last_name'] ?? ''),
            'pesel'           => trim($_POST['pesel'] ?? '') ?: null,
            'birth_date'      => $_POST['birth_date'] ?: null,
            'gender'          => $_POST['gender'] ?: null,
            'age_category_id'  => $_POST['age_category_id'] ?: null,
            'member_class_id'  => $_POST['member_class_id'] ?: null,
            'member_type'     => $_POST['member_type'] ?? 'rekreacyjny',
            'card_number'     => trim($_POST['card_number'] ?? '') ?: null,
            'email'           => trim($_POST['email'] ?? '') ?: null,
            'phone'           => trim($_POST['phone'] ?? '') ?: null,
            'address_street'  => trim($_POST['address_street'] ?? '') ?: null,
            'address_city'    => trim($_POST['address_city'] ?? '') ?: null,
            'address_postal'  => trim($_POST['address_postal'] ?? '') ?: null,
            'join_date'       => $_POST['join_date'] ?: date('Y-m-d'),
            'status'          => $_POST['status'] ?? 'aktywny',
            'notes'                  => trim($_POST['notes'] ?? '') ?: null,
            'firearm_permit_number'  => trim($_POST['firearm_permit_number'] ?? '') ?: null,
            'created_by'             => Auth::id(),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['first_name'])) $errors[] = 'Imię jest wymagane.';
        if (empty($data['last_name']))  $errors[] = 'Nazwisko jest wymagane.';
        if (empty($data['join_date']))  $errors[] = 'Data wstąpienia jest wymagana.';
        if (!in_array($data['member_type'], ['rekreacyjny','wyczynowy'])) {
            $errors[] = 'Nieprawidłowy typ członkostwa.';
        }
        return $errors;
    }

    public function memberCard(string $id): void
    {
        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $disciplines = $this->memberModel->getDisciplines((int)$id);
        $license     = $this->memberModel->getLatestLicense((int)$id);
        $clubName    = current_club_name('Klub Strzelecki');

        $this->view->setLayout('none');
        $this->render('members/card', [
            'title'       => 'Karta zawodnika',
            'member'      => $member,
            'disciplines' => $disciplines,
            'license'     => $license,
            'clubName'    => $clubName,
        ]);
    }

    public function memberCardPdf(string $id): void
    {
        $member = $this->memberModel->findById((int)$id);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $disciplines = $this->memberModel->getDisciplines((int)$id);
        $license     = $this->memberModel->getLatestLicense((int)$id);
        $clubName    = current_club_name('Klub Strzelecki');

        $html = $this->renderToString('pdf/member_card', [
            'member'      => $member,
            'disciplines' => $disciplines,
            'license'     => $license,
            'clubName'    => $clubName,
        ]);

        $safe     = preg_replace('/[^a-zA-Z0-9_-]/', '_', $member['last_name'] . '_' . $member['first_name']);
        $filename = 'legitymacja_' . $safe . '.pdf';
        \App\Helpers\PdfHelper::send($html, $filename, 'A5');
    }

    private function saveDisciplines(int $memberId): void
    {
        $disciplineIds  = $_POST['discipline_ids'] ?? [];
        $classes        = $_POST['discipline_classes'] ?? [];
        $instructorIds  = $_POST['discipline_instructors'] ?? [];
        $joinedAt       = $_POST['discipline_joined'] ?? [];

        foreach ($disciplineIds as $i => $discId) {
            if (!$discId) continue;
            try {
                $this->memberModel->addDiscipline([
                    'member_id'     => $memberId,
                    'discipline_id' => (int)$discId,
                    'class'         => $classes[$i] ?: null,
                    'instructor_id' => $instructorIds[$i] ?: null,
                    'joined_at'     => $joinedAt[$i] ?: date('Y-m-d'),
                ]);
            } catch (\Throwable) {
                // duplicate — skip
            }
        }
    }
}
