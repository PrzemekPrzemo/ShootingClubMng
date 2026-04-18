<?php

namespace App\Helpers;

/**
 * Member authentication — uses separate session keys from staff Auth.
 * Staff uses 'user_id'; members use 'member_id'. Never overlap.
 */
class MemberAuth
{
    public static function check(): bool
    {
        return Session::has('member_id');
    }

    public static function member(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id'                  => Session::get('member_id'),
            'club_id'             => Session::get('member_club_id'),
            'full_name'           => Session::get('member_full_name'),
            'email'               => Session::get('member_email'),
            'status'              => Session::get('member_status'),
            'must_change_password'=> Session::get('must_change_password'),
        ];
    }

    public static function id(): ?int
    {
        return Session::get('member_id');
    }

    public static function clubId(): ?int
    {
        $cid = Session::get('member_club_id');
        return $cid !== null ? (int)$cid : null;
    }

    public static function mustChangePassword(): bool
    {
        return (bool) Session::get('must_change_password');
    }

    public static function login(array $member): void
    {
        Session::start();
        session_regenerate_id(true);

        // Clear any staff session keys to prevent cross-session confusion
        Session::remove('user_id');
        Session::remove('username');
        Session::remove('full_name');
        Session::remove('role');

        Session::set('member_id',            (int)$member['id']);
        Session::set('member_club_id',       isset($member['club_id']) ? (int)$member['club_id'] : null);
        Session::set('member_full_name',     trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')));
        Session::set('member_email',         $member['email'] ?? '');
        Session::set('member_status',        $member['status'] ?? 'aktywny');
        Session::set('must_change_password', (bool)($member['must_change_password'] ?? true));
    }

    public static function logout(): void
    {
        Session::remove('member_id');
        Session::remove('member_club_id');
        Session::remove('member_full_name');
        Session::remove('member_email');
        Session::remove('member_status');
        Session::remove('must_change_password');
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Musisz się zalogować, aby uzyskać dostęp do portalu.');
            header('Location: ' . url('portal/login'));
            exit;
        }
    }
}
