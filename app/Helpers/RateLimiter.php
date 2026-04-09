<?php

namespace App\Helpers;

/**
 * Simple DB-backed rate limiter.
 * Uses `rate_limits` table (key, attempts, reset_at).
 */
class RateLimiter
{
    private static int $maxAttempts = 5;
    private static int $decaySeconds = 900; // 15 minutes

    /**
     * Returns true if the action is allowed (not rate-limited).
     * Increments the attempt counter.
     */
    public static function attempt(string $key, int $maxAttempts = 0, int $decaySeconds = 0): bool
    {
        $max   = $maxAttempts   ?: self::$maxAttempts;
        $decay = $decaySeconds  ?: self::$decaySeconds;

        try {
            $db = Database::getInstance();
            self::clearExpired($db);

            $stmt = $db->prepare("SELECT attempts, reset_at FROM rate_limits WHERE `key` = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            if (!$row) {
                $db->prepare(
                    "INSERT INTO rate_limits (`key`, attempts, reset_at) VALUES (?, 1, ?)"
                )->execute([$key, date('Y-m-d H:i:s', time() + $decay)]);
                return true;
            }

            if ((int)$row['attempts'] >= $max) {
                return false;
            }

            $db->prepare(
                "UPDATE rate_limits SET attempts = attempts + 1 WHERE `key` = ?"
            )->execute([$key]);
            return true;
        } catch (\PDOException) {
            // If table missing, allow the action
            return true;
        }
    }

    /**
     * Returns true if the key is currently blocked.
     */
    public static function isBlocked(string $key, int $maxAttempts = 0): bool
    {
        $max = $maxAttempts ?: self::$maxAttempts;
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT attempts FROM rate_limits WHERE `key` = ? AND reset_at > NOW()"
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            return $row && (int)$row['attempts'] >= $max;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Returns seconds until the block expires (0 if not blocked).
     */
    public static function secondsUntilReset(string $key): int
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT TIMESTAMPDIFF(SECOND, NOW(), reset_at) AS secs FROM rate_limits WHERE `key` = ?"
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            return $row ? max(0, (int)$row['secs']) : 0;
        } catch (\PDOException) {
            return 0;
        }
    }

    /**
     * Clears all attempts for a key (call on successful login).
     */
    public static function clear(string $key): void
    {
        try {
            Database::getInstance()->prepare(
                "DELETE FROM rate_limits WHERE `key` = ?"
            )->execute([$key]);
        } catch (\PDOException) {}
    }

    /**
     * Builds a rate limit key from action + identifier (IP or username).
     */
    public static function key(string $action, string $identifier): string
    {
        return $action . ':' . hash('sha256', $identifier);
    }

    private static function clearExpired(\PDO $db): void
    {
        try {
            $db->exec("DELETE FROM rate_limits WHERE reset_at < NOW()");
        } catch (\PDOException) {}
    }
}
