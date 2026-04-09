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

    // ------------------------------------------------------------------
    // Multi-club: powiązanie użytkowników z klubami
    // ------------------------------------------------------------------

    /** Pobierz listę klubów przypisanych do użytkownika (z rolą). */
    public function getClubsForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT uc.club_id, uc.role, uc.is_active, c.name AS club_name, c.short_name
             FROM user_clubs uc
             JOIN clubs c ON c.id = uc.club_id
             WHERE uc.user_id = ? AND uc.is_active = 1 AND c.is_active = 1
             ORDER BY c.name"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Zwróć rolę użytkownika w danym klubie (lub null). */
    public function getRoleInClub(int $userId, int $clubId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT role FROM user_clubs WHERE user_id = ? AND club_id = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$userId, $clubId]);
        $row = $stmt->fetch();
        return $row ? $row['role'] : null;
    }

    /** Przypisz użytkownika do klubu z rolą. */
    public function assignToClub(int $userId, int $clubId, string $role): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO user_clubs (user_id, club_id, role, is_active)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE role = VALUES(role), is_active = 1"
        );
        $stmt->execute([$userId, $clubId, $role]);
    }

    /** Usuń użytkownika z klubu (soft — is_active = 0). */
    public function removeFromClub(int $userId, int $clubId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_clubs SET is_active = 0 WHERE user_id = ? AND club_id = ?"
        );
        $stmt->execute([$userId, $clubId]);
    }

    /** Pobierz użytkowników przypisanych do danego klubu. */
    public function getUsersForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.email, u.full_name, u.is_active, u.last_login,
                    uc.role AS club_role
             FROM user_clubs uc
             JOIN users u ON u.id = uc.user_id
             WHERE uc.club_id = ? AND uc.is_active = 1
             ORDER BY u.full_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
