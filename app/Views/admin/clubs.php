<h2 class="mb-4"><i class="bi bi-building"></i> Zarządzanie klubami</h2>

<div class="mb-3">
    <a href="<?= url('admin/clubs/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nowy klub
    </a>
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Panel admina
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Skrót</th>
                    <th>E-mail</th>
                    <th>NIP</th>
                    <th>Zawodnicy</th>
                    <th>Limit</th>
                    <th>Plan</th>
                    <th>Kadra</th>
                    <th>Status</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clubs as $club): ?>
                <tr>
                    <td><?= (int)$club['id'] ?></td>
                    <td><strong><?= e($club['name']) ?></strong></td>
                    <td><?= e($club['short_name'] ?? '—') ?></td>
                    <td><?= e($club['email'] ?? '—') ?></td>
                    <td><?= e($club['nip'] ?? '—') ?></td>
                    <td class="text-center"><?= (int)($club['stats']['active_members'] ?? 0) ?></td>
                    <td class="text-center text-muted small"><?= $club['sub']['max_members'] ?? '∞' ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?= e($club['sub']['plan'] ?? '—') ?></span></td>
                    <td class="text-center"><?= (int)($club['stats']['staff'] ?? 0) ?></td>
                    <td>
                        <?= $club['is_active']
                            ? '<span class="badge bg-success">Aktywny</span>'
                            : '<span class="badge bg-secondary">Nieaktywny</span>' ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("admin/switch-club/{$club['id']}") ?>" class="btn btn-sm btn-outline-primary" title="Przełącz kontekst">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </a>
                        <a href="<?= url("admin/clubs/{$club['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary" title="Edytuj">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= url("admin/clubs/{$club['id']}/users") ?>" class="btn btn-sm btn-outline-info" title="Użytkownicy">
                            <i class="bi bi-people"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
