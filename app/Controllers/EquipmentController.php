<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AmmoModel;
use App\Models\MemberModel;
use App\Models\MemberWeaponModel;
use App\Models\WeaponModel;

class EquipmentController extends BaseController
{
    private WeaponModel       $weaponModel;
    private AmmoModel         $ammoModel;
    private MemberModel       $memberModel;
    private MemberWeaponModel $memberWeaponModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->weaponModel       = new WeaponModel();
        $this->ammoModel         = new AmmoModel();
        $this->memberModel       = new MemberModel();
        $this->memberWeaponModel = new MemberWeaponModel();
    }

    // ── Weapons list + overview ──────────────────────────────────────

    public function index(): void
    {
        $filters = [
            'q'         => trim($_GET['q'] ?? ''),
            'type'      => $_GET['type'] ?? '',
            'condition' => $_GET['condition'] ?? '',
            'is_active' => $_GET['is_active'] ?? '1',
        ];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->weaponModel->getAll($filters, $page);
        $ammoSummary = $this->ammoModel->getSummaryByCaliber();

        $clubId         = ClubContext::current();
        $memberWeapons  = $clubId !== null ? $this->memberWeaponModel->getActiveForClub($clubId) : [];

        $this->render('equipment/index', [
            'title'         => 'Sprzęt',
            'result'        => $result,
            'filters'       => $filters,
            'ammoSummary'   => $ammoSummary,
            'memberWeapons' => $memberWeapons,
        ]);
    }

    // ── Create weapon ────────────────────────────────────────────────

    public function createWeapon(): void
    {
        $this->render('equipment/weapon_form', [
            'title'  => 'Dodaj broń',
            'weapon' => null,
        ]);
    }

    public function storeWeapon(): void
    {
        Csrf::verify();
        $data   = $this->collectWeaponData();
        $errors = $this->validateWeapon($data);

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->render('equipment/weapon_form', ['title' => 'Dodaj broń', 'weapon' => $data]);
            return;
        }

        $this->weaponModel->createWeapon($data);
        Session::flash('success', 'Broń dodana.');
        $this->redirect('equipment');
    }

    // ── Edit weapon ──────────────────────────────────────────────────

    public function editWeapon(string $id): void
    {
        $weapon = $this->getWeapon((int)$id);
        $this->render('equipment/weapon_form', [
            'title'      => 'Edytuj broń',
            'weapon'     => $weapon,
            'assignment' => $this->weaponModel->getCurrentAssignment((int)$id),
            'history'    => $this->weaponModel->getAssignmentHistory((int)$id),
            'members'    => $this->memberModel->getAllActive(),
        ]);
    }

    public function updateWeapon(string $id): void
    {
        Csrf::verify();
        $weapon = $this->getWeapon((int)$id);
        $data   = $this->collectWeaponData();
        $errors = $this->validateWeapon($data);

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->render('equipment/weapon_form', [
                'title'      => 'Edytuj broń',
                'weapon'     => array_merge($weapon, $data),
                'assignment' => $this->weaponModel->getCurrentAssignment((int)$id),
                'history'    => $this->weaponModel->getAssignmentHistory((int)$id),
                'members'    => $this->memberModel->getAllActive(),
            ]);
            return;
        }

        $this->weaponModel->updateWeapon((int)$id, $data);
        Session::flash('success', 'Dane broni zaktualizowane.');
        $this->redirect('equipment/' . $id . '/edit');
    }

    public function destroyWeapon(string $id): void
    {
        Csrf::verify();
        $this->getWeapon((int)$id);
        $this->weaponModel->deleteWeapon((int)$id);
        Session::flash('success', 'Broń wycofana z ewidencji.');
        $this->redirect('equipment');
    }

    // ── Weapon assignment ────────────────────────────────────────────

    public function assignWeapon(string $id): void
    {
        Csrf::verify();
        $this->getWeapon((int)$id);

        $memberId     = (int)($_POST['member_id'] ?? 0);
        $assignedDate = trim($_POST['assigned_date'] ?? date('Y-m-d'));
        $notes        = trim($_POST['notes'] ?? '') ?: null;

        if (!$memberId) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('equipment/' . $id . '/edit');
        }

        $this->weaponModel->assign((int)$id, $memberId, $assignedDate, $notes);
        Session::flash('success', 'Broń przypisana.');
        $this->redirect('equipment/' . $id . '/edit');
    }

    public function returnWeapon(string $aid): void
    {
        Csrf::verify();
        $returnedDate = trim($_POST['returned_date'] ?? date('Y-m-d'));
        $this->weaponModel->returnWeapon((int)$aid, $returnedDate);
        Session::flash('success', 'Zwrot broni odnotowany.');
        $this->safeRedirectBack('equipment');
    }

    // ── Ammo ─────────────────────────────────────────────────────────

    public function ammo(): void
    {
        $filters = [
            'caliber'   => trim($_GET['caliber'] ?? ''),
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to'] ?? '',
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $result  = $this->ammoModel->getAll($filters, $page);
        $summary = $this->ammoModel->getSummaryByCaliber();
        $calibers = $this->ammoModel->getCaliberList();

        $this->render('equipment/ammo', [
            'title'    => 'Amunicja',
            'result'   => $result,
            'filters'  => $filters,
            'summary'  => $summary,
            'calibers' => $calibers,
        ]);
    }

    public function storeAmmo(): void
    {
        Csrf::verify();

        $caliber  = trim($_POST['caliber'] ?? '');
        $type     = trim($_POST['type'] ?? '') ?: null;
        $quantity = (int)($_POST['quantity'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '') ?: null;
        $date     = trim($_POST['recorded_at'] ?? date('Y-m-d'));

        if (!$caliber || $quantity === 0) {
            Session::flash('error', 'Kaliber i ilość są wymagane (ilość ≠ 0).');
            $this->redirect('equipment/ammo');
        }

        $this->ammoModel->recordMovement([
            'caliber'     => $caliber,
            'type'        => $type,
            'quantity'    => $quantity,
            'notes'       => $notes,
            'recorded_at' => $date,
            'recorded_by' => Auth::id(),
        ]);

        $label = $quantity > 0 ? 'Przyjęcie' : 'Wydanie';
        Session::flash('success', $label . ' amunicji zapisane.');
        $this->redirect('equipment/ammo');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function getWeapon(int $id): array
    {
        $weapon = $this->weaponModel->findById($id);
        if (!$weapon) {
            Session::flash('error', 'Broń nie istnieje.');
            $this->redirect('equipment');
        }
        return $weapon;
    }

    private function collectWeaponData(): array
    {
        $types      = ['karabin','pistolet','strzelba','inne'];
        $conditions = ['dobry','wymaga_obslugi','uszkodzona','wycofana'];

        return [
            'name'          => trim($_POST['name'] ?? ''),
            'type'          => in_array($_POST['type'] ?? '', $types) ? $_POST['type'] : 'inne',
            'serial_number' => trim($_POST['serial_number'] ?? '') ?: null,
            'caliber'       => trim($_POST['caliber'] ?? '') ?: null,
            'manufacturer'  => trim($_POST['manufacturer'] ?? '') ?: null,
            'purchase_date' => trim($_POST['purchase_date'] ?? '') ?: null,
            'condition'     => in_array($_POST['condition'] ?? '', $conditions) ? $_POST['condition'] : 'dobry',
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function validateWeapon(array $data): array
    {
        $errors = [];
        if (empty($data['name'])) $errors[] = 'Nazwa broni jest wymagana.';
        return $errors;
    }

    // ── Member weapon — PESEL search (JSON) ──────────────────────────

    public function memberSearch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $pesel  = trim($_GET['pesel'] ?? '');
        $clubId = ClubContext::current();

        if (strlen($pesel) < 5 || !$clubId) {
            echo json_encode(['member' => null, 'error' => 'Podaj PESEL.']);
            exit;
        }

        $db   = \App\Helpers\Database::pdo();
        $stmt = $db->prepare(
            "SELECT id, first_name, last_name, member_number, status, firearm_permit_number
             FROM members
             WHERE pesel = ? AND club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$pesel, $clubId]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['member' => null, 'error' => 'Nie znaleziono zawodnika w tym klubie.']);
            exit;
        }

        echo json_encode(['member' => [
            'id'             => (int)$row['id'],
            'full_name'      => $row['last_name'] . ' ' . $row['first_name'],
            'member_number'  => $row['member_number'],
            'status'         => $row['status'],
            'permit_number'  => $row['firearm_permit_number'] ?? '',
        ]]);
        exit;
    }

    // ── Member weapon — store (POST) ──────────────────────────────────

    public function storeMemberWeapon(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        $clubId   = ClubContext::current();

        if (!$memberId || !$clubId) {
            Session::flash('error', 'Brak danych zawodnika.');
            $this->redirect('equipment/weapons/create');
        }

        // Ownership check — member must belong to current club
        $db   = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("SELECT id FROM members WHERE id = ? AND club_id = ?");
        $stmt->execute([$memberId, $clubId]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Zawodnik nie należy do tego klubu.');
            $this->redirect('equipment/weapons/create');
        }

        $name = trim($_POST['mw_name'] ?? '');
        if (empty($name)) {
            Session::flash('error', 'Nazwa broni zawodnika jest wymagana.');
            $this->redirect('equipment/weapons/create');
        }

        $this->memberWeaponModel->create([
            'member_id'     => $memberId,
            'name'          => $name,
            'type'          => $_POST['mw_type'] ?? 'inne',
            'caliber'       => trim($_POST['mw_caliber'] ?? '') ?: null,
            'manufacturer'  => trim($_POST['mw_manufacturer'] ?? '') ?: null,
            'serial_number' => trim($_POST['mw_serial_number'] ?? '') ?: null,
            'permit_number' => trim($_POST['mw_permit_number'] ?? '') ?: null,
            'notes'         => trim($_POST['mw_notes'] ?? '') ?: null,
            'is_active'     => 1,
            'created_by'    => Auth::id(),
        ]);

        Session::flash('success', 'Broń zawodnika została dodana i jest widoczna w jego portalu.');
        $this->redirect('equipment/weapons/create');
    }
}
