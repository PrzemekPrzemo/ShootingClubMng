<h2 class="h4 mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h2>

<!-- Stats row -->
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

    <!-- Alert: Badania sportowe -->
    <?php if ($expiringMedicals): ?>
    <div class="col-lg-6">
        <div class="card border-<?= count($expiringMedicals) > 0 ? 'danger' : 'success' ?>">
            <div class="card-header bg-<?= count($expiringMedicals) > 0 ? 'danger' : 'success' ?> text-white">
                <i class="bi bi-heart-pulse"></i>
                <strong>Badania sportowe — wyczynowi</strong>
                <span class="badge bg-light text-dark ms-2"><?= count($expiringMedicals) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Zawodnik</th><th>Ważne do</th><th>Termin</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($expiringMedicals, 0, 8) as $med): ?>
                        <tr>
                            <td><a href="<?= url('members/' . $med['member_id'] . '/exams') ?>"><?= e($med['last_name']) ?> <?= e($med['first_name']) ?></a></td>
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

    <!-- Quick links -->
    <div class="col-lg-<?= ($expiringLicenses || $expiringMedicals) ? '6' : '12' ?>">
        <div class="card h-100">
            <div class="card-header"><strong>Szybkie akcje</strong></div>
            <div class="card-body d-flex flex-wrap gap-2 align-items-start">
                <a href="<?= url('members/create') ?>" class="btn btn-outline-danger">
                    <i class="bi bi-person-plus"></i> Dodaj zawodnika
                </a>
                <a href="<?= url('finances/create') ?>" class="btn btn-outline-success">
                    <i class="bi bi-cash-coin"></i> Zarejestruj wpłatę
                </a>
                <a href="<?= url('licenses/create') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-card-checklist"></i> Dodaj licencję
                </a>
                <a href="<?= url('competitions/create') ?>" class="btn btn-outline-warning">
                    <i class="bi bi-trophy"></i> Utwórz zawody
                </a>
                <a href="<?= url('reports') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-bar-graph"></i> Raporty
                </a>
            </div>
        </div>
    </div>
</div>
