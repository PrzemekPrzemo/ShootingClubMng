<h2 class="h4 mb-4"><i class="bi bi-trophy"></i> Zawody</h2>

<?php if ($openCompetitions): ?>
<div class="card mb-4">
    <div class="card-header"><strong>Otwarte zapisy</strong></div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Zawody</th>
                    <th>Data</th>
                    <th>Dyscyplina</th>
                    <th>Status zapisu</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($openCompetitions as $c): ?>
                <tr>
                    <td>
                        <strong><?= e($c['name']) ?></strong>
                        <?php if ($c['location']): ?><br><small class="text-muted"><?= e($c['location']) ?></small><?php endif; ?>
                    </td>
                    <td class="small"><?= format_date($c['competition_date']) ?></td>
                    <td class="small"><?= e($c['discipline_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($c['entry_id']): ?>
                            <?php $sc = match($c['entry_status']) { 'potwierdzony'=>'success','wycofany'=>'secondary',default=>'warning' }; ?>
                            <span class="badge bg-<?= $sc ?>"><?= e($c['entry_status']) ?></span>
                            <?php if ($c['start_fee_paid']): ?>
                                <span class="badge bg-success ms-1"><i class="bi bi-cash"></i> Opłata</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted small">Niezapisany/a</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($c['entry_id'] && $c['entry_status'] === 'zgloszony'): ?>
                            <form method="post" action="<?= url('portal/entries/' . $c['entry_id'] . '/cancel') ?>"
                                  onsubmit="return confirm('Wycofać zgłoszenie?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary">Wycofaj</button>
                            </form>
                        <?php elseif (!$c['entry_id']): ?>
                            <a href="<?= url('portal/competitions/' . $c['id'] . '/register') ?>"
                               class="btn btn-sm btn-danger">Zapisz się</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Brak zawodów z otwartymi zapisami.</div>
<?php endif; ?>

<?php if ($myEntries): ?>
<div class="card">
    <div class="card-header"><strong>Moje zgłoszenia</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Zawody</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Opłata startowa</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($myEntries as $e): ?>
                <tr>
                    <td><?= e($e['competition_name']) ?></td>
                    <td class="small"><?= format_date($e['competition_date']) ?></td>
                    <td>
                        <?php $sc = match($e['status']) { 'potwierdzony'=>'success','wycofany'=>'secondary','zgloszony'=>'warning',default=>'secondary' }; ?>
                        <span class="badge bg-<?= $sc ?>"><?= e($e['status']) ?></span>
                    </td>
                    <td>
                        <?php if (isset($e['start_fee_paid'])): ?>
                            <span class="badge bg-<?= $e['start_fee_paid'] ? 'success' : 'secondary' ?>">
                                <?= $e['start_fee_paid'] ? 'Zapłacona' : 'Oczekuje' ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($allUpcoming)): ?>
<div class="card mt-4">
    <div class="card-header"><strong><i class="bi bi-search"></i> Nadchodzące zawody — wszystkie kluby</strong></div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Zawody</th>
                    <th>Klub</th>
                    <th>Data</th>
                    <th>Dyscyplina</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allUpcoming as $c): ?>
                <tr>
                    <td>
                        <strong><?= e($c['name']) ?></strong>
                        <?php if ($c['location']): ?><br><small class="text-muted"><?= e($c['location']) ?></small><?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= e($c['club_name'] ?? '—') ?></td>
                    <td class="small"><?= format_date($c['competition_date']) ?></td>
                    <td class="small"><?= e($c['discipline_name'] ?? '—') ?></td>
                    <td>
                        <?php $sc = match($c['status']) { 'otwarte'=>'success', 'planowane'=>'secondary', default=>'warning' }; ?>
                        <span class="badge bg-<?= $sc ?>"><?= e($c['status']) ?></span>
                    </td>
                    <td>
                        <?php if ($c['status'] === 'otwarte' && !$c['entry_id']): ?>
                            <a href="<?= url('portal/competitions/' . $c['id'] . '/register') ?>"
                               class="btn btn-sm btn-danger">Zapisz się</a>
                        <?php elseif ($c['entry_id']): ?>
                            <?php $sc2 = match($c['entry_status']) { 'potwierdzony'=>'success','wycofany'=>'secondary',default=>'warning' }; ?>
                            <span class="badge bg-<?= $sc2 ?>"><?= e($c['entry_status']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($myWaitlist)): ?>
<div class="card mt-3">
    <div class="card-header"><strong><i class="bi bi-hourglass-split"></i> Lista rezerwowa</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-dark">
                <tr><th>Zawody</th><th>Data</th><th class="text-center">Pozycja</th></tr>
            </thead>
            <tbody>
            <?php foreach ($myWaitlist as $w): ?>
                <tr>
                    <td><?= e($w['competition_name']) ?></td>
                    <td class="small text-muted"><?= format_date($w['competition_date']) ?></td>
                    <td class="text-center"><span class="badge bg-warning text-dark"><?= (int)$w['position'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        <i class="bi bi-info-circle"></i>
        Gdy pojawi się wolne miejsce, wyślemy Ci wiadomość e-mail.
    </div>
</div>
<?php endif; ?>
