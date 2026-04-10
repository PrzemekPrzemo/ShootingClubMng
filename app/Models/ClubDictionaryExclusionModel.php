<?php

namespace App\Models;

/**
 * Zarządza wykluczeniami globalnych wpisów słownika per klub.
 * Klub może ukryć dowolny globalny wpis (club_id IS NULL) ze swojego widoku.
 * Nie usuwa wpisu globalnego — tylko go ukrywa dla danego klubu.
 */
class ClubDictionaryExclusionModel extends BaseModel
{
    protected string $table = 'club_dictionary_exclusions';

    /** Dozwolone klucze słowników — odpowiadają DICTIONARY_KEY w modelach. */
    public const ALLOWED = [
        'categories',
        'disciplines',
        'member_classes',
        'license_types',
        'medical_exam_types',
        'discipline_classes',
        'member_types',
    ];

    /** Ukrywa globalny wpis dla danego klubu. Idempotentna (INSERT IGNORE). */
    public function exclude(int $clubId, string $dictionary, int $entryId): void
    {
        $this->db->prepare(
            "INSERT IGNORE INTO club_dictionary_exclusions (club_id, dictionary, entry_id) VALUES (?,?,?)"
        )->execute([$clubId, $dictionary, $entryId]);
    }

    /** Przywraca globalny wpis do widoku klubu. */
    public function restore(int $clubId, string $dictionary, int $entryId): void
    {
        $this->db->prepare(
            "DELETE FROM club_dictionary_exclusions WHERE club_id = ? AND dictionary = ? AND entry_id = ?"
        )->execute([$clubId, $dictionary, $entryId]);
    }

    /**
     * Zwraca tablicę wykluczonych entry_id dla danego klubu i słownika.
     * Wartości są rzutowane na int — bezpieczne do użycia w NOT IN.
     */
    public function getExcludedIds(int $clubId, string $dictionary): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT entry_id FROM club_dictionary_exclusions WHERE club_id = ? AND dictionary = ?"
            );
            $stmt->execute([$clubId, $dictionary]);
            return array_map('intval', array_column($stmt->fetchAll(), 'entry_id'));
        } catch (\PDOException) {
            // Tabela jeszcze nie istnieje (migracja nie uruchomiona) — brak wykluczeń
            return [];
        }
    }
}
