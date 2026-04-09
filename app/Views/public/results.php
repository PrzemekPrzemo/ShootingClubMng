<?php
$stMap = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Traf./Chyb.'];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= url('pub/' . $slug . '/competitions') ?>"><?= e($club['name']) ?></a>
        </li>
        <li class="breadcrumb-item active"><?= e($competition['name']) ?></li>
    </ol>
</nav>

<div class="text-center mb-4">
    <h2 class="fw-bold"><?= e($competition['name']) ?></h2>
    <p class="text-muted">
        <i class="bi bi-calendar3"></i>
        <?= date('d.m.Y', strtotime($competition['competition_date'])) ?>
        <?php if ($competition['location']): ?>
            &nbsp;|&nbsp; <i class="bi bi-geo-alt"></i> <?= e($competition['location']) ?>
        <?php endif; ?>
        &nbsp;|&nbsp; <?= e($club['name']) ?>
    </p>
</div>

<?php if (empty($rankings)): ?>
    <div class="alert alert-info">Brak wyników do wyświetlenia.</div>
<?php else: ?>
    <?php foreach ($rankings as $block): ?>
        <?php $ev = $block['event']; $rows = $block['results']; ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?= e($ev['name']) ?>
                    <?php if ($ev['shots_count']): ?>
                        <small class="text-muted fw-normal">— <?= (int)$ev['shots_count'] ?> strzałów</small>
                    <?php endif; ?>
                    <small class="text-muted fw-normal">(<?= $stMap[$ev['scoring_type']] ?? $ev['scoring_type'] ?>)</small>
                </h5>
            </div>
            <?php if (empty($rows)): ?>
                <div class="card-body"><p class="text-muted mb-0">Brak wyników.</p></div>
            <?php else: ?>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px" class="text-center">Msc.</th>
                            <th>Zawodnik</th>
                            <th style="width:80px" class="text-center">Klasa</th>
                            <th style="width:100px" class="text-center">Wynik</th>
                            <?php if ($ev['scoring_type'] === 'decimal'): ?>
                            <th style="width:60px" class="text-center" title="X-count">X</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr <?= $r['calc_place'] <= 3 ? 'class="table-warning"' : '' ?>>
                            <td class="text-center fw-bold">
                                <?php if ($r['calc_place'] === 1): ?>
                                    <i class="bi bi-trophy-fill text-warning"></i>
                                <?php elseif ($r['calc_place'] <= 3): ?>
                                    <?= $r['calc_place'] ?>.
                                <?php else: ?>
                                    <?= $r['calc_place'] ?>.
                                <?php endif; ?>
                            </td>
                            <td><?= e($r['last_name'] . ' ' . $r['first_name']) ?></td>
                            <td class="text-center text-muted small">
                                <?= e(($r['entry_class'] ?? '') . ($r['member_class_code'] ? ' ' . $r['member_class_code'] : '')) ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?= $r['score'] !== null
                                    ? number_format((float)$r['score'], $ev['scoring_type'] === 'decimal' ? 2 : 0, ',', '')
                                    : '—' ?>
                            </td>
                            <?php if ($ev['scoring_type'] === 'decimal'): ?>
                            <td class="text-center text-muted small"><?= $r['score_inner'] ?? '—' ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="text-muted small text-end mt-2">
    Wyniki opublikowane: <?= date('d.m.Y') ?>
    &mdash; <a href="<?= url('pub/' . $slug . '/competitions') ?>">Powrót do listy zawodów</a>
</div>
