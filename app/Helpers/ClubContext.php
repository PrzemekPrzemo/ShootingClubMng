<?php

namespace App\Helpers;

/**
 * Zarządza aktywnym kontekstem klubu w sesji.
 *
 * Każde żądanie ma przypisany jeden aktywny klub (club_id z sesji).
 * Super admin może przełączać kontekst między klubami.
 * ClubContext::setFromSubdomain() jest wywoływany przed routingiem
 * w public/index.php aby subdomena automatycznie ustawiała kontekst.
 */
class ClubContext
{
    private const SESSION_KEY       = 'club_id';
    private const SUPER_ADMIN_KEY   = 'is_super_admin';

    /** Zwraca aktywny club_id z sesji lub null jeśli brak kontekstu. */
    public static function current(): ?int
    {
        $val = Session::get(self::SESSION_KEY);
        return $val !== null ? (int)$val : null;
    }

    /** Ustawia aktywny klub w sesji. */
    public static function set(int $clubId): void
    {
        Session::set(self::SESSION_KEY, $clubId);
    }

    /** Czyści kontekst klubu z sesji (np. przy wylogowaniu). */
    public static function clear(): void
    {
        Session::remove(self::SESSION_KEY);
    }

    /** Sprawdza czy zalogowany użytkownik jest super adminem. */
    public static function isSuperAdmin(): bool
    {
        return (bool)Session::get(self::SUPER_ADMIN_KEY, false);
    }

    /**
     * Wykrywa klub na podstawie subdomeny żądania HTTP.
     *
     * Przykład: host = "mks-gdansk.system.pl", baseDomain = "system.pl"
     * → wyszukuje club_customization.subdomain = "mks-gdansk"
     * → ustawia kontekst jeśli znaleziono
     *
     * Wywołaj raz na początku public/index.php przed routingiem.
     */
    public static function setFromSubdomain(string $host, string $baseDomain): void
    {
        if (empty($baseDomain) || empty($host)) {
            return;
        }

        // Odetnij port jeśli jest (host może być "mks-gdansk.system.pl:8080")
        $host = strtolower(explode(':', $host)[0]);
        $base = strtolower($baseDomain);

        // Sprawdź czy host jest subdomeną bazy
        if (!str_ends_with($host, '.' . $base)) {
            return;
        }

        $subdomain = substr($host, 0, strlen($host) - strlen('.' . $base));

        // Pomiń "www" i puste subdomeny
        if ($subdomain === '' || $subdomain === 'www') {
            return;
        }

        // Wyszukaj klub po subdomenie
        try {
            $db  = Database::pdo();
            $stmt = $db->prepare(
                'SELECT club_id FROM club_customization WHERE subdomain = ? LIMIT 1'
            );
            $stmt->execute([$subdomain]);
            $row = $stmt->fetch();

            if ($row) {
                self::set((int)$row['club_id']);
            }
        } catch (\Throwable) {
            // Tabela może jeszcze nie istnieć podczas setup — ignoruj
        }
    }

    /**
     * Zwraca aktywny club_id lub rzuca wyjątek jeśli kontekst nie jest ustawiony.
     * Używaj w kontrolerach po requireClubContext().
     */
    public static function require(): int
    {
        $id = self::current();
        if ($id === null) {
            throw new \RuntimeException('Brak aktywnego kontekstu klubu w sesji.');
        }
        return $id;
    }
}
