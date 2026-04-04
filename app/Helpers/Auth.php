<?php

namespace App\Helpers;

class Auth
{
    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'        => Session::get('user_id'),
            'username'  => Session::get('username'),
            'full_name' => Session::get('full_name'),
            'role'      => Session::get('role'),
        ];
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function role(): ?string
    {
        return Session::get('role');
    }

    public static function hasRole(string|array $roles): bool
    {
        $current = self::role();
        if ($current === null) return false;
        $roles = (array)$roles;
        return in_array($current, $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Musisz być zalogowany, aby uzyskać dostęp.');
            header('Location: ' . url('portal/login'));
            exit;
        }
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            http_response_code(403);
            die('Brak uprawnień do tej sekcji.');
        }
    }

    public static function login(array $user): void
    {
        Session::start();
        session_regenerate_id(true);

        // Clear any member portal session keys to prevent cross-session confusion
        Session::remove('member_id');
        Session::remove('member_full_name');
        Session::remove('member_email');
        Session::remove('member_status');
        Session::remove('must_change_password');

        Session::set('user_id',   $user['id']);
        Session::set('username',  $user['username']);
        Session::set('full_name', $user['full_name']);
        Session::set('role',      $user['role']);
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
