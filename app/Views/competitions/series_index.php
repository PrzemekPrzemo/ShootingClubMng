<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('competitions') ?>">Zawody</a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id']) ?>"><?= e($competition['name']) ?></a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id'] . '/events') ?>">Konkurencje</a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/results') ?>"><?= e($event['name']) ?></a></li>
        <li class="breadcrumb-item active">Serie</li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0">
        <i class="bi bi-grid-3x3"></i>
        Serie z metryczki: <?= e($event['name']) ?>
    </h2>
    <?php if ($event['shots_count']): ?>
        <span class="badge bg-secondary"><?= $event['shots_count'] ?> strzałów</span>
    <?php endif; ?>
    <?php $stMap = ['decimal' => 'Dziesiętna', 'integer' => 'Całkowita', 'hit_miss' => 'Traf/Chyb']; ?>
    <span class="badge bg-light text-dark border"><?= $stMap[$event['scoring_type']] ?? '' ?></span>
    <span class="badge bg-info text-dark"><?= $serieCount ?> serii</span>
</div>

<?php if (empty($entries)): ?>
    <div class="alert alert-info">
        Brak zgłoszonych zawodników do tej konkurencji.
        <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>">Zarządzaj zgłoszeniami</a>.
    </div>
<?php else: ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Zawodnicy — status wpisania serii</strong>
        <small class="text-muted">
            Kliknij "Wpisz serie" by przepisać dane z papierowej metryczki dla zawodnika
        </small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Zawodnik</th>
                        <th>Nr leg.</th>
                        <th>Klasa</th>
                        <th>Oficjalny wynik</th>
                        <th>Wpisane serie</th>
                        <th>Suma serii</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry):
                    $mid      = (int)$entry['member_id'];
                    $status   = $seriesStatus[$mid] ?? null;
                    $official = $resultsMap[$mid]   ?? null;

                    $seriesCount   = $status ? $status['series_count']   : 0;
                    $computedTotal = $status ? (float)$status['computed_total'] : null;
                    $totalX        = $status ? (int)$status['total_x']   : 0;
                    $officialScore = $official ? (float)$official['score'] : null;

                    // Determine status badge
                    if ($seriesCount === 0) {
                        $badge = '<span class="badge bg-secondary">nie wpisano</span>';
                    } elseif ($seriesCount < $serieCount) {
                        $badge = '<span class="badge bg-warning text-dark">' . $seriesCount . '/' . $serieCount . ' serii</span>';
                    } elseif ($officialScore !== null && abs($computedTotal - $officialScore) > 0.01) {
                        $badge = '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill"></i> rozbieżność</span>';
                    } else {
                        $badge = '<span class="badge bg-success"><i class="bi bi-check-lg"></i> zgodny</span>';
                    }
                ?>
                <tr>
                    <td>
                        <a href="<?= url('members/' . $mid) ?>">
                            <?= e($entry['last_name']) ?> <?= e($entry['first_name']) ?>
                        </a>
                    </td>
                    <td class="small text-muted"><?= e($entry['member_number']) ?></td>
                    <td class="small">
                        <?= e($entry['class'] ?? '') ?>
                        <?php if (!empty($entry['member_class_name'])): ?>
                            <span class="badge bg-info text-dark small"><?= e($entry['member_class_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($officialScore !== null): ?>
                            <strong><?= number_format($officialScore, ($event['scoring_type'] === 'decimal') ? 1 : 0) ?></strong>
                            <?php if ($official['score_inner'] !== null): ?>
                                <span class="text-muted">(X: <?= (int)$official['score_inner'] ?>)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-center">
                        <?php if ($seriesCount > 0): ?>
                            <?= $seriesCount ?>/<?= $serieCount ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($computedTotal !== null): ?>
                            <?= number_format($computedTotal, ($event['scoring_type'] === 'decimal') ? 1 : 0) ?>
                            <?php if ($event['scoring_type'] === 'decimal' && $totalX > 0): ?>
                                <span class="text-muted">(X: <?= $totalX ?>)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $badge ?></td>
                    <td>
                        <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/series/' . $mid) ?>"
                           class="btn btn-sm <?= $seriesCount > 0 ? 'btn-outline-primary' : 'btn-outline-secondary' ?>">
                            <i class="bi bi-pencil-square"></i>
                            <?= $seriesCount > 0 ? 'Popraw' : 'Wpisz' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Summary: any discrepancies?
$discrepancies = 0;
$missing = 0;
foreach ($entries as $entry) {
    $mid    = (int)$entry['member_id'];
    $status = $seriesStatus[$mid] ?? null;
    $official = $resultsMap[$mid] ?? null;
    if (!$status || $status['series_count'] < $serieCount) {
        $missing++;
    } elseif ($official && abs((float)$status['computed_total'] - (float)$official['score']) > 0.01) {
        $discrepancies++;
    }
}
if ($discrepancies > 0 || $missing > 0): ?>
<div class="alert alert-warning mt-3 d-flex gap-2 align-items-center">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <?php if ($discrepancies > 0): ?>
            <strong><?= $discrepancies ?> zawodnik<?= $discrepancies === 1 ? '' : 'ów' ?></strong>
            z rozbieżnością między sumą serii a oficjalnym wynikiem.
        <?php endif; ?>
        <?php if ($missing > 0): ?>
            <strong><?= $missing ?> zawodnik<?= $missing === 1 ? '' : 'ów' ?></strong>
            bez kompletnych serii.
        <?php endif; ?>
    </div>
</div>
<?php elseif (count($entries) > 0 && count($seriesStatus) === count($entries)): ?>
<div class="alert alert-success mt-3">
    <i class="bi bi-check-circle-fill"></i>
    Wszystkie serie wpisane i zgodne z oficjalnymi wynikami.
</div>
<?php endif; ?>

<div class="mt-3">
    <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/results') ?>"
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Wróć do wyników
    </a>
</div>

<?php endif; ?>
