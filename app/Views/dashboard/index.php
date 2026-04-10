<?php
$role          = $authUser['role'] ?? '';
$isFinanceRole = in_array($role, ['admin','zarzad']);
$isAdminRole   = in_array($role, ['admin','zarzad','instruktor','sędzia']);
?>
<h2 class="h4 mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>

<!-- Stats row -->
<?php if ($isAdminRole): ?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-people"></i> Aktywnych zawodników</div>
                <div class="display-6 fw-bold text-success"><?= $memberStats['aktywny'] ?? 0 ?></div>
                <?php if (!empty($memberStats['zawieszony'])): ?>
                    <small class="text-warning"><?= $memberStats['zawieszony'] ?> zawieszonych</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($isFinanceRole): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-<?= $debtorsCount > 0 ? 'danger' : 'success' ?> h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-exclamation-triangle"></i> Zalegający ze składkami</div>
                <div class="display-6 fw-bold text-<?= $debtorsCount > 0 ? 'danger' : 'success' ?>"><?= $debtorsCount ?></div>
                <a href="<?= url('finances/debts?year=' . $currentYear) ?>" class="small">Zobacz zaległości</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-info h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-cash-stack"></i> Wpłaty <?= $currentYear ?></div>
                <div class="h4 fw-bold text-info"><?= format_money($totalPaymentsYear) ?></div>
                <a href="<?= url('finances?year=' . $currentYear) ?>" class="small">Przejdź do finansów</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-warning h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-card-checklist"></i> Licencje wygasające (<?= $alertLicDays ?> dni)</div>
                <div class="display-6 fw-bold text-<?= count($expiringLicenses) > 0 ? 'warning' : 'success' ?>"><?= count($expiringLicenses) ?></div>
                <a href="<?= url('licenses') ?>" class="small">Zarządzaj licencjami</a>
            </div>
        </div>
    </div>
</div>
<?php endif; // $isAdminRole — stats row ?>

