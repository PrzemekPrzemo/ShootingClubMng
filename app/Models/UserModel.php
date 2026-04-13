<?php

namespace App\Models;

class UserModel extends BaseModel
{
    protected string $table = 'users';

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$id]);
    }

    public function createUser(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->insert($data);
    }

    public function updateUser(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            unset($data['password']);
        }
        return $this->update($id, $data);
    }

    public function getAllUsers(): array
    {
        return $this->db->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM users ORDER BY full_name")->fetchAll();
    }

    public function getInstructors(): array
    {
        $stmt = $this->db->query("SELECT id, full_name FROM users WHERE role = 'instruktor' AND is_active = 1 ORDER BY full_name");
        return $stmt->fetchAll();
    }

    /** Instruktorzy przypisani do konkretnego klubu (rola 'instruktor' w user_clubs). */
    public function getInstructorsForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id, u.full_name
             FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND uc.role = 'instruktor' AND uc.is_active = 1 AND u.is_active = 1
             ORDER BY u.full_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------
    // Multi-club: powiązanie użytkowników z klubami
    // ------------------------------------------------------------------

    /**
     * Role priority — higher value = more permissions.
     * Used to compute the effective (highest) role for a user.
     */
    public const ROLE_PRIORITY = [
        'admin'     => 5,
        'zarzad'    => 4,
        'sędzia'    => 3,
        'instruktor'=> 2,
        'zawodnik'  => 1,
    ];

    /** Returns the highest-priority role from a list. */
    public static function highestRole(array $roles): string
    {
        $best = 'zawodnik';
        foreach ($roles as $r) {
            if ((self::ROLE_PRIORITY[$r] ?? 0) > (self::ROLE_PRIORITY[$best] ?? 0)) {
                $best = $r;
            }
        }
        return $best;
    }

    /**
     * Pobierz listę klubów przypisanych do użytkownika.
     * Każdy klub zwraca roles[] (tablica ról) + highest_role (najwyższa).
     */
    public function getClubsForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT uc.club_id, uc.role, uc.is_active, uc.linked_member_id, c.name AS club_name, c.short_name
             FROM user_clubs uc
             JOIN clubs c ON c.id = uc.club_id
             WHERE uc.user_id = ? AND uc.is_active = 1 AND c.is_active = 1
             ORDER BY c.name, FIELD(uc.role,'admin','zarzad','sędzia','instruktor','zawodnik')"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        // Group by club_id so each club has a roles[] array
        $grouped = [];
        foreach ($rows as $row) {
            $cid = $row['club_id'];
            if (!isset($grouped[$cid])) {
                $grouped[$cid] = [
                    'club_id'          => $cid,
                    'club_name'        => $row['club_name'],
                    'short_name'       => $row['short_name'],
                    'is_active'        => $row['is_active'],
                    'roles'            => [],
                    'highest_role'     => 'zawodnik',
                    // keep legacy 'role' key for backward compat
                    'role'             => 'zawodnik',
                    'linked_member_id' => $row['linked_member_id'] ? (int)$row['linked_member_id'] : null,
                ];
            }
            $grouped[$cid]['roles'][]       = $row['role'];
            $grouped[$cid]['highest_role']  = self::highestRole($grouped[$cid]['roles']);
            $grouped[$cid]['role']          = $grouped[$cid]['highest_role'];
        }
        return array_values($grouped);
    }

    /**
     * Zwróć najwyższą rolę użytkownika w danym klubie (lub null).
     */
    public function getRoleInClub(int $userId, int $clubId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT role FROM user_clubs WHERE user_id = ? AND club_id = ? AND is_active = 1"
        );
        $stmt->execute([$userId, $clubId]);
        $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $roles ? self::highestRole($roles) : null;
    }

    /**
     * Zwróć wszystkie role użytkownika w danym klubie.
     */
    public function getRolesInClub(int $userId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT role FROM user_clubs WHERE user_id = ? AND club_id = ? AND is_active = 1"
        );
        $stmt->execute([$userId, $clubId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Ustaw role użytkownika w klubie (zastępuje wszystkie poprzednie).
     * Deaktywuje nieobecne role, dodaje nowe.
     */
    public function setRolesInClub(int $userId, int $clubId, array $roles): void
    {
        // Preserve linked_member_id before soft-deleting
        $linkedId = $this->getLinkedMemberId($userId, $clubId);

        // Soft-delete all current roles
        $this->db->prepare(
            "UPDATE user_clubs SET is_active = 0 WHERE user_id = ? AND club_id = ?"
        )->execute([$userId, $clubId]);

        // Re-insert/activate each selected role (preserving linked member)
        $ins = $this->db->prepare(
            "INSERT INTO user_clubs (user_id, club_id, role, is_active, linked_member_id)
             VALUES (?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE is_active = 1, linked_member_id = VALUES(linked_member_id)"
        );
        foreach (array_unique($roles) as $role) {
            if (isset(self::ROLE_PRIORITY[$role])) {
                $ins->execute([$userId, $clubId, $role, $linkedId]);
            }
        }
    }

    /** Przypisz użytkownika do klubu z pojedynczą rolą (backward compat). */
    public function assignToClub(int $userId, int $clubId, string $role): void
    {
        $this->db->prepare(
            "INSERT INTO user_clubs (user_id, club_id, role, is_active)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE is_active = 1"
        )->execute([$userId, $clubId, $role]);
    }

    /** Usuń użytkownika z klubu (soft — is_active = 0). */
    public function removeFromClub(int $userId, int $clubId): void
    {
        $this->db->prepare(
            "UPDATE user_clubs SET is_active = 0 WHERE user_id = ? AND club_id = ?"
        )->execute([$userId, $clubId]);
    }

    /** Pobierz użytkowników przypisanych do danego klubu. */
    public function getUsersForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.email, u.full_name, u.is_active, u.last_login,
                    GROUP_CONCAT(uc.role ORDER BY FIELD(uc.role,'admin','zarzad','sędzia','instruktor','zawodnik') SEPARATOR ',') AS club_roles
             FROM user_clubs uc
             JOIN users u ON u.id = uc.user_id
             WHERE uc.club_id = ? AND uc.is_active = 1
             GROUP BY u.id, u.username, u.email, u.full_name, u.is_active, u.last_login
             ORDER BY u.full_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------
    // Linked member (user ↔ member portal switching)
    // ------------------------------------------------------------------

    /** Pobierz linked_member_id dla użytkownika w danym klubie. */
    public function getLinkedMemberId(int $userId, int $clubId): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT linked_member_id FROM user_clubs
             WHERE user_id = ? AND club_id = ? AND is_active = 1 AND linked_member_id IS NOT NULL
             LIMIT 1"
        );
        $stmt->execute([$userId, $clubId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['linked_member_id'] : null;
    }

    /** Ustaw powiązanie z zawodnikiem (na wszystkich aktywnych rolach w klubie). */
    public function setLinkedMemberId(int $userId, int $clubId, ?int $memberId): void
    {
        $this->db->prepare(
            "UPDATE user_clubs SET linked_member_id = ? WHERE user_id = ? AND club_id = ? AND is_active = 1"
        )->execute([$memberId, $userId, $clubId]);
    }

    /** Pobierz dane powiązanego zawodnika (do sesji). */
    public function getLinkedMember(int $userId, int $clubId): ?array
    {
        $linkedId = $this->getLinkedMemberId($userId, $clubId);
        if (!$linkedId) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, email, status, club_id
             FROM members WHERE id = ? AND status = 'aktywny' LIMIT 1"
        );
        $stmt->execute([$linkedId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Pobierz aktywnych zawodników klubu (do dropdownu powiązania). */
    public function getMembersForLinking(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, email, member_number
             FROM members
             WHERE club_id = ? AND status = 'aktywny'
             ORDER BY last_name, first_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
