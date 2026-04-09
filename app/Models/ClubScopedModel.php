<?php

namespace App\Models;

use App\Helpers\ClubContext;

/**
 * Abstrakcyjna klasa bazowa dla modeli powiązanych z klubem.
 *
 * Automatycznie filtruje zapytania po club_id z ClubContext.
 * Automatycznie dodaje club_id przy insercie.
 * Super admin może wyłączyć scope przez withoutScope().
 */
abstract class ClubScopedModel extends BaseModel
{
    private bool $scopeEnabled = true;

    /** Zwraca aktywny club_id z kontekstu lub null gdy scope wyłączony. */
    protected function clubId(): ?int
    {
        if (!$this->scopeEnabled) {
            return null;
        }
        return ClubContext::current();
    }

    /** Wyłącza filtrowanie po club_id (dla super admina). Zwraca $this. */
    public function withoutScope(): static
    {
        $this->scopeEnabled = false;
        return $this;
    }

    /** Przywraca filtrowanie po club_id. */
    public function withScope(): static
    {
        $this->scopeEnabled = true;
        return $this;
    }

    // ------------------------------------------------------------------
    // Override BaseModel methods to add club_id filtering
    // ------------------------------------------------------------------

    public function findById(int $id): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::findById($id);
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::findAll($orderBy, $dir);
        }
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $stmt    = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE club_id = ? ORDER BY `{$orderBy}` {$dir}"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::delete($id);
        }
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }

    public function count(): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::count();
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        return (int)$stmt->fetchColumn();
    }

    protected function insert(array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId !== null && !isset($data['club_id'])) {
            $data['club_id'] = $clubId;
        }
        return parent::insert($data);
    }

    /**
     * Paginacja z automatycznym filtrowaniem po club_id.
     * SQL powinien zawierać placeholder :club_id jeśli potrzebny
     * — ta metoda go dodaje automatycznie.
     */
    protected function paginateScoped(string $sql, array $params, int $page, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $params[] = $clubId;
        }
        return parent::paginate($sql, $params, $page, $perPage);
    }

    /**
     * Helper: warunek SQL club_id do wstawiania w WHERE.
     * Zwraca " AND club_id = ?" lub pusty string (super admin).
     */
    protected function clubWhere(): string
    {
        return $this->clubId() !== null ? ' AND club_id = ?' : '';
    }

    /**
     * Helper: parametry club_id do dodania do tablicy params.
     * Zwraca [clubId] lub [] (super admin).
     */
    protected function clubParams(): array
    {
        $id = $this->clubId();
        return $id !== null ? [$id] : [];
    }
}
