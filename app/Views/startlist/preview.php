<?php
$statusBadge = match($generator['status']) {
    'generated' => ['bg-success',   'Wygenerowany'],
    'published' => ['bg-primary',   'Opublikowany'],
    default     => ['bg-secondary', 'Szkic'],
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
        <span class="badge <?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('startlist/' . $generator['id'] . '/export.pdf') ?>"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-pdf"></i> Pobierz PDF
        </a>
    </div>
</div>

<!-- Conflict alerts -->
<?php if (!empty($conflicts)): ?>
<div class="alert alert-warning mb-3">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <strong>Wykryto <?= count($conflicts) ?> konflikt<?= count($conflicts) === 1 ? '' : (count($conflicts) < 5 ? 'y' : 'ów') ?> czasowych (przerwa &lt; 40 min)</strong>
    </div>
    <table class="table table-sm table-warning mb-0">
        <thead>
            <tr>
                <th>Zawodnik</th>
                <th>Dyscyplina 1 (koniec)</th>
                <th>Dyscyplina 2 (start)</th>
                <th>Przerwa</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($conflicts as $cf): ?>
            <tr>
                <td><?= e($cf['competitor_name']) ?></td>
                <td><code><?= e($cf['discipline_a']) ?></code> do <?= e($cf['end_a']) ?></td>
                <td><code><?= e($cf['discipline_b']) ?></code> od <?= e($cf['start_b']) ?></td>
                <td class="text-danger fw-bold"><?= $cf['gap_minutes'] ?> min</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif (!empty($schedule)): ?>
<div class="alert alert-success mb-3 py-2">
    <i class="bi bi-check-circle-fill"></i> Brak konfliktów czasowych — wszyscy zawodnicy mają co najmniej 40 min przerwy między startami.
</div>
<?php endif; ?>

<!-- Schedule -->
<?php if (empty($schedule)): ?>
<div class="alert alert-info">
    Brak wygenerowanego harmonogramu. <a href="<?= url('startlist/' . $generator['id']) ?>">Wróć do kroku 6 i wygeneruj harmonogram.</a>
</div>
<?php else: ?>

<div class="accordion" id="scheduleAccordion">
<?php foreach ($schedule as $gi => $group):
    $disc      = $group['discipline'];
    $relayList = $group['relays'];
    $headerId  = 'head-' . $disc['id'];
    $collapseId = 'collapse-' . $disc['id'];
    $totalComp = array_sum(array_map(fn($r) => count($r['entries']), $relayList));
?>
<div class="accordion-item border mb-2">
    <h2 class="accordion-header" id="<?= $headerId ?>">
        <button class="accordion-button <?= $gi > 0 ? 'collapsed' : '' ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>"
                aria-expanded="<?= $gi === 0 ? 'true' : 'false' ?>"
                aria-controls="<?= $collapseId ?>">
            <span class="fw-bold me-2"><?= e($disc['name']) ?></span>
            <code class="me-2"><?= e($disc['code']) ?></code>
            <span class="badge bg-secondary me-2"><?= count($relayList) ?> zmian<?= count($relayList) === 1 ? 'a' : (count($relayList) < 5 ? 'y' : '') ?></span>
            <span class="badge bg-info text-dark me-2"><?= $totalComp ?> zawodników</span>
            <?php if ($disc['gender_mode'] === 'separate'): ?>
            <span class="badge bg-light text-dark border">M / K osobno</span>
            <?php endif; ?>
        </button>
    </h2>
    <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $gi === 0 ? 'show' : '' ?>"
         aria-labelledby="<?= $headerId ?>" data-bs-parent="#scheduleAccordion">
        <div class="accordion-body p-0">
            <?php foreach ($relayList as $ri => $slot):
                $relay   = $slot['relay'];
                $entries = $slot['entries'];
                $startFmt = date('H:i', strtotime($relay['start_datetime']));
                $endFmt   = date('H:i', strtotime($relay['end_datetime']));
                $dateFmt  = date('d.m.Y', strtotime($relay['start_datetime']));
            ?>
            <div class="border-bottom px-3 py-2 <?= $ri % 2 === 0 ? 'bg-light' : '' ?>">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-dark">Zmiana <?= $relay['slot_index'] ?></span>
                    <span class="fw-semibold"><?= $dateFmt ?></span>
                    <span class="text-danger fw-bold"><?= $startFmt ?> &ndash; <?= $endFmt ?></span>
                    <span class="text-muted small">(<?= (int)$relay['lanes_count'] ?> stanowisk)</span>
                    <span class="badge bg-secondary ms-auto"><?= count($entries) ?> osób</span>
                </div>

                <?php if (empty($entries)): ?>
                <span class="text-muted small">Brak zawodników w tej zmianie.</span>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-xs table-sm mb-0" style="font-size:.82rem">
                        <thead>
                            <tr class="table-secondary">
                                <th style="width:3rem">Stan.</th>
                                <th>Nazwisko i imię</th>
                                <th style="width:3.5rem">Płeć</th>
                                <?php if ($disc['gender_mode'] === 'separate' || !empty($relay['combo_id'])): ?>
                                <th>Dyscyplina</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td class="text-center fw-bold"><?= (int)$entry['lane'] ?></td>
                                <td><?= e($entry['last_name'] . ' ' . $entry['first_name']) ?></td>
                                <td><?= e($entry['gender'] ?? '') ?></td>
                                <?php if ($disc['gender_mode'] === 'separate' || !empty($relay['combo_id'])): ?>
                                <td><code><?= e($entry['discipline_code'] ?? $disc['code']) ?></code></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Summary -->
<div class="mt-3 text-end">
    <a href="<?= url('startlist/' . $generator['id'] . '/export.pdf') ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-file-pdf"></i> Pobierz PDF
    </a>
    <a href="<?= url('startlist/' . $generator['id']) ?>"
       class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left"></i> Wróć do wizarda
    </a>
</div>

<?php endif; ?>
