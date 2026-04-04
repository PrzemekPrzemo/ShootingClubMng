<?php
$statusLabels = ['planowane'=>'Planowane','otwarte'=>'Otwarte','zamkniete'=>'Zamknięte','zakonczone'=>'Zakończone'];
$statusColors = ['planowane'=>'secondary','otwarte'=>'success','zamkniete'=>'warning','zakonczone'=>'dark'];
$status       = $competition['status'] ?? 'planowane';
$stMap        = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Traf./Chyb.'];
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">Rankingi — <?= e($competition['name']) ?></h2>
    <span class="badge bg-secondary"><?= format_date($competition['competition_date']) ?></span>
    <span class="badge bg-<?= $statusColors[$status] ?? 'secondary' ?>"><?= $statusLabels[$status] ?? $status ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('competitions/' . $competition['id'] . '/protocol') ?>"
           target="_blank"
           class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer"></i> Drukuj protokół
        </a>
    </div>
</div>

<?php if (empty($rankings)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i>
        Brak konkurencji lub wyników dla tych zawodów.
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
    <?php foreach ($rankings as $block): ?>
        <?php $ev = $block['event']; $rows = $block['results']; ?>
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <strong><?= e($ev['name']) ?></strong>
                <?php if ($ev['shots_count']): ?>
                    <span class="badge bg-light text-dark border"><?= $ev['shots_count'] ?> strzałów</span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-1 small"><?= $stMap[$ev['scoring_type']] ?? $ev['scoring_type'] ?></span>
                <span class="badge bg-light text-muted border ms-auto"><?= count($rows) ?> wyników</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($rows)): ?>
                    <p class="text-muted p-3 mb-0 small">Brak wprowadzonych wyników.</p>
                <?php else: ?>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">Miejsce</th>
                            <th>Zawodnik</th>
                            <th style="width:60px" class="text-center">Nr</th>
                            <th style="width:100px">Klasa</th>
                            <th style="width:90px">Wynik</th>
                            <th style="width:50px" title="X-count">X</th>
                            <th style="width:80px">Broń</th>
                            <th>Uwagi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php $p = $r['calc_place']; ?>
                        <tr class="<?= $p === 1 ? 'table-warning' : ($p === 2 ? 'table-secondary' : ($p === 3 ? 'table-danger' : '')) ?>">
                            <td class="text-center fw-bold">
                                <?php if ($p === 1): ?>🥇
                                <?php elseif ($p === 2): ?>🥈
                                <?php elseif ($p === 3): ?>🥉
                                <?php else: ?><?= $p ?>.
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($r['last_name']) ?> <?= e($r['first_name']) ?>
                                <?php if ($r['group_name']): ?>
                                    <span class="badge bg-secondary ms-1 small"><?= e($r['group_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-muted small"><?= e($r['member_number']) ?></td>
                            <td>
                                <?php if ($r['entry_class']): ?>
                                    <span class="badge bg-light border text-dark"><?= e($r['entry_class']) ?></span>
                                <?php endif; ?>
                                <?php if ($r['member_class_code']): ?>
                                    <span class="badge bg-info text-dark"><?= e($r['member_class_code']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold">
                                <?= $r['score'] !== null
                                    ? number_format((float)$r['score'], $ev['scoring_type'] === 'decimal' ? 2 : 0, ',', '')
                                    : '—' ?>
                            </td>
                            <td class="text-muted">
                                <?= $ev['scoring_type'] === 'decimal' ? ($r['score_inner'] ?? '—') : '—' ?>
                            </td>
                            <td>
                                <?php if ($r['weapon_type']): ?>
                                    <span class="badge bg-<?= $r['weapon_type'] === 'klubowa' ? 'secondary' : 'light border text-dark' ?> small">
                                        <?= $r['weapon_type'] === 'klubowa' ? 'Klub.' : 'Własna' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= e($r['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
