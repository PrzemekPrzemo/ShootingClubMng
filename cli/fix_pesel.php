#!/usr/bin/env php
<?php
/**
 * PESEL fixer — aktualizuje birth_date i gender dla wszystkich zawodników
 * na podstawie numeru PESEL.
 *
 * Użycie:
 *   php cli/fix_pesel.php           — tryb podglądu (dry-run), bez zmian w bazie
 *   php cli/fix_pesel.php --apply   — zapisuje zmiany do bazy
 *   php cli/fix_pesel.php --club=3  — tylko zawodnicy danego klubu
 *
 * Kody wyjścia:
 *   0 — sukces
 *   1 — błąd krytyczny (np. brak połączenia z DB)
 */

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/app/autoload.php';

use App\Helpers\Database;

// ── Argumenty ────────────────────────────────────────────────────────────────

$apply  = in_array('--apply', $argv, true);
$clubId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--club=')) {
        $clubId = (int)substr($arg, 7);
    }
}

// ── Połączenie z bazą ─────────────────────────────────────────────────────────

try {
    $db = Database::getInstance();
} catch (\Throwable $e) {
    fwrite(STDERR, "BŁĄD: Nie można połączyć się z bazą danych: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Pobierz zawodników z PESEL ────────────────────────────────────────────────

$sql    = "SELECT id, first_name, last_name, member_number, pesel, birth_date, gender, club_id FROM members WHERE pesel IS NOT NULL AND pesel != ''";
$params = [];
if ($clubId !== null) {
    $sql    .= " AND club_id = ?";
    $params[] = $clubId;
}
$sql .= " ORDER BY id";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// ── Statystyki ────────────────────────────────────────────────────────────────

$stats = [
    'total'        => count($members),
    'invalid_pesel'=> 0,
    'updated'      => 0,
    'unchanged'    => 0,
    'skipped'      => 0,
];
$log = [];

// ── Przetwarzanie ─────────────────────────────────────────────────────────────

foreach ($members as $m) {
    $pesel = preg_replace('/\D/', '', $m['pesel'] ?? '');

    if (strlen($pesel) !== 11) {
        $log[] = ['id' => $m['id'], 'nr' => $m['member_number'], 'name' => fullName($m),
                  'status' => 'BŁĄD', 'info' => "Nieprawidłowa długość PESEL: '{$m['pesel']}'"];
        $stats['invalid_pesel']++;
        continue;
    }

    // Walidacja cyfry kontrolnej
    if (!peselChecksum($pesel)) {
        $log[] = ['id' => $m['id'], 'nr' => $m['member_number'], 'name' => fullName($m),
                  'status' => 'BŁĄD', 'info' => "Nieprawidłowa cyfra kontrolna PESEL: {$pesel}"];
        $stats['invalid_pesel']++;
        continue;
    }

    // Ekstrakcja danych
    ['date' => $birthDate, 'gender' => $gender] = peselParse($pesel);

    $currentDate   = $m['birth_date'] ? substr($m['birth_date'], 0, 10) : null;
    $currentGender = $m['gender'] ?? null;

    $needsUpdate = ($currentDate !== $birthDate) || ($currentGender !== $gender);

    if (!$needsUpdate) {
        $log[] = ['id' => $m['id'], 'nr' => $m['member_number'], 'name' => fullName($m),
                  'status' => 'OK', 'info' => "Data: {$birthDate}, Płeć: {$gender} — bez zmian"];
        $stats['unchanged']++;
        continue;
    }

    $changes = [];
    if ($currentDate !== $birthDate) {
        $changes[] = "data urodzenia: " . ($currentDate ?? '∅') . " → {$birthDate}";
    }
    if ($currentGender !== $gender) {
        $changes[] = "płeć: " . ($currentGender ?? '∅') . " → {$gender}";
    }

    if ($apply) {
        $upd = $db->prepare("UPDATE members SET birth_date = ?, gender = ? WHERE id = ?");
        $upd->execute([$birthDate, $gender, $m['id']]);
        $log[] = ['id' => $m['id'], 'nr' => $m['member_number'], 'name' => fullName($m),
                  'status' => 'ZAKTUALIZOWANY', 'info' => implode('; ', $changes)];
        $stats['updated']++;
    } else {
        $log[] = ['id' => $m['id'], 'nr' => $m['member_number'], 'name' => fullName($m),
                  'status' => 'DO_AKTUALIZACJI', 'info' => implode('; ', $changes)];
        $stats['skipped']++;
    }
}

// ── Raport ────────────────────────────────────────────────────────────────────

$mode = $apply ? '[TRYB ZAPISU]' : '[DRY-RUN — brak zmian w bazie, dodaj --apply aby zapisać]';
echo "\n";
echo "========================================================\n";
echo " Skrypt naprawy PESEL — {$mode}\n";
if ($clubId !== null) {
    echo " Zakres: klub ID {$clubId}\n";
}
echo "========================================================\n\n";

// Tabela wyników
$colWidths = [6, 12, 30, 16, 0];
$header = ['ID', 'Nr członka', 'Imię i nazwisko', 'Status', 'Szczegóły'];
printRow($header, $colWidths);
echo str_repeat('-', 100) . "\n";

foreach ($log as $row) {
    $statusColor = match($row['status']) {
        'ZAKTUALIZOWANY', 'DO_AKTUALIZACJI' => "\033[33m",
        'OK'                                 => "\033[32m",
        'BŁĄD'                               => "\033[31m",
        default                              => '',
    };
    $reset = "\033[0m";
    printRow(
        [$row['id'], $row['nr'], $row['name'], $statusColor . $row['status'] . $reset, $row['info']],
        $colWidths
    );
}

echo "\n";
echo "========================================================\n";
echo " Podsumowanie\n";
echo "========================================================\n";
echo " Łącznie przetworzonych:   {$stats['total']}\n";
echo " Nieprawidłowy PESEL:      {$stats['invalid_pesel']}\n";
echo " Bez zmian (dane OK):      {$stats['unchanged']}\n";
if ($apply) {
    echo " Zaktualizowanych:         {$stats['updated']}\n";
} else {
    echo " Do aktualizacji:          {$stats['skipped']} (uruchom z --apply aby zapisać)\n";
}
echo "========================================================\n\n";

exit(0);

// ── Funkcje pomocnicze ────────────────────────────────────────────────────────

/**
 * Parsuje PESEL i zwraca datę urodzenia (YYYY-MM-DD) oraz płeć ('M' lub 'K').
 * Zakłada, że PESEL jest prawidłowy (11 cyfr, cyfra kontrolna OK).
 */
function peselParse(string $pesel): array
{
    $yy = (int)substr($pesel, 0, 2);
    $mm = (int)substr($pesel, 2, 2);
    $dd = (int)substr($pesel, 4, 2);
    $g  = (int)substr($pesel, 9, 1); // cyfra płci

    // Dekodowanie stulecia na podstawie miesiąca
    if ($mm >= 81 && $mm <= 92) {
        $year  = 1800 + $yy;
        $month = $mm - 80;
    } elseif ($mm >= 1 && $mm <= 12) {
        $year  = 1900 + $yy;
        $month = $mm;
    } elseif ($mm >= 21 && $mm <= 32) {
        $year  = 2000 + $yy;
        $month = $mm - 20;
    } elseif ($mm >= 41 && $mm <= 52) {
        $year  = 2100 + $yy;
        $month = $mm - 40;
    } elseif ($mm >= 61 && $mm <= 72) {
        $year  = 2200 + $yy;
        $month = $mm - 60;
    } else {
        // Nieznany zakres — fallback
        $year  = 1900 + $yy;
        $month = $mm;
    }

    $birthDate = sprintf('%04d-%02d-%02d', $year, $month, $dd);
    $gender    = ($g % 2 === 1) ? 'M' : 'K'; // nieparzysta = mężczyzna

    return ['date' => $birthDate, 'gender' => $gender];
}

/**
 * Sprawdza cyfrę kontrolną PESEL.
 * Wagi: 1, 3, 7, 9, 1, 3, 7, 9, 1, 3, 1
 * Suma iloczynów mod 10 musi być równa 0.
 */
function peselChecksum(string $pesel): bool
{
    $weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3, 1];
    $sum = 0;
    for ($i = 0; $i < 11; $i++) {
        $sum += (int)$pesel[$i] * $weights[$i];
    }
    return ($sum % 10) === 0;
}

function fullName(array $m): string
{
    return trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
}

function printRow(array $cols, array $widths): void
{
    $line = '';
    foreach ($cols as $i => $col) {
        $w = $widths[$i] ?? 0;
        if ($w > 0) {
            // Uwzględnij kody ANSI przy obliczaniu długości
            $visibleLen = mb_strlen(preg_replace('/\033\[[0-9;]*m/', '', (string)$col));
            $pad = max(0, $w - $visibleLen);
            $line .= $col . str_repeat(' ', $pad) . ' ';
        } else {
            $line .= $col;
        }
    }
    echo $line . "\n";
}
