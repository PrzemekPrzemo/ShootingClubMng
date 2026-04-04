<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-people-fill"></i> Użytkownicy &amp; Uprawnienia</h2>
    <a href="<?= url('config/users/create') ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Dodaj użytkownika
    </a>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="usersTabs">
    <li class="nav-item">
        <a class="nav-link <?= !str_contains($_SERVER['REQUEST_URI'], '#perm') ? 'active' : '' ?>"
           href="#users" data-bs-toggle="tab">
            <i class="bi bi-person-lines-fill"></i> Lista użytkowników
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#permissions" data-bs-toggle="tab" id="permTab">
            <i class="bi bi-shield-lock"></i> Uprawnienia ról
        </a>
    </li>
</ul>

<div class="tab-content">

    <!-- ═══ Tab 1: Users ══════════════════════════════════════════════ -->
    <div class="tab-pane fade show active" id="users">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Login</th>
                            <th>Imię i nazwisko</th>
                            <th>E-mail</th>
                            <th>Rola</th>
                            <th>Aktywny</th>
                            <th>Ostatnie logowanie</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="<?= $u['is_active'] ? '' : 'table-secondary text-muted' ?>">
                            <td><code><?= e($u['username']) ?></code></td>
                            <td><?= e($u['full_name']) ?></td>
                            <td class="small"><?= e($u['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= match($u['role']) {
                                    'admin'      => 'danger',
                                    'zarzad'     => 'warning text-dark',
                                    'sędzia'     => 'primary',
                                    default      => 'secondary'
                                } ?>">
                                    <?= e($roles[$u['role']]['label'] ?? $u['role']) ?>
                                </span>
                            </td>
                            <td><?= $u['is_active']
                                ? '<span class="text-success"><i class="bi bi-check-circle"></i></span>'
                                : '<span class="text-muted"><i class="bi bi-x-circle"></i></span>' ?></td>
                            <td class="small text-muted">
                                <?= $u['last_login'] ? format_date(substr($u['last_login'],0,10)) : '—' ?>
                            </td>
                            <td class="text-end">
                                <?php if ($u['role'] !== 'admin' || $authUser['role'] === 'admin'): ?>
                                <a href="<?= url('config/users/' . $u['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-shield-lock"></i></span>
                                <?php endif; ?>
                                <?php if ($u['is_active'] && $u['role'] !== 'admin'): ?>
                                <form method="post" action="<?= url('config/users/' . $u['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Dezaktywować użytkownika <?= e($u['username']) ?>?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Brak użytkowników.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ Tab 2: Role permissions ═══════════════════════════════════ -->
    <div class="tab-pane fade" id="permissions">

        <div class="alert alert-info small py-2 mb-3">
            <i class="bi bi-info-circle"></i>
            Zaznacz które moduły są dostępne dla każdej roli. Administrator zawsze ma pełen dostęp.
            Zmiany wpływają na widoczność menu — dostęp do konkretnych akcji (edycja, usuwanie)
            kontrolowany jest osobno w ustawieniach ról.
        </div>

        <form method="post" action="<?= url('config/users/permissions') ?>" id="permForm">
            <?= csrf_field() ?>

            <div class="card">
                <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 perm-table">
                    <thead>
                        <tr class="table-dark">
                            <th style="min-width:200px">Moduł</th>
                            <?php foreach ($roles as $role => $rInfo): ?>
                            <th class="role-col">
                                <span class="badge bg-<?= $rInfo['color'] ?>">
                                    <?= e($rInfo['label']) ?>
                                </span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($modules as $mod => $mInfo): ?>
                        <tr>
                            <td class="perm-row-label">
                                <i class="bi bi-<?= $mInfo['icon'] ?>"></i>
                                <?= e($mInfo['label']) ?>
                            </td>
                            <?php foreach ($roles as $role => $rInfo):
                                $checked   = $permMatrix[$role][$mod] ?? false;
                                $isAdmin   = $role === 'admin';
                                $isDashboard = $mod === 'dashboard';
                            ?>
                            <td class="text-center">
                                <input type="checkbox"
                                       class="perm-check"
                                       name="perm[<?= $role ?>][<?= $mod ?>]"
                                       value="1"
                                       <?= $checked    ? 'checked'  : '' ?>
                                       <?= ($isAdmin || $isDashboard) ? 'disabled' : '' ?>
                                       onchange="syncHidden(this)">
                                <?php if ($isAdmin || $isDashboard): ?>
                                <!-- Always-on: send value via hidden input -->
                                <input type="hidden" name="perm[<?= $role ?>][<?= $mod ?>]" value="1">
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-floppy"></i> Zapisz uprawnienia
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetPerms()">
                    <i class="bi bi-arrow-counterclockwise"></i> Przywróć domyślne
                </button>
            </div>
        </form>

        <div class="mt-4">
            <h6 class="text-muted small text-uppercase">Legenda ról</h6>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach ($roles as $role => $rInfo): ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?= $rInfo['color'] ?>"><?= e($rInfo['label']) ?></span>
                    <span class="small text-muted">
                        <?= match($role) {
                            'admin'      => 'Pełen dostęp do wszystkiego, w tym konfiguracji',
                            'zarzad'     => 'Zarządzanie członkami, finansami i zawodami',
                            'instruktor' => 'Dostęp do zawodów, zawodników i licencji',
                            'sędzia'     => 'Dostęp do przeglądania zawodów i wprowadzania wyników',
                            default      => ''
                        } ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /tab permissions -->

</div><!-- /tab-content -->

<script>
// Switch to permissions tab if URL has #permissions
if (location.hash === '#permissions') {
    document.getElementById('permTab')?.click();
}

// After save, stay on permissions tab
document.getElementById('permForm')?.addEventListener('submit', function() {
    history.replaceState(null, '', location.pathname + '#permissions');
});

// Sync visible disabled checkbox with hidden input (for always-on)
function syncHidden(cb) {
    // For enabled checkboxes: no hidden, value sent normally
    // This handles the case where a non-disabled checkbox is toggled
}

// Reset to hardcoded defaults
const DEFAULTS = <?= json_encode(
    array_map(
        fn($mods) => array_fill_keys($mods, true),
        \App\Models\RolePermissionModel::DEFAULTS
    )
) ?>;

function resetPerms() {
    if (!confirm('Przywrócić domyślne uprawnienia ról?')) return;
    document.querySelectorAll('.perm-check:not(:disabled)').forEach(cb => {
        const match = cb.name.match(/perm\[(\w+)\]\[(\w+)\]/);
        if (!match) return;
        const [, role, mod] = match;
        cb.checked = !!(DEFAULTS[role] && DEFAULTS[role][mod]);
    });
}
</script>
