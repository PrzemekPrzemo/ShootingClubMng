<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Models\MemberWeaponModel;

/**
 * Manages personal weapons owned by club members.
 * Staff (admin/zarząd/instruktor) can manage weapons for any member.
 * The member portal has a separate lightweight endpoint.
 */
class MemberWeaponsController extends BaseController
{
    private MemberWeaponModel $weaponModel;
    private MemberModel $memberModel;

    private const STAFF_ROLES = ['admin', 'zarzad', 'instruktor'];

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->weaponModel = new MemberWeaponModel();
        $this->memberModel = new MemberModel();
    }

    // ── List for a specific member (staff view) ───────────────────────

    public function index(string $memberId): void
    {
        $this->requireRole(self::STAFF_ROLES);

        $member  = $this->memberModel->findById((int)$memberId);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $weapons = $this->weaponModel->getForMember((int)$memberId);

        $this->render('members/weapons/index', [
            'title'   => 'Broń osobista — ' . e($member['first_name'] . ' ' . $member['last_name']),
            'member'  => $member,
            'weapons' => $weapons,
            'types'   => MemberWeaponModel::$TYPES,
        ]);
    }

    // ── Create form ───────────────────────────────────────────────────

    public function create(string $memberId): void
    {
        $this->requireRole(self::STAFF_ROLES);

        $member = $this->memberModel->findById((int)$memberId);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $this->render('members/weapons/form', [
            'title'    => 'Dodaj broń — ' . e($member['first_name'] . ' ' . $member['last_name']),
            'mode'     => 'create',
            'member'   => $member,
            'weapon'   => [],
            'types'    => MemberWeaponModel::$TYPES,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────

    public function store(string $memberId): void
    {
        $this->requireRole(self::STAFF_ROLES);
        Csrf::verify();

        $member = $this->memberModel->findById((int)$memberId);
        if (!$member) {
            Session::flash('error', 'Zawodnik nie istnieje.');
            $this->redirect('members');
        }

        $data   = $this->collectData((int)$memberId);
        $errors = $this->validateData($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('members/' . $memberId . '/weapons/create');
        }

        $data['created_by'] = Auth::id();
        $this->weaponModel->create($data);

        Session::flash('success', 'Broń została dodana.');
        $this->redirect('members/' . $memberId . '/weapons');
    }

    // ── Edit form ─────────────────────────────────────────────────────

    public function edit(string $memberId, string $weaponId): void
    {
        $this->requireRole(self::STAFF_ROLES);

        $member = $this->memberModel->findById((int)$memberId);
        $weapon = $this->weaponModel->findById((int)$weaponId);

        if (!$member || !$weapon || (int)$weapon['member_id'] !== (int)$memberId) {
            Session::flash('error', 'Nie znaleziono rekordu.');
            $this->redirect('members/' . $memberId . '/weapons');
        }

        $this->render('members/weapons/form', [
            'title'  => 'Edytuj broń — ' . e($member['first_name'] . ' ' . $member['last_name']),
            'mode'   => 'edit',
            'member' => $member,
            'weapon' => $weapon,
            'types'  => MemberWeaponModel::$TYPES,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────

    public function update(string $memberId, string $weaponId): void
    {
        $this->requireRole(self::STAFF_ROLES);
        Csrf::verify();

        $member = $this->memberModel->findById((int)$memberId);
        $weapon = $this->weaponModel->findById((int)$weaponId);

        if (!$member || !$weapon || (int)$weapon['member_id'] !== (int)$memberId) {
            Session::flash('error', 'Nie znaleziono rekordu.');
            $this->redirect('members');
        }

        $data   = $this->collectData((int)$memberId);
        $errors = $this->validateData($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('members/' . $memberId . '/weapons/' . $weaponId . '/edit');
        }

        $this->weaponModel->updateWeapon((int)$weaponId, $data);

        Session::flash('success', 'Zmiany zostały zapisane.');
        $this->redirect('members/' . $memberId . '/weapons');
    }

    // ── Delete ────────────────────────────────────────────────────────

    public function destroy(string $memberId, string $weaponId): void
    {
        $this->requireRole(self::STAFF_ROLES);
        Csrf::verify();

        $weapon = $this->weaponModel->findById((int)$weaponId);
        if ($weapon && (int)$weapon['member_id'] === (int)$memberId) {
            $this->weaponModel->delete((int)$weaponId);
            Session::flash('success', 'Broń została usunięta.');
        }

        $this->redirect('members/' . $memberId . '/weapons');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function collectData(int $memberId): array
    {
        return [
            'member_id'      => $memberId,
            'name'           => trim($_POST['name'] ?? ''),
            'type'           => $_POST['type'] ?? 'inne',
            'serial_number'  => trim($_POST['serial_number'] ?? '') ?: null,
            'caliber'        => trim($_POST['caliber'] ?? '') ?: null,
            'manufacturer'   => trim($_POST['manufacturer'] ?? '') ?: null,
            'permit_number'  => trim($_POST['permit_number'] ?? '') ?: null,
            'booklet_number' => trim($_POST['booklet_number'] ?? '') ?: null,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function validateData(array $data): array
    {
        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Nazwa broni jest wymagana.';
        }
        if (!array_key_exists($data['type'], MemberWeaponModel::$TYPES)) {
            $errors[] = 'Nieprawidłowy typ broni.';
        }
        return $errors;
    }
}
