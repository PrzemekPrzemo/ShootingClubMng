<div class="container py-5" style="max-width:860px">

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
        Poniżej znajdziesz dane logowania do każdego poziomu dostępu. Hasło do wszystkich kont:
        <strong class="ms-1 font-monospace"><?= e($password) ?></strong>
    </div>

    <!-- Panel users -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-layout-sidebar"></i> Panel zarządzania klubem</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Poziom dostępu</th>
                        <th>Login (nazwa użytkownika)</th>
                        <th>Hasło</th>
                        <th>Opis</th>
                        <th class="text-end">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $roleLabels = [
                        'zarzad'    => ['Zarząd (admin klubu)',   'bg-danger',  'Pełny dostęp: zawodnicy, zawody, finanse, ustawienia'],
                        'instruktor'=> ['Instruktor',              'bg-success', 'Zarządzanie treningami i zawodnikami'],
                        'sędzia'   => ['Sędzia',                  'bg-info',    'Protokołowanie zawodów i wyniki'],
                    ];
                    ?>
                    <?php foreach ($demoUsers as $u): ?>
                        <?php [$label, $badgeCls, $desc] = $roleLabels[$u['role']] ?? [$u['role'], 'bg-secondary', '']; ?>
                    <tr>
                        <td><span class="badge <?= $badgeCls ?>"><?= e($label) ?></span></td>
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

    <!-- Member portal -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-person-badge"></i> Portal zawodnika</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($portalMember): ?>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Imię i nazwisko</th>
                        <th>E-mail (login)</th>
                        <th>Hasło</th>
                        <th>Opis</th>
                        <th class="text-end">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= e($portalMember['first_name'] . ' ' . $portalMember['last_name']) ?></td>
                        <td><code><?= e($portalMember['email']) ?></code></td>
                        <td><code><?= e($password) ?></code></td>
                        <td class="small text-muted">Widok własnych wyników, zgłoszenia na zawody, dokumenty</td>
                        <td class="text-end">
                            <a href="<?= url('portal/login') ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="bi bi-box-arrow-in-right"></i> Zaloguj
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <div class="p-3 text-muted">Brak konta portalowego w tym demo.</div>
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
