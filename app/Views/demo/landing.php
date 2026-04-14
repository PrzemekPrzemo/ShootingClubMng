<div class="container py-5" style="max-width:900px">

    <div class="text-center mb-4">
        <h1 class="fw-bold"><i class="bi bi-joystick"></i> Środowisko demonstracyjne</h1>
        <p class="lead text-muted">Wypróbuj system z perspektywy każdego poziomu dostępu. Dane są fikcyjne.</p>
        <?php if ($demo['demo_expires_at']): ?>
            <?php $expiresTs = strtotime($demo['demo_expires_at']); ?>
            <?php $hoursLeft = max(0, round(($expiresTs - time()) / 3600, 1)); ?>
            <span class="badge <?= $hoursLeft < 2 ? 'bg-danger' : 'bg-secondary' ?> fs-6">
                <i class="bi bi-clock"></i> Wygasa: <?= date('d.m.Y H:i', $expiresTs) ?>
                (za <?= $hoursLeft ?>h)
            </span>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Hasło do <strong>wszystkich</strong> kont: <strong class="ms-1 font-monospace"><?= e($password) ?></strong>
    </div>

    <!-- Staff panel section -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-layout-sidebar"></i> Panel zarządzania klubem</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Poziom dostępu</th>
                        <th>Login</th>
                        <th>Hasło</th>
                        <th>Opis</th>
                        <th class="text-end">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $roleLabels = [
                        'zarzad'     => ['Zarząd (admin klubu)', 'bg-danger',  'Pełny dostęp: zawodnicy, zawody, finanse, ustawienia'],
                        'instruktor' => ['Instruktor',           'bg-success', 'Zarządzanie treningami i zawodnikami'],
                        'sędzia'    => ['Sędzia',               'bg-info',    'Protokołowanie zawodów i wyniki'],
                    ];
                    ?>
                    <?php foreach ($demoUsers as $u): ?>
                        <?php [$label, $badgeCls, $desc] = $roleLabels[$u['role']] ?? [$u['role'], 'bg-secondary', '']; ?>
                    <tr>
                        <td>
                            <span class="badge <?= $badgeCls ?>"><?= e($label) ?></span>
                        </td>
                        <td><code><?= e($u['username']) ?></code></td>
                        <td><code><?= e($password) ?></code></td>
                        <td class="small text-muted"><?= e($desc) ?></td>
                        <td class="text-end">
                            <a href="<?= url('auth/login') ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-box-arrow-in-right"></i> Zaloguj
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($demoUsers)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Brak kont panelu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Portal members section -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white d-flex align-items-center gap-2">
            <h5 class="mb-0"><i class="bi bi-person-badge"></i> Portal zawodnika</h5>
            <span class="badge bg-white text-success ms-1"><?= count($portalMembers) ?> konta</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($portalMembers)): ?>
            <div class="alert alert-secondary rounded-0 border-0 py-2 px-3 small mb-0"
                 style="background:rgba(134,239,172,.06);border-bottom:1px solid rgba(134,239,172,.15)!important">
                <i class="bi bi-arrow-left-right me-1 text-success"></i>
                <strong>Podwójny kontekst:</strong> 3 z tych kont są powiązane z kontami panelu.
                Po zalogowaniu do panelu zarządzania zobaczysz przycisk
                <i class="bi bi-person-badge" style="color:#86EFAC"></i> w górnym pasku — kliknij, by przełączyć na widok zawodnika.
            </div>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Zawodnik</th>
                        <th>E-mail (login do portalu)</th>
                        <th>Hasło</th>
                        <th>Powiązany z panelem</th>
                        <th class="text-end">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rolePortalLabels = [
                        'zarzad'     => ['Zarząd',    'bg-danger'],
                        'instruktor' => ['Instruktor', 'bg-success'],
                        'sędzia'    => ['Sędzia',     'bg-info'],
                    ];
                    ?>
                    <?php foreach ($portalMembers as $pm): ?>
                    <tr>
                        <td class="fw-medium"><?= e($pm['first_name'] . ' ' . $pm['last_name']) ?></td>
                        <td><code><?= e($pm['email']) ?></code></td>
                        <td><code><?= e($password) ?></code></td>
                        <td>
                            <?php if (!empty($pm['linked_username'])): ?>
                                <?php [$rl, $rc] = $rolePortalLabels[$pm['linked_role']] ?? [$pm['linked_role'], 'bg-secondary']; ?>
                                <span class="badge <?= $rc ?> me-1"><?= e($rl) ?></span>
                                <code class="small"><?= e($pm['linked_username']) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">— tylko portal</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('portal/login') ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="bi bi-box-arrow-in-right"></i> Portal
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="p-3 text-muted">Brak kont portalowych w tym demo.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sample data overview -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-database"></i> Przykładowe dane w demo</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="p-3 border rounded">
                        <h4 class="mb-0 text-primary">12</h4>
                        <small class="text-muted">Zawodników</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 border rounded">
                        <h4 class="mb-0 text-success">2</h4>
                        <small class="text-muted">Zawody</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 border rounded">
                        <h4 class="mb-0 text-info">3</h4>
                        <small class="text-muted">Treningi</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 border rounded">
                        <h4 class="mb-0 text-warning">6</h4>
                        <small class="text-muted">Egzemplarze broni</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center text-muted small">
        <i class="bi bi-shield-check"></i> Dane demonstracyjne są fikcyjne. System działa na pełnej wersji produkcyjnej.
        <br>Zainteresowany wdrożeniem? <strong>Skontaktuj się z nami.</strong>
    </div>

</div>
