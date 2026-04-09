<?php $isEdit = !empty($user); ?>
<h2 class="mb-4">
    <i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?>"></i>
    <?= $isEdit ? 'Edycja użytkownika' : 'Nowy użytkownik' ?>
</h2>

<div class="row g-4">
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><h5 class="mb-0">Dane konta</h5></div>
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? url("admin/users/{$user['id']}/edit") : url('admin/users/create') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Imię i nazwisko</label>
                <input type="text" class="form-control" name="full_name"
                       value="<?= e($user['full_name'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Login *</label>
                    <input type="text" class="form-control" name="username"
                           value="<?= e($user['username'] ?? '') ?>" required autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail *</label>
                    <input type="email" class="form-control" name="email"
                           value="<?= e($user['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Hasło <?= $isEdit ? '(zostaw puste = bez zmiany)' : '*' ?></label>
                    <input type="password" class="form-control" name="password"
                           autocomplete="new-password" <?= !$isEdit ? 'required' : '' ?> minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rola systemowa</label>
                    <select class="form-select" name="role">
                        <?php foreach (['admin' => 'Administrator', 'zarzad' => 'Zarząd', 'instruktor' => 'Instruktor', 'sędzia' => 'Sędzia'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($user['role'] ?? 'instruktor') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active"
                       <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktywny</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Zapisz' : 'Utwórz' ?>
                </button>
                <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>

<div class="col-lg-6">
    <!-- Przypisanie do klubu -->
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">Przypisz do klubu</h5></div>
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? url("admin/users/{$user['id']}/edit") : url('admin/users/create') ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="username"  value="<?= e($user['username']) ?>">
                    <input type="hidden" name="email"     value="<?= e($user['email']) ?>">
                    <input type="hidden" name="full_name" value="<?= e($user['full_name']) ?>">
                    <input type="hidden" name="role"      value="<?= e($user['role']) ?>">
                    <input type="hidden" name="is_active" value="<?= $user['is_active'] ? '1' : '' ?>">
                <?php endif; ?>
                <div class="row g-2 align-items-end">
                    <div class="col-7">
                        <label class="form-label">Klub</label>
                        <select class="form-select" name="club_id">
                            <option value="">— Wybierz —</option>
                            <?php foreach ($clubs as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label">Rola</label>
                        <select class="form-select" name="club_role">
                            <option value="zarzad">Zarząd</option>
                            <option value="instruktor" selected>Instruktor</option>
                            <option value="sędzia">Sędzia</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-outline-primary w-100" title="Przypisz">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit && !empty($userClubs)): ?>
    <!-- Aktualne przypisania -->
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Aktualne przypisania do klubów</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Klub</th><th>Rola</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($userClubs as $uc): ?>
                <tr>
                    <td><?= e($uc['club_name']) ?></td>
                    <td><span class="badge bg-info"><?= e($uc['role']) ?></span></td>
                    <td class="text-end">
                        <form method="post" action="<?= url("admin/users/{$user['id']}/clubs/{$uc['club_id']}/remove") ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Usunąć przypisanie?')">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>
