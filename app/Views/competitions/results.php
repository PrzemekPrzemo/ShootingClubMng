<?php
$statusLabels = ['planowane'=>'Planowane','otwarte'=>'Otwarte','zamkniete'=>'Zamknięte','zakonczone'=>'Zakończone'];
$statusColors = ['planowane'=>'secondary','otwarte'=>'success','zamkniete'=>'warning','zakonczone'=>'dark'];
$status = $competition['status'] ?? 'planowane';
$stMap  = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Traf./Chyb.'];
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">Wyniki — <?= e($competition['name']) ?></h2>
    <span class="badge bg-secondary"><?= format_date($competition['competition_date']) ?></span>
    <span class="badge bg-<?= $statusColors[$status] ?? 'secondary' ?>">
        <?= $locked ? '<i class="bi bi-lock-fill me-1"></i>' : '' ?>
        <?= $statusLabels[$status] ?? $status ?>
    </span>
</div>

<?php if ($locked): ?>
    <?php if ($canEdit): ?>
        <!-- Admin: unlock button -->
        <div class="alert alert-warning d-flex align-items-center justify-content-between py-2 mb-3">
            <span><i class="bi bi-lock-fill me-2"></i>Zawody zamknięte. Jako administrator możesz edytować wyniki lub odblokować zawody.</span>
            <form method="post" action="<?= url('competitions/' . $competition['id'] . '/unlock') ?>" class="ms-3">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-warning" onclick="return confirm('Zmienić status zawodów na Otwarte?')">
                    <i class="bi bi-unlock"></i> Odblokuj zawody
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-3">
            <i class="bi bi-lock-fill"></i>
            Zawody zamknięte — wyniki tylko do odczytu. Edycja możliwa wyłącznie przez administratora.
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (empty($entriesWithEvents)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i>
        Brak zgłoszeń z przypisanymi konkurencjami.
        <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>">Zarządzaj zgłoszeniami</a>, aby przypisać zawodnikom konkurencje.
    </div>
<?php else: ?>
<?php if ($canEdit): ?>
<form method="post" action="<?= url('competitions/' . $competition['id'] . '/results/save') ?>">
    <?= csrf_field() ?>
<?php endif; ?>

    <div class="d-flex flex-column gap-3">
    <?php foreach ($entriesWithEvents as $entry): ?>
        <?php
        $hasResults = array_filter($entry['events'], fn($ev) => $ev['score'] !== null || $ev['place'] !== null);
        ?>
        <!-- Hidden member_id for save -->
        <?php if ($canEdit): ?>
        <input type="hidden" name="member_ids[<?= $entry['entry_id'] ?>]" value="<?= $entry['member_id'] ?>">
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex align-items-center gap-2 py-2">
                <strong><?= e($entry['last_name']) ?> <?= e($entry['first_name']) ?></strong>
                <small class="text-muted"><?= e($entry['member_number']) ?></small>
                <?php if ($entry['entry_class']): ?>
                    <span class="badge bg-light text-dark border"><?= e($entry['entry_class']) ?></span>
                <?php endif; ?>
                <?php if ($entry['member_class_code']): ?>
                    <span class="badge bg-info text-dark"><?= e($entry['member_class_code']) ?></span>
                <?php endif; ?>
                <?php if ($entry['group_name']): ?>
                    <span class="badge bg-secondary ms-auto"><?= e($entry['group_name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($entry['start_fee_paid'])): ?>
                    <span class="badge bg-success ms-auto" title="Opłata uiszczona"><i class="bi bi-cash"></i></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Konkurencja</th>
                            <th class="text-center" style="width:50px">Strzały</th>
                            <th style="width:110px">Wynik</th>
                            <th style="width:60px" title="X-count / wewnętrzne 10">X</th>
                            <th style="width:80px">Miejsce</th>
                            <th>Uwagi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entry['events'] as $ev): ?>
                        <tr>
                            <td>
                                <?= e($ev['event_name']) ?>
                                <span class="badge bg-<?= $ev['weapon_type'] === 'klubowa' ? 'secondary' : 'light border text-dark' ?> ms-1 small">
                                    <?= $ev['weapon_type'] === 'klubowa' ? 'Klub.' : 'Własna' ?>
                                </span>
                            </td>
                            <td class="text-center text-muted small"><?= $ev['shots_count'] ?? '—' ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                    <input type="number" step="0.01" min="0"
                                           name="results[<?= $entry['entry_id'] ?>][<?= $ev['event_id'] ?>][score]"
                                           class="form-control form-control-sm"
                                           value="<?= e($ev['score'] ?? '') ?>"
                                           placeholder="—">
                                <?php else: ?>
                                    <span class="<?= $ev['score'] !== null ? 'fw-bold' : 'text-muted' ?>">
                                        <?= $ev['score'] !== null ? number_format((float)$ev['score'], $ev['scoring_type'] === 'decimal' ? 2 : 0, ',', '') : '—' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ev['scoring_type'] === 'decimal'): ?>
                                    <?php if ($canEdit): ?>
                                        <input type="number" min="0"
                                               name="results[<?= $entry['entry_id'] ?>][<?= $ev['event_id'] ?>][score_inner]"
                                               class="form-control form-control-sm"
                                               value="<?= e($ev['score_inner'] ?? '') ?>"
                                               placeholder="—">
                                    <?php else: ?>
                                        <span class="text-muted"><?= $ev['score_inner'] ?? '—' ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php if ($canEdit): ?>
                                        <input type="hidden" name="results[<?= $entry['entry_id'] ?>][<?= $ev['event_id'] ?>][score_inner]" value="">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canEdit): ?>
                                    <input type="number" min="1"
                                           name="results[<?= $entry['entry_id'] ?>][<?= $ev['event_id'] ?>][place]"
                                           class="form-control form-control-sm"
                                           value="<?= e($ev['place'] ?? '') ?>"
                                           placeholder="—">
                                <?php else: ?>
                                    <span class="<?= $ev['place'] ? 'fw-bold' : 'text-muted' ?>">
                                        <?= $ev['place'] ? $ev['place'] . '.' : '—' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canEdit): ?>
                                    <input type="text"
                                           name="results[<?= $entry['entry_id'] ?>][<?= $ev['event_id'] ?>][notes]"
                                           class="form-control form-control-sm"
                                           value="<?= e($ev['notes'] ?? '') ?>">
                                <?php else: ?>
                                    <span class="text-muted small"><?= e($ev['notes'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($canEdit): ?>
    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-check-lg"></i> Zapisz wyniki
        </button>
        <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
    </form>
    <?php endif; ?>
<?php endif; ?>
