<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('competitions') ?>">Zawody</a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id']) ?>"><?= e($competition['name']) ?></a></li>
        <li class="breadcrumb-item"><a href="<?= url('competitions/' . $competition['id'] . '/events') ?>">Konkurencje</a></li>
        <li class="breadcrumb-item active"><?= e($event['name']) ?></li>
    </ol>
</nav>

<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0">Wyniki: <?= e($event['name']) ?></h2>
    <?php if ($event['shots_count']): ?>
        <span class="badge bg-secondary"><?= $event['shots_count'] ?> strzałów</span>
    <?php endif; ?>
    <?php $stMap = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Trafiony/Chybiony']; ?>
    <span class="badge bg-light text-dark border"><?= $stMap[$event['scoring_type']] ?? '' ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/startcard') ?>"
           target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer"></i> Lista startowa (A4)
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/scorecards') ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-person"></i> Metryczki A5
        </a>
    </div>
</div>

<form method="post" action="<?= url('competitions/' . $competition['id'] . '/events/' . $event['id'] . '/results/save') ?>">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Wprowadź wyniki zawodników</strong>
            <small class="text-muted">Pozostaw puste pola dla zawodników bez wyniku</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Zawodnik</th>
                            <th>Nr leg.</th>
                            <th>Klasa</th>
                            <th>Wynik</th>
                            <?php if ($event['scoring_type'] === 'decimal'): ?>
                            <th title="Wewnętrzne 10 / X-count">X</th>
                            <?php endif; ?>
                            <th>Miejsce</th>
                            <th>Uwagi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Merge registered entries + any extras already in results
                    $shownIds = [];
                    foreach ($entries as $entry):
                        $shownIds[] = $entry['member_id'];
                        $r = $resultsMap[$entry['member_id']] ?? null;
                    ?>
                        <input type="hidden" name="member_id[]" value="<?= $entry['member_id'] ?>">
                        <tr>
                            <td>
                                <a href="<?= url('members/' . $entry['member_id']) ?>">
                                    <?= e($entry['last_name']) ?> <?= e($entry['first_name']) ?>
                                </a>
                            </td>
                            <td class="small text-muted"><?= e($entry['member_number']) ?></td>
                            <td class="small">
                                <?= e($entry['class'] ?? '') ?>
                                <?php if (!empty($entry['member_class_name'] ?? '')): ?>
                                    <span class="badge bg-info text-dark small"><?= e($entry['member_class_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="width:110px">
                                <input type="number" name="score[]"
                                       step="<?= $event['scoring_type'] === 'decimal' ? '0.1' : '1' ?>"
                                       class="form-control form-control-sm"
                                       value="<?= $r ? e($r['score'] ?? '') : '' ?>">
                            </td>
                            <?php if ($event['scoring_type'] === 'decimal'): ?>
                            <td style="width:60px">
                                <input type="number" name="score_inner[]" min="0" max="99"
                                       class="form-control form-control-sm"
                                       value="<?= $r ? e($r['score_inner'] ?? '') : '' ?>">
                            </td>
                            <?php else: ?>
                            <input type="hidden" name="score_inner[]" value="">
                            <?php endif; ?>
                            <td style="width:80px">
                                <input type="number" name="place[]" min="1"
                                       class="form-control form-control-sm"
                                       value="<?= $r ? e($r['place'] ?? '') : '' ?>">
                            </td>
                            <td>
                                <input type="text" name="notes[]" class="form-control form-control-sm"
                                       value="<?= $r ? e($r['notes'] ?? '') : '' ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    // Show results for non-registered members (entered directly)
                    foreach ($resultsMap as $memberId => $r):
                        if (in_array($memberId, $shownIds)) continue;
                    ?>
                        <input type="hidden" name="member_id[]" value="<?= $memberId ?>">
                        <tr class="table-warning">
                            <td>
                                <a href="<?= url('members/' . $memberId) ?>">
                                    <?= e($r['last_name']) ?> <?= e($r['first_name']) ?>
                                </a>
                                <small class="text-muted">(niezgłoszony)</small>
                            </td>
                            <td class="small text-muted"><?= e($r['member_number']) ?></td>
                            <td class="small">
                                <?php if (!empty($r['member_class_name'])): ?>
                                    <span class="badge bg-info text-dark small"><?= e($r['member_class_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="number" name="score[]"
                                       step="<?= $event['scoring_type'] === 'decimal' ? '0.1' : '1' ?>"
                                       class="form-control form-control-sm"
                                       value="<?= e($r['score'] ?? '') ?>">
                            </td>
                            <?php if ($event['scoring_type'] === 'decimal'): ?>
                            <td>
                                <input type="number" name="score_inner[]" min="0" max="99"
                                       class="form-control form-control-sm"
                                       value="<?= e($r['score_inner'] ?? '') ?>">
                            </td>
                            <?php else: ?>
                            <input type="hidden" name="score_inner[]" value="">
                            <?php endif; ?>
                            <td>
                                <input type="number" name="place[]" min="1"
                                       class="form-control form-control-sm"
                                       value="<?= e($r['place'] ?? '') ?>">
                            </td>
                            <td>
                                <input type="text" name="notes[]" class="form-control form-control-sm"
                                       value="<?= e($r['notes'] ?? '') ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($entries) && empty($resultsMap)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">
                            Brak zgłoszonych zawodników.
                            <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>">Dodaj zgłoszenia</a>.
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-check-lg"></i> Zapisz wyniki
        </button>
        <a href="<?= url('competitions/' . $competition['id'] . '/events') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
