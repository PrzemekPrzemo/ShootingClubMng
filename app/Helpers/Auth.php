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
            'id'             => Session::get('user_id'),
            'username'       => Session::get('username'),
            'full_name'      => Session::get('full_name'),
            'role'           => Session::get('role'),
            'club_id'        => Session::get('club_id'),
            'is_super_admin' => (bool)Session::get('is_super_admin', false),
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

        Session::set('user_id',        $user['id']);
        Session::set('username',       $user['username']);
        Session::set('full_name',      $user['full_name']);
        Session::set('role',           $user['role']);
        Session::set('is_super_admin', !empty($user['is_super_admin']));

        // club_id ustawiany osobno przez setClub() po wyborze klubu
    }

    /** Ustaw aktywny klub w sesji (po zalogowaniu lub przełączeniu). */
    public static function setClub(int $clubId, string $roleInClub): void
    {
        Session::set('club_id', $clubId);
        Session::set('role', $roleInClub);
        ClubContext::set($clubId);
    }

    /** Czy zalogowany user jest super adminem? */
    public static function isSuperAdmin(): bool
    {
        return (bool)Session::get('is_super_admin', false);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    // ── Impersonation ────────────────────────────────────────────────────────

    /** Super admin starts impersonating a club user. Saves original session. */
    public static function impersonateClubUser(array $targetUser, int $clubId, string $roleInClub): void
    {
        Session::set('impersonation_original', [
            'user_id'        => Session::get('user_id'),
            'username'       => Session::get('username'),
            'full_name'      => Session::get('full_name'),
            'role'           => Session::get('role'),
            'club_id'        => Session::get('club_id'),
            'is_super_admin' => Session::get('is_super_admin'),
        ]);

        Session::set('user_id',        $targetUser['id']);
        Session::set('username',       $targetUser['username']);
        Session::set('full_name',      $targetUser['full_name']);
        Session::set('role',           $roleInClub);
        Session::set('club_id',        $clubId);
        Session::set('is_super_admin', false);
        Session::set('impersonating',  'club_user');
        ClubContext::set($clubId);
    }

    /** Super admin starts impersonating a member (portal session). */
    public static function impersonateMember(array $member): void
    {
        Session::set('impersonation_original', [
            'user_id'        => Session::get('user_id'),
            'username'       => Session::get('username'),
            'full_name'      => Session::get('full_name'),
            'role'           => Session::get('role'),
            'club_id'        => Session::get('club_id'),
            'is_super_admin' => Session::get('is_super_admin'),
        ]);

        Session::set('member_id',        $member['id']);
        Session::set('member_full_name', $member['first_name'] . ' ' . $member['last_name']);
        Session::set('member_email',     $member['email'] ?? '');
        Session::set('member_status',    $member['status']);
        Session::set('impersonating',    'member');
        // Clear admin keys so portal works cleanly
        Session::set('user_id',    null);
        ClubContext::set((int)$member['club_id']);
    }

    /** Stop impersonation and restore original admin session. */
    public static function stopImpersonation(): void
    {
        $original = Session::get('impersonation_original');
        if (!$original) return;

        Session::remove('member_id');
        Session::remove('member_full_name');
        Session::remove('member_email');
        Session::remove('member_status');
        Session::remove('must_change_password');
        Session::remove('impersonating');
        Session::remove('impersonation_original');

        foreach ($original as $k => $v) {
            Session::set($k, $v);
        }
        if (!empty($original['club_id'])) {
            ClubContext::set((int)$original['club_id']);
        } else {
            ClubContext::clear();
        }
    }

    public static function isImpersonating(): bool
    {
        return Session::get('impersonating') !== null;
    }

    public static function impersonatingType(): ?string
    {
        return Session::get('impersonating');
    }
}
