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
                        <?php if ($user['is_active'] && empty($user['is_super_admin']) && !empty($user['clubs'])): ?>
                        <a href="<?= url("admin/impersonate/user/{$user['id']}") ?>"
                           class="btn btn-sm btn-outline-warning" title="Zaloguj jako"
                           onclick="return confirm('Zalogować się jako <?= e(addslashes($user['full_name'])) ?>?')">
                            <i class="bi bi-person-fill-gear"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?= url("admin/users/{$user['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary" title="Edytuj">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if (empty($user['is_super_admin'])): ?>
                        <form method="post" action="<?= url("admin/users/{$user['id']}/delete") ?>" class="d-inline"
                              onsubmit="return confirm('Dezaktywować konto <?= e(addslashes($user['username'])) ?>?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Dezaktywuj konto">
                                <i class="bi bi-person-x"></i>
                            </button>
                        </form>
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                title="Usuń konto permanentnie"
                                data-bs-toggle="modal"
                                data-bs-target="#modalDelete"
                                data-username="<?= e($user['username']) ?>"
                                data-fullname="<?= e($user['full_name']) ?>"
                                data-action="<?= url("admin/users/{$user['id']}/permanent-delete") ?>">
                            <i class="bi bi-trash3"></i>
                        </button>
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

<!-- Modal: trwałe usunięcie konta -->
<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#1E2838;border:1px solid rgba(220,38,38,.4)">
            <div class="modal-header" style="border-color:rgba(220,38,38,.3)">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-trash3-fill me-2"></i>Trwałe usunięcie konta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="deleteForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-danger small py-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Ta operacja jest nieodwracalna.</strong> Konto zostanie usunięte z bazy danych.
                        Wszystkie przypisania do klubów zostaną usunięte. Historia aktywności zostanie zachowana (zanonimizowana).
                    </div>
                    <p class="mb-1">Usuwasz konto: <strong id="deleteFullname"></strong></p>
                    <p class="text-muted small mb-3">Login: <code id="deleteUsernameDisplay"></code></p>
                    <label for="confirm_username" class="form-label fw-semibold">
                        Wpisz login użytkownika, aby potwierdzić:
                    </label>
                    <input type="text" class="form-control" id="confirm_username" name="confirm_username"
                           placeholder="wpisz login..." autocomplete="off" required>
                </div>
                <div class="modal-footer" style="border-color:rgba(220,38,38,.3)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmDelete" disabled>
                        <i class="bi bi-trash3 me-1"></i>Usuń permanentnie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalDelete');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        const username = btn.dataset.username;
        document.getElementById('deleteFullname').textContent     = btn.dataset.fullname;
        document.getElementById('deleteUsernameDisplay').textContent = username;
        document.getElementById('deleteForm').action               = btn.dataset.action;
        document.getElementById('confirm_username').value          = '';
        document.getElementById('btnConfirmDelete').disabled       = true;
    });
    document.getElementById('confirm_username').addEventListener('input', function () {
        const expected = document.getElementById('deleteUsernameDisplay').textContent;
        document.getElementById('btnConfirmDelete').disabled = this.value !== expected;
    });
})();
</script>
