<h2 class="mb-4"><i class="bi bi-shield-lock"></i> Panel administratora</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h3 class="text-primary"><?= (int)$stats['clubs'] ?></h3>
                <div class="text-muted">Aktywne kluby</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3 class="text-success"><?= (int)$stats['members'] ?></h3>
                <div class="text-muted">Aktywni zawodnicy</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-info">
            <div class="card-body">
                <h3 class="text-info"><?= (int)$stats['users'] ?></h3>
                <div class="text-muted">Użytkownicy systemu</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-building"></i> Kluby</h5>
        <a href="<?= url('admin/clubs/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Nowy klub
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th>Skrót</th>
                    <th>Status</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clubs as $club): ?>
                <tr>
                    <td><?= e($club['name']) ?></td>
                    <td><?= e($club['short_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($club['is_active']): ?>
                            <span class="badge bg-success">Aktywny</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nieaktywny</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url("admin/switch-club/{$club['id']}") ?>" class="btn btn-sm btn-outline-primary" title="Przełącz na ten klub">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </a>
                        <a href="<?= url("admin/clubs/{$club['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= url("admin/clubs/{$club['id']}/users") ?>" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-people"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clubs)): ?>
                <tr><td colspan="4" class="text-muted text-center py-3">Brak klubów</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex flex-wrap gap-2">
    <a href="<?= url('admin/settings') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-gear"></i> Ustawienia
    </a>
    <a href="<?= url('admin/subscriptions') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-credit-card-2-front"></i> Subskrypcje
    </a>
    <a href="<?= url('admin/subscriptions/plans') ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-sliders"></i> Plany / Ceny
    </a>
    <a href="<?= url('admin/subscriptions/invoices') ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-receipt"></i> Faktury
    </a>
    <a href="<?= url('admin/analytics') ?>" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-bar-chart-line"></i> Analityka
    </a>
    <a href="<?= url('admin/ads') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-megaphone"></i> Reklamy
    </a>
    <a href="<?= url('admin/security') ?>" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-shield-check"></i> Audyt bezpieczeństwa
    </a>
    <a href="<?= url('admin/demos') ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-joystick"></i> Demo
    </a>
</div>