<div class="row g-3">
    <!-- Alert: Licencje -->
    <?php if ($expiringLicenses): ?>
    <div class="col-lg-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-exclamation-circle"></i>
                <strong>Licencje wymagające uwagi</strong>
                <span class="badge bg-dark ms-2"><?= count($expiringLicenses) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Zawodnik</th><th>Nr licencji</th><th>Ważna do</th><th>Termin</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($expiringLicenses, 0, 8) as $lic): ?>
                        <tr>
                            <td><a href="<?= url('members/' . $lic['member_id']) ?>"><?= e($lic['last_name']) ?> <?= e($lic['first_name']) ?></a></td>
                            <td><small><?= e($lic['license_number']) ?></small></td>
                            <td><small><?= format_date($lic['valid_until']) ?></small></td>
                            <td>
                                <span class="badge bg-<?= alert_class($lic['days_left'], $alertLicDays) ?>">
                                    <?= $lic['days_left'] >= 0 ? 'za ' . $lic['days_left'] . ' dni' : 'WYGASŁA' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($expiringLicenses) > 8): ?>
            <div class="card-footer text-center">
                <a href="<?= url('licenses') ?>" class="small">i <?= count($expiringLicenses)-8 ?> więcej…</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert: Badania lekarskie -->
    <?php if ($expiringMedicals): ?>
    <div class="col-lg-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-heart-pulse"></i>
                <strong>Badania lekarskie — wygasające</strong>
                <span class="badge bg-light text-dark ms-2"><?= count($expiringMedicals) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Zawodnik</th><th>Typ</th><th>Ważne do</th><th>Termin</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($expiringMedicals, 0, 8) as $med): ?>
                        <tr>
                            <td><a href="<?= url('members/' . $med['member_id'] . '/exams') ?>"><?= e($med['last_name']) ?> <?= e($med['first_name']) ?></a></td>
                            <td class="small text-muted"><?= e($med['exam_type_name'] ?? '—') ?></td>
                            <td><small><?= format_date($med['valid_until']) ?></small></td>
                            <td>
                                <span class="badge bg-<?= alert_class($med['days_left'], $alertMedDays) ?>">
                                    <?= $med['days_left'] >= 0 ? 'za ' . $med['days_left'] . ' dni' : 'WYGASŁE' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert: Licencje sędziowskie -->
    <?php if (!empty($expiringJudgeLic)): ?>
    <div class="col-lg-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-person-badge"></i>
                <strong>Licencje sędziowskie — wygasające</strong>
                <span class="badge bg-dark ms-2"><?= count($expiringJudgeLic) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Sędzia</th><th>Klasa</th><th>Ważna do</th><th>Termin</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($expiringJudgeLic, 0, 6) as $jl): ?>
                        <tr>
                            <td><a href="<?= url('judges') ?>"><?= e($jl['last_name']) ?> <?= e($jl['first_name']) ?></a></td>
                            <td><span class="badge bg-dark"><?= e($jl['judge_class']) ?></span></td>
                            <td><small><?= format_date($jl['valid_until']) ?></small></td>
                            <td>
                                <span class="badge bg-<?= alert_class($jl['days_left'], 60) ?>">
                                    <?= $jl['days_left'] >= 0 ? 'za ' . $jl['days_left'] . ' dni' : 'WYGASŁA' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert: Opłaty PZSS/PomZSS -->
    <?php if ($isFinanceRole && $clubFeesPending > 0): ?>
    <div class="col-lg-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-bank"></i>
                <strong>Opłaty PZSS/PomZSS <?= $currentYear ?></strong>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>Należne:</span>
                    <strong><?= format_money($clubFeesTotalDue) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Zapłacono:</span>
                    <strong class="text-success"><?= format_money($clubFeesTotalPaid) ?></strong>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span>Pozostało:</span>
                    <strong class="text-danger"><?= format_money($clubFeesPending) ?></strong>
                </div>
                <a href="<?= url('club-fees/' . $currentYear) ?>" class="btn btn-sm btn-outline-light mt-2">
                    Zarządzaj opłatami
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Upcoming competitions -->
    <?php if ($upcomingCompetitions): ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-trophy"></i> <strong>Nadchodzące zawody (30 dni)</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Data</th><th>Zawody</th><th>Status</th><th>Zgłoszeń</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcomingCompetitions as $c): ?>
                        <tr>
                            <td><small><?= format_date($c['competition_date']) ?></small></td>
                            <td><a href="<?= url('competitions/' . $c['id']) ?>"><?= e($c['name']) ?></a></td>
                            <td>
                                <?php $sc = match($c['status']) { 'otwarte'=>'success','planowane'=>'secondary',default=>'warning' }; ?>
                                <span class="badge bg-<?= $sc ?>"><?= e($c['status']) ?></span>
                            </td>
                            <td><?= $c['entry_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notifications for admin/zarząd -->
    <?php if (!empty($notifications)): ?>
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
                <strong><i class="bi bi-bell"></i> Powiadomienia <?php if ($notifCount ?? 0): ?><span class="badge bg-white text-info"><?= $notifCount ?></span><?php endif; ?></strong>
                <form method="post" action="<?= url('dashboard/notifications/read') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-light">Oznacz wszystkie jako przeczytane</button>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($notifications as $n): ?>
                        <tr>
                            <td style="width:24px"><i class="bi bi-<?= match($n['type']) { 'exam_upload'=>'heart-pulse','competition_entry'=>'trophy',default=>'bell' } ?> text-info"></i></td>
                            <td>
                                <div class="fw-bold small"><?= e($n['title']) ?></div>
                                <div class="text-muted small"><?= e($n['message']) ?></div>
                            </td>
                            <td class="text-muted small" style="white-space:nowrap"><?= format_date(substr($n['created_at'], 0, 10)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick links -->
    <?php if ($isAdminRole): ?>
    <div class="col-lg-<?= ($expiringLicenses || $expiringMedicals) ? '6' : '12' ?>">
        <div class="card h-100">
            <div class="card-header"><strong>Szybkie akcje</strong></div>
            <div class="card-body d-flex flex-wrap gap-2 align-items-start">
                <a href="<?= url('members/create') ?>" class="btn btn-outline-danger">
                    <i class="bi bi-person-plus"></i> Dodaj zawodnika
                </a>
                <?php if ($isFinanceRole): ?>
                <a href="<?= url('finances/create') ?>" class="btn btn-outline-success">
                    <i class="bi bi-cash-coin"></i> Zarejestruj wpłatę
                </a>
                <?php endif; ?>
                <a href="<?= url('licenses/create') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-card-checklist"></i> Dodaj licencję
                </a>
                <a href="<?= url('competitions/create') ?>" class="btn btn-outline-warning">
                    <i class="bi bi-trophy"></i> Utwórz zawody
                </a>
                <a href="<?= url('judges/create') ?>" class="btn btn-outline-dark">
                    <i class="bi bi-person-badge"></i> Dodaj sędziego
                </a>
                <?php if ($isFinanceRole): ?>
                <a href="<?= url('club-fees') ?>" class="btn btn-outline-danger">
                    <i class="bi bi-bank"></i> Opłaty PZSS
                </a>
                <?php endif; ?>
                <a href="<?= url('reports') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-bar-graph"></i> Raporty
                </a>
                <?php if ($isFinanceRole): ?>
                <a href="<?= url('dashboard/stats') ?>" class="btn btn-outline-info">
                    <i class="bi bi-bar-chart-line"></i> Statystyki
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; // $isAdminRole — quick actions ?>
</div>

<?php
// Announcements widget — try to load active announcements
try {
    $__announcements = (new \App\Models\AnnouncementModel())->getActive();
} catch (\Throwable) { $__announcements = []; }
if (!empty($__announcements)):
?>
<div class="mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-megaphone text-danger"></i> Ogłoszenia</strong>
            <?php if ($isFinanceRole): ?>
            <a href="<?= url('announcements') ?>" class="btn btn-sm btn-outline-secondary">Zarządzaj</a>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php foreach ($__announcements as $ann): ?>
            <?php $bg = match($ann['priority']) {'pilne'=>'danger','wazne'=>'warning','normal'=>'light',default=>'light'}; ?>
            <div class="p-3 border-bottom bg-<?= $bg ?> bg-opacity-10">
                <div class="d-flex justify-content-between">
                    <strong><?= e($ann['title']) ?></strong>
                    <?php if ($ann['priority'] !== 'normal'): ?>
                    <span class="badge bg-<?= $bg === 'warning' ? 'warning text-dark' : $bg ?>"><?= e($ann['priority']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="small mt-1"><?= nl2br(e($ann['body'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
