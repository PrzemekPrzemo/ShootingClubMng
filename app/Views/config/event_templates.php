<?php
$stMap = ['decimal' => 'Dziesiętna', 'integer' => 'Całkowita', 'hit_miss' => 'Traf./Chyb.'];
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-list-check"></i> Szablony konkurencji</h2>
</div>

<div class="alert alert-info small py-2 mb-3">
    <i class="bi bi-info-circle"></i>
    Szablony służą do szybkiego dodawania konkurencji przy tworzeniu zawodów (przycisk <strong>Wstaw z szablonu</strong>).
    Są pogrupowane według dyscyplin. Kliknij <strong>Zarządzaj</strong> przy dyscyplinie, aby dodawać, edytować i usuwać szablony.
</div>

<?php if (empty($disciplines)): ?>
    <div class="alert alert-warning">
        Brak zdefiniowanych dyscyplin. <a href="<?= url('config/disciplines') ?>">Dodaj dyscyplinę</a>, aby móc tworzyć szablony.
    </div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($disciplines as $d): ?>
        <?php
        $templates = $byDiscipline[$d['id']] ?? [];
        $count     = count($templates);
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 <?= $d['is_active'] ? '' : 'border-secondary opacity-75' ?>">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <div>
                        <strong><?= e($d['name']) ?></strong>
                        <code class="ms-1 small text-muted"><?= e($d['short_code']) ?></code>
                        <?php if (!$d['is_active']): ?>
                            <span class="badge bg-secondary ms-1">nieaktywna</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('config/disciplines/' . $d['id'] . '/templates') ?>"
                       class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-pencil-square"></i> Zarządzaj
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($templates)): ?>
                        <p class="text-muted small px-3 py-2 mb-0">Brak aktywnych szablonów.</p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                            <?php foreach ($templates as $t): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="fw-semibold"><?= e($t['name']) ?></span>
                                    </td>
                                    <td class="text-muted small text-center" style="width:50px">
                                        <?= $t['shots_count'] ?? '—' ?>
                                    </td>
                                    <td class="text-end pe-3" style="width:90px">
                                        <span class="badge bg-light text-dark border small">
                                            <?= $stMap[$t['scoring_type']] ?? $t['scoring_type'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="card-footer py-1 text-end">
                    <small class="text-muted"><?= $count ?> <?= $count === 1 ? 'szablon' : ($count < 5 ? 'szablony' : 'szablonów') ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
