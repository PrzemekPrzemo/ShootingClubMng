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
}
