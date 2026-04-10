<h2 class="mb-4">
    <i class="bi bi-people"></i> Użytkownicy — <?= e($club['name']) ?>
</h2>

<a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary btn-sm mb-3">
    <i class="bi bi-arrow-left"></i> Wróć do listy klubów
</a>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Przypisani użytkownicy</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Imię i nazwisko</th>
                    <th>Login</th>
                    <th>E-mail</th>
                    <th>Rola w klubie</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td>
                        <?php foreach (explode(',', $user['club_roles'] ?? '') as $r): ?>
                            <span class="badge bg-secondary"><?= e(trim($r)) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td class="text-end d-flex gap-1 justify-content-end">
                        <a href="<?= url("admin/impersonate/club/{$club['id']}/user/{$user['id']}") ?>"
                           class="btn btn-sm btn-outline-warning" title="Zaloguj się jako ten użytkownik">
                            <i class="bi bi-person-fill-gear"></i>
                        </a>
                        <a href="<?= url("admin/clubs/{$club['id']}/users/{$user['id']}/remove") ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Usunąć tego użytkownika z klubu?')">
                            <i class="bi bi-person-x"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="5" class="text-muted text-center py-3">Brak przypisanych użytkowników</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header"><h5 class="mb-0">Dodaj użytkownika do klubu</h5></div>
    <div class="card-body">
        <form method="post" action="<?= url("admin/clubs/{$club['id']}/users") ?>">
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="user_id" class="form-label">Użytkownik</label>
                    <select class="form-select" name="user_id" id="user_id" required>
                        <option value="">— Wybierz —</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label">Rola</label>
                    <select class="form-select" name="role" id="role">
                        <option value="zarzad">Zarząd</option>
                        <option value="instruktor" selected>Instruktor</option>
                        <option value="sędzia">Sędzia</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus"></i> Dodaj
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
