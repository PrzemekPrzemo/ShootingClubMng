<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\PdfHelper;
use App\Models\StartListModel;

class StartListController extends BaseController
{
    private StartListModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['admin', 'zarzad', 'instruktor']);
        $this->requireClubContext();
        $this->model = new StartListModel();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->render('startlist/index', [
            'title'      => 'Listy startowe',
            'generators' => $this->model->getGenerators(),
        ]);
    }

    // ── Generator CRUD ────────────────────────────────────────────────────────

    public function create(): void
    {
        $this->render('startlist/generator_form', [
            'title'        => 'Nowy generator list startowych',
            'generator'    => null,
            'mode'         => 'create',
            'competitions' => $this->model->getCompetitionOptions(),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data   = $this->collectGeneratorData();
        $errors = $this->validateGeneratorData($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect('startlist/create');
        }
        $data['club_id']    = $this->currentClub();
        $data['created_by'] = Auth::id();
        $id = $this->model->createGenerator($data);
        Session::flash('success', 'Generator utworzony. Dodaj dyscypliny w kroku 2.');
        $this->redirect("startlist/{$id}/disciplines");
    }

    public function show(string $id): void
    {
        $gen = $this->guardGenerator((int)$id);
        $this->render('startlist/show', [
            'title'           => $gen['name'],
            'generator'       => $gen,
            'disciplineCount' => count($this->model->getDisciplines((int)$id)),
            'comboCount'      => count($this->model->getCombos((int)$id)),
            'catCount'        => count($this->model->getAgeCategories((int)$id)),
            'competitorCount' => count($this->model->getCompetitors((int)$id)),
            'relayCount'      => count($this->model->getRelays((int)$id)),
        ]);
    }

    public function edit(string $id): void
    {
        $gen = $this->guardGenerator((int)$id);
        $this->render('startlist/generator_form', [
            'title'        => 'Edytuj generator',
            'generator'    => $gen,
            'mode'         => 'edit',
            'competitions' => $this->model->getCompetitionOptions(),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $data   = $this->collectGeneratorData();
        $errors = $this->validateGeneratorData($data);
        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            $this->redirect("startlist/{$id}/edit");
        }
        $this->model->updateGenerator((int)$id, $data);
        Session::flash('success', 'Zmiany zapisane.');
        $this->redirect("startlist/{$id}");
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $this->model->deleteGenerator((int)$id);
        Session::flash('success', 'Generator usunięty.');
        $this->redirect('startlist');
    }

    // ── Disciplines ───────────────────────────────────────────────────────────

    public function disciplines(string $id): void
    {
        $gen = $this->guardGenerator((int)$id);
        $this->render('startlist/disciplines', [
            'title'       => 'Dyscypliny — ' . $gen['name'],
            'generator'   => $gen,
            'disciplines' => $this->model->getDisciplines((int)$id),
        ]);
    }

    public function addDiscipline(string $id): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $data = [
            'generator_id'     => (int)$id,
            'name'             => trim($_POST['name'] ?? ''),
            'code'             => strtolower(trim($_POST['code'] ?? '')),
            'duration_minutes' => max(1, (int)($_POST['duration_minutes'] ?? 30)),
            'lanes_count'      => max(1, (int)($_POST['lanes_count'] ?? 4)),
            'gender_mode'      => in_array($_POST['gender_mode'] ?? '', ['open','separate'])
                                    ? $_POST['gender_mode'] : 'open',
            'sort_order'       => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['name'] || !$data['code']) {
            Session::flash('error', 'Nazwa i kod dyscypliny są wymagane.');
        } else {
            try {
                $this->model->addDiscipline($data);
                Session::flash('success', 'Dyscyplina dodana.');
            } catch (\PDOException) {
                Session::flash('error', 'Kod dyscypliny musi być unikalny w tym generatorze.');
            }
        }
        $this->redirect("startlist/{$id}/disciplines");
    }

    public function updateDiscipline(string $id, string $did): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $data = [
            'name'             => trim($_POST['name'] ?? ''),
            'code'             => strtolower(trim($_POST['code'] ?? '')),
            'duration_minutes' => max(1, (int)($_POST['duration_minutes'] ?? 30)),
            'lanes_count'      => max(1, (int)($_POST['lanes_count'] ?? 4)),
            'gender_mode'      => in_array($_POST['gender_mode'] ?? '', ['open','separate'])
                                    ? $_POST['gender_mode'] : 'open',
            'sort_order'       => (int)($_POST['sort_order'] ?? 0),
        ];
        $this->model->updateDiscipline((int)$did, $data);
        Session::flash('success', 'Dyscyplina zaktualizowana.');
        $this->redirect("startlist/{$id}/disciplines");
    }

    public function deleteDiscipline(string $id, string $did): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $this->model->deleteDiscipline((int)$did);
        Session::flash('success', 'Dyscyplina usunięta.');
        $this->redirect("startlist/{$id}/disciplines");
    }

    // ── Combos ────────────────────────────────────────────────────────────────

    public function combos(string $id): void
    {
        $gen = $this->guardGenerator((int)$id);
        $this->render('startlist/combos', [
            'title'       => 'Kombinacje — ' . $gen['name'],
            'generator'   => $gen,
            'combos'      => $this->model->getCombos((int)$id),
            'disciplines' => $this->model->getDisciplines((int)$id),
        ]);
    }

    public function addCombo(string $id): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $name          = trim($_POST['name'] ?? '');
        $maxPerRelay   = max(1, (int)($_POST['max_per_relay'] ?? 8));
        $disciplineIds = array_map('intval', (array)($_POST['discipline_ids'] ?? []));
        if (!$name || count($disciplineIds) < 2) {
            Session::flash('error', 'Podaj nazwę i wybierz co najmniej 2 dyscypliny.');
        } else {
            $this->model->addCombo((int)$id, $name, $maxPerRelay, $disciplineIds);
            Session::flash('success', 'Kombinacja dodana.');
        }
        $this->redirect("startlist/{$id}/combos");
    }

    public function updateCombo(string $id, string $cid): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $name          = trim($_POST['name'] ?? '');
        $maxPerRelay   = max(1, (int)($_POST['max_per_relay'] ?? 8));
        $disciplineIds = array_map('intval', (array)($_POST['discipline_ids'] ?? []));
        $this->model->updateCombo((int)$cid, $name, $maxPerRelay, $disciplineIds);
        Session::flash('success', 'Kombinacja zaktualizowana.');
        $this->redirect("startlist/{$id}/combos");
    }

    public function deleteCombo(string $id, string $cid): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $this->model->deleteCombo((int)$cid);
        Session::flash('success', 'Kombinacja usunięta.');
        $this->redirect("startlist/{$id}/combos");
    }

    // ── Age categories ────────────────────────────────────────────────────────

    public function ageCategories(string $id): void
    {
        $gen = $this->guardGenerator((int)$id);
        $this->render('startlist/age_categories', [
            'title'      => 'Kategorie wiekowe — ' . $gen['name'],
            'generator'  => $gen,
            'categories' => $this->model->getAgeCategories((int)$id),
        ]);
    }

    public function addAgeCategory(string $id): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $data = [
            'generator_id' => (int)$id,
            'name'         => trim($_POST['name'] ?? ''),
            'age_from'     => (int)($_POST['age_from'] ?? 0),
            'age_to'       => (int)($_POST['age_to'] ?? 99),
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['name']) {
            Session::flash('error', 'Nazwa kategorii jest wymagana.');
        } else {
            $this->model->addAgeCategory($data);
            Session::flash('success', 'Kategoria dodana.');
        }
        $this->redirect("startlist/{$id}/age-categories");
    }

    public function updateAgeCategory(string $id, string $cid): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'age_from'   => (int)($_POST['age_from'] ?? 0),
            'age_to'     => (int)($_POST['age_to'] ?? 99),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        $this->model->updateAgeCategory((int)$cid, $data);
        Session::flash('success', 'Kategoria zaktualizowana.');
        $this->redirect("startlist/{$id}/age-categories");
    }

    public function deleteAgeCategory(string $id, string $cid): void
    {
        Csrf::verify();
        $this->guardGenerator((int)$id);
        $this->model->deleteAgeCategory((int)$cid);
        Session::flash('success', 'Kategoria usunięta.');
        $this->redirect("startlist/{$id}/age-categories");
    }

    // ── Import ────────────────────────────────────────────────────────────────

    public function importForm(string $id): void
    {
        $gen = $this->guardGenerator((int)$id);
        $this->render('startlist/import', [
            'title'       => 'Import zawodników — ' . $gen['name'],
            'generator'   => $gen,
            'competitors' => $this->model->getCompetitors((int)$id),
            'disciplines' => $this->model->getDisciplines((int)$id),
            'preview'     => null,
        ]);
    }

    public function importTemplate(string $id): void
    {
        $this->guardGenerator((int)$id);
        $bom = "\xEF\xBB\xBF";
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="szablon_startlist.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, $bom);
        fputcsv($out, ['first_name', 'last_name', 'birth_date', 'gender', 'disciplines'], ';');
        fputcsv($out, ['Jan',   'Kowalski', '1990-05-15', 'M', 'ppn;pst'], ';');
        fputcsv($out, ['Anna',  'Nowak',    '2000-03-20', 'K', 'psp'],     ';');
        fclose($out);
        exit;
    }

    public function importProcess(string $id): void
    {
        Csrf::verify();
        $gen  = $this->guardGenerator((int)$id);
        $file = $_FILES['csv_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Błąd przesyłania pliku. Sprawdź, czy wybrałeś/aś plik CSV.');
            $this->redirect("startlist/{$id}/import");
        }

        $hasHeader  = !empty($_POST['has_header']);
        $action     = $_POST['action'] ?? 'preview';
        $codeMap    = $this->model->getDisciplineCodeMap((int)$id);
        $categories = $this->model->getAgeCategories((int)$id);

        if (empty($codeMap)) {
            Session::flash('error', 'Dodaj najpierw dyscypliny w kroku 2, aby móc przypisać zawodników.');
            $this->redirect("startlist/{$id}/import");
        }

        $rows   = $this->parseCsvFile($file['tmp_name'], $hasHeader);
        $parsed = [];
        foreach ($rows as $row) {
            $parsed[] = $this->mapCsvRow($row, $codeMap, $categories, (int)$id);
        }

        if ($action === 'import') {
            $valid = array_filter($parsed, fn($r) => empty($r['_error']));
            $competitorRows = [];
            foreach ($valid as $r) {
                $competitorRows[] = [
                    'generator_id'    => (int)$id,
                    'first_name'      => $r['first_name'],
                    'last_name'       => $r['last_name'],
                    'birth_date'      => $r['birth_date'] ?: null,
                    'gender'          => $r['gender'] ?: null,
                    'age_category_id' => $r['age_category_id'] ?? null,
                    '_discipline_ids' => $r['discipline_ids'],
                ];
            }
            $this->model->replaceCompetitors((int)$id, $competitorRows);
            $this->model->setGeneratorStatus((int)$id, 'draft');
            $count = count($competitorRows);
            Session::flash('success', "Zaimportowano {$count} zawodników. Wygeneruj harmonogram.");
            $this->redirect("startlist/{$id}/import");
        }

        // Preview mode
        $this->render('startlist/import', [
            'title'       => 'Import zawodników — ' . $gen['name'],
            'generator'   => $gen,
            'competitors' => $this->model->getCompetitors((int)$id),
            'disciplines' => $this->model->getDisciplines((int)$id),
            'preview'     => $parsed,
        ]);
    }

    // ── Generate ──────────────────────────────────────────────────────────────

    public function generate(string $id): void
    {
        Csrf::verify();
        $gen = $this->guardGenerator((int)$id);

        $disciplines = $this->model->getDisciplines((int)$id);
        $combos      = $this->model->getCombos((int)$id);
        $competitors = $this->model->getCompetitors((int)$id);

        if (empty($disciplines)) {
            Session::flash('error', 'Dodaj co najmniej jedną dyscyplinę przed generowaniem.');
            $this->redirect("startlist/{$id}");
        }
        if (empty($competitors)) {
            Session::flash('error', 'Importuj zawodników przed generowaniem.');
            $this->redirect("startlist/{$id}");
        }

        $startDt      = new \DateTime($gen['start_date'] . ' ' . $gen['start_time']);
        $breakMinutes = (int)$gen['break_minutes'];

        [$relays, $entries] = $this->runScheduler(
            $gen, $disciplines, $combos, $competitors, $startDt, $breakMinutes
        );

        $this->model->clearRelays((int)$id);
        $this->model->insertRelays($relays);

        // Map discipline_id + slot_index → relay_id after insert
        $saved       = $this->model->getRelays((int)$id);
        $relayLookup = [];
        foreach ($saved as $r) {
            $relayLookup[$r['discipline_id']][$r['slot_index']] = $r['id'];
        }

        $finalEntries = [];
        foreach ($entries as $e) {
            $relayId = $relayLookup[$e['_discipline_id']][$e['_slot_index']] ?? null;
            if ($relayId === null) continue;
            $finalEntries[] = [
                'relay_id'             => $relayId,
                'competitor_id'        => $e['competitor_id'],
                'actual_discipline_id' => $e['actual_discipline_id'],
                'lane'                 => $e['lane'] ?? null,
            ];
        }
        $this->model->insertRelayEntries($finalEntries);
        $this->model->setGeneratorStatus((int)$id, 'generated');

        $relayCount = count($saved);
        Session::flash('success', "Harmonogram wygenerowany: {$relayCount} zmian.");
        $this->redirect("startlist/{$id}/preview");
    }

    // ── Preview ───────────────────────────────────────────────────────────────

    public function preview(string $id): void
    {
        $gen      = $this->guardGenerator((int)$id);
        $schedule = $this->model->getRelaysGroupedByDiscipline((int)$id);
        $conflicts = $this->detectConflicts((int)$id);

        $this->render('startlist/preview', [
            'title'     => 'Podgląd harmonogramu — ' . $gen['name'],
            'generator' => $gen,
            'schedule'  => $schedule,
            'conflicts' => $conflicts,
        ]);
    }

    // ── PDF export ────────────────────────────────────────────────────────────

    public function exportPdf(string $id): void
    {
        $gen       = $this->guardGenerator((int)$id);
        $schedule  = $this->model->getScheduleForPdf((int)$id);
        $conflicts = $this->detectConflicts((int)$id);

        $html = $this->renderToString('startlist/pdf_layout', [
            'generator' => $gen,
            'schedule'  => $schedule,
            'conflicts' => $conflicts,
        ]);

        $filename = 'lista-startowa-' . date('Y-m-d') . '.pdf';
        PdfHelper::send($html, $filename, 'A4', false);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function guardGenerator(int $id): array
    {
        $gen = $this->model->getGenerator($id);
        if (!$gen) {
            Session::flash('error', 'Generator nie istnieje lub brak dostępu.');
            $this->redirect('startlist');
        }
        return $gen;
    }

    private function collectGeneratorData(): array
    {
        return [
            'name'           => trim($_POST['name'] ?? ''),
            'competition_id' => !empty($_POST['competition_id'])
                                    ? (int)$_POST['competition_id'] : null,
            'start_date'     => $_POST['start_date'] ?? date('Y-m-d'),
            'start_time'     => $_POST['start_time'] ?? '09:00',
            'break_minutes'  => max(0, (int)($_POST['break_minutes'] ?? 10)),
        ];
    }

    private function validateGeneratorData(array $data): array
    {
        $errors = [];
        if (empty($data['name']))       $errors[] = 'Nazwa generatora jest wymagana.';
        if (empty($data['start_date'])) $errors[] = 'Data startu jest wymagana.';
        return $errors;
    }

    private function parseCsvFile(string $filePath, bool $hasHeader): array
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) return [];

        // Strip UTF-8 BOM
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        // Convert Windows-1250 if needed
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1250');
        }

        // Detect delimiter
        $firstLine = strtok($raw, "\n");
        $delim = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $tmp = tempnam(sys_get_temp_dir(), 'sl_csv_');
        file_put_contents($tmp, $raw);
        $handle = fopen($tmp, 'r');
        if (!$handle) { @unlink($tmp); return []; }

        $headers = null;
        $rows    = [];
        while (($line = fgetcsv($handle, 4096, $delim)) !== false) {
            if ($hasHeader && $headers === null) {
                $headers = array_map(fn($h) => strtolower(trim($h)), $line);
                continue;
            }
            if ($headers !== null) {
                $rows[] = array_combine($headers, array_pad($line, count($headers), ''));
            } else {
                $rows[] = [
                    'first_name'  => $line[0] ?? '',
                    'last_name'   => $line[1] ?? '',
                    'birth_date'  => $line[2] ?? '',
                    'gender'      => $line[3] ?? '',
                    'disciplines' => $line[4] ?? '',
                ];
            }
        }
        fclose($handle);
        @unlink($tmp);
        return $rows;
    }

    private function mapCsvRow(array $row, array $codeMap, array $categories, int $generatorId): array
    {
        $entry = [
            'first_name'         => trim($row['first_name']  ?? $row[0] ?? ''),
            'last_name'          => trim($row['last_name']   ?? $row[1] ?? ''),
            'birth_date'         => trim($row['birth_date']  ?? $row[2] ?? ''),
            'gender'             => strtoupper(trim($row['gender'] ?? $row[3] ?? '')),
            'discipline_codes'   => trim($row['disciplines'] ?? $row[4] ?? ''),
            'discipline_ids'     => [],
            'age_category_id'    => null,
            'age_category_name'  => '',
            '_error'             => '',
        ];

        if (!$entry['first_name'] || !$entry['last_name']) {
            $entry['_error'] = 'Brak imienia lub nazwiska';
            return $entry;
        }

        if (!in_array($entry['gender'], ['M', 'K'], true)) {
            $entry['gender'] = '';
        }

        // Resolve discipline codes
        if ($entry['discipline_codes'] !== '') {
            $codes   = preg_split('/[;,\s]+/', strtolower($entry['discipline_codes']), -1, PREG_SPLIT_NO_EMPTY);
            $unknown = [];
            foreach ($codes as $code) {
                if (isset($codeMap[$code])) {
                    $entry['discipline_ids'][] = $codeMap[$code];
                } else {
                    $unknown[] = $code;
                }
            }
            if ($unknown) {
                $entry['_error'] = 'Nieznane kody dyscyplin: ' . implode(', ', $unknown);
            }
        }

        if (empty($entry['discipline_ids'])) {
            $entry['_error'] = $entry['_error'] ?: 'Brak przypisanych dyscyplin';
        }

        // Resolve age category
        if (!empty($categories) && $entry['birth_date']) {
            try {
                $age = (int)(new \DateTimeImmutable($entry['birth_date']))
                    ->diff(new \DateTimeImmutable('today'))->y;
                $cat = $this->model->resolveAgeCategory($generatorId, $age);
                if ($cat) {
                    $entry['age_category_id']   = (int)$cat['id'];
                    $entry['age_category_name'] = $cat['name'];
                }
            } catch (\Throwable) {}
        }

        return $entry;
    }

    /**
     * Detect competitors with < 40 min gap between relays.
     */
    private function detectConflicts(int $generatorId): array
    {
        $entries = $this->model->getAllRelayEntries($generatorId);
        $perComp = [];

        foreach ($entries as $e) {
            $perComp[$e['competitor_id']][] = [
                'start'     => new \DateTime($e['relay_start_datetime']),
                'end'       => new \DateTime($e['relay_end_datetime']),
                'disc_name' => $e['discipline_name'],
                'disc_code' => $e['discipline_code'],
                'name'      => $e['last_name'] . ' ' . $e['first_name'],
            ];
        }

        $conflicts = [];
        foreach ($perComp as $slots) {
            if (count($slots) < 2) continue;
            usort($slots, fn($a, $b) => $a['start'] <=> $b['start']);
            for ($i = 0; $i < count($slots) - 1; $i++) {
                $gapMin = ($slots[$i + 1]['start']->getTimestamp()
                           - $slots[$i]['end']->getTimestamp()) / 60;
                if ($gapMin < 40) {
                    $conflicts[] = [
                        'competitor_name' => $slots[$i]['name'],
                        'discipline_a'    => $slots[$i]['disc_code'],
                        'end_a'           => $slots[$i]['end']->format('H:i'),
                        'discipline_b'    => $slots[$i + 1]['disc_code'],
                        'start_b'         => $slots[$i + 1]['start']->format('H:i'),
                        'gap_minutes'     => (int)round($gapMin),
                    ];
                }
            }
        }
        return $conflicts;
    }

    /**
     * Core scheduling algorithm.
     * Returns [relays[], pendingEntries[]]
     */
    private function runScheduler(
        array     $gen,
        array     $disciplines,
        array     $combos,
        array     $competitors,
        \DateTime $startDt,
        int       $breakMinutes
    ): array {
        // ── Build lookups ───────────────────────────────────────────────────

        $discById = [];
        foreach ($disciplines as $d) {
            $discById[$d['id']] = $d;
        }

        // discipline_id → combo row
        $discToCombo = [];
        $comboById   = [];
        foreach ($combos as $c) {
            $comboById[$c['id']] = $c;
            foreach ($c['discipline_ids'] as $did) {
                $discToCombo[$did] = $c;
            }
        }

        // ── Build competitor queues ─────────────────────────────────────────
        // Key: "{discipline_id}|{gender_bucket}|{age_category_id}"
        $queues = [];

        foreach ($competitors as $comp) {
            foreach ($comp['discipline_ids'] as $did) {
                $disc         = $discById[$did] ?? null;
                if (!$disc) continue;
                $genderBucket = ($disc['gender_mode'] === 'separate')
                                ? ($comp['gender'] ?: 'open')
                                : 'open';
                $catId        = $comp['age_category_id'] ?? 0;
                $qKey         = "{$did}|{$genderBucket}|{$catId}";
                $queues[$qKey][] = (int)$comp['id'];
            }
        }

        // ── Build scheduling units (preserve user sort_order) ───────────────
        $units      = [];
        $seenCombos = [];
        usort($disciplines, fn($a, $b) => (int)$a['sort_order'] <=> (int)$b['sort_order']);

        foreach ($disciplines as $d) {
            $did = $d['id'];
            if (isset($discToCombo[$did])) {
                $combo = $discToCombo[$did];
                if (!isset($seenCombos[$combo['id']])) {
                    $seenCombos[$combo['id']] = true;
                    $units[] = ['type' => 'combo', 'combo_id' => (int)$combo['id']];
                }
            } else {
                $units[] = ['type' => 'standalone', 'discipline_id' => (int)$did];
            }
        }

        // ── Timeline ────────────────────────────────────────────────────────
        $currentTime     = clone $startDt;
        $relaysToInsert  = [];
        $entriesToInsert = [];

        foreach ($units as $unit) {

            if ($unit['type'] === 'standalone') {
                $disc     = $discById[$unit['discipline_id']];
                $did      = (int)$disc['id'];
                $lanes    = (int)$disc['lanes_count'];
                $duration = (int)$disc['duration_minutes'];

                // Collect and sort queue keys for this discipline
                $discQueues = [];
                foreach ($queues as $qKey => $qIds) {
                    [$qDid] = explode('|', $qKey);
                    if ((int)$qDid === $did) {
                        $discQueues[$qKey] = $qIds;
                    }
                }
                if (empty($discQueues)) continue;

                ksort($discQueues);
                $flat      = array_merge(...array_values($discQueues));
                $slotIndex = 1;

                foreach (array_chunk($flat, $lanes) as $chunk) {
                    $endTime = (clone $currentTime)->modify("+{$duration} minutes");

                    $relaysToInsert[] = [
                        'generator_id'   => (int)$gen['id'],
                        'discipline_id'  => $did,
                        'combo_id'       => null,
                        'slot_index'     => $slotIndex,
                        'start_datetime' => $currentTime->format('Y-m-d H:i:s'),
                        'end_datetime'   => $endTime->format('Y-m-d H:i:s'),
                    ];

                    $lane = 1;
                    foreach ($chunk as $compId) {
                        $entriesToInsert[] = [
                            '_discipline_id'      => $did,
                            '_slot_index'         => $slotIndex,
                            'competitor_id'        => $compId,
                            'actual_discipline_id' => $did,
                            'lane'                 => $lane++,
                        ];
                    }

                    $currentTime = (clone $endTime)->modify("+{$breakMinutes} minutes");
                    $slotIndex++;
                }

            } else {
                // ── Combo ───────────────────────────────────────────────────
                $combo       = $comboById[$unit['combo_id']];
                $maxPerRelay = (int)$combo['max_per_relay'];
                $comboDids   = $combo['discipline_ids'];

                $comboDuration = 0;
                foreach ($comboDids as $cdid) {
                    $comboDuration = max($comboDuration, (int)($discById[$cdid]['duration_minutes'] ?? 0));
                }
                if ($comboDuration === 0) $comboDuration = 30;

                $primaryDid = $comboDids[0];

                // Merge queues from all combo disciplines; map competitor → actual disc
                $comboQueue = []; // competitor_id => actual_discipline_id
                foreach ($comboDids as $cdid) {
                    foreach ($queues as $qKey => $qIds) {
                        [$qDid] = explode('|', $qKey);
                        if ((int)$qDid !== (int)$cdid) continue;
                        foreach ($qIds as $compId) {
                            if (!isset($comboQueue[$compId])) {
                                $comboQueue[$compId] = (int)$cdid;
                            }
                        }
                    }
                }
                if (empty($comboQueue)) continue;

                $compIds   = array_keys($comboQueue);
                $slotIndex = 1;

                foreach (array_chunk($compIds, $maxPerRelay) as $chunk) {
                    $endTime = (clone $currentTime)->modify("+{$comboDuration} minutes");

                    $relaysToInsert[] = [
                        'generator_id'   => (int)$gen['id'],
                        'discipline_id'  => $primaryDid,
                        'combo_id'       => (int)$combo['id'],
                        'slot_index'     => $slotIndex,
                        'start_datetime' => $currentTime->format('Y-m-d H:i:s'),
                        'end_datetime'   => $endTime->format('Y-m-d H:i:s'),
                    ];

                    $lane = 1;
                    foreach ($chunk as $compId) {
                        $entriesToInsert[] = [
                            '_discipline_id'      => $primaryDid,
                            '_slot_index'         => $slotIndex,
                            'competitor_id'        => $compId,
                            'actual_discipline_id' => $comboQueue[$compId],
                            'lane'                 => $lane++,
                        ];
                    }

                    $currentTime = (clone $endTime)->modify("+{$breakMinutes} minutes");
                    $slotIndex++;
                }
            }
        }

        return [$relaysToInsert, $entriesToInsert];
    }
}
