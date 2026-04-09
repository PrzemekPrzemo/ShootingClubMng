<h2 class="mb-4"><i class="bi bi-people"></i> Użytkownicy systemu</h2>

<div class="mb-3">
    <a href="<?= url('admin/users/create') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nowy użytkownik
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
                    <th>Imię i nazwisko</th>
                    <th>Login</th>
                    <th>E-mail</th>
                    <th>Rola systemowa</th>
                    <th>Kluby</th>
                    <th>Status</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['full_name']) ?></td>
                    <td><code><?= e($user['username']) ?></code></td>
                    <td><?= e($user['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= match($user['role']) {
                            'admin' => 'danger', 'zarzad' => 'warning text-dark',
                            'instruktor' => 'info', default => 'secondary'
                        } ?>"><?= e($user['role']) ?></span>
                        <?php if (!empty($user['is_super_admin'])): ?>
                            <span class="badge bg-dark"><i class="bi bi-shield-lock-fill"></i> superadmin</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (empty($user['clubs'])): ?>
                            <span class="text-muted small">—</span>
                        <?php else: ?>
                            <?php foreach ($user['clubs'] as $uc): ?>
                                <span class="badge bg-light text-dark border me-1">
                                    <?= e($uc['short_name'] ?? $uc['club_name']) ?>
                                    <small class="text-muted">(<?= e($uc['role']) ?>)</small>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $user['is_active']
                            ? '<span class="badge bg-success">Aktywny</span>'
                            : '<span class="badge bg-secondary">Nieaktywny</span>' ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= url("admin/users/{$user['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary" title="Edytuj">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if (empty($user['is_super_admin'])): ?>
                        <form method="post" action="<?= url("admin/users/{$user['id']}/delete") ?>" class="d-inline"
                              onsubmit="return confirm('Dezaktywować konto <?= e($user['username']) ?>?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Dezaktywuj">
                                <i class="bi bi-person-x"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="7" class="text-muted text-center py-3">Brak użytkowników</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
