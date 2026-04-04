<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config/users') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('config/users/create') : url('config/users/' . $user['id'] . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Imię i nazwisko <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= e($user['full_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Login <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control"
                       value="<?= e($user['username'] ?? '') ?>" required autocomplete="off">
            </div>
            <div class="mb-3">
                <label class="form-label">E-mail <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= e($user['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Rola</label>
                <select name="role" class="form-select">
                    <?php
                    $roleLabels = ['instruktor' => 'Instruktor', 'sędzia' => 'Sędzia', 'zarzad' => 'Zarząd', 'admin' => 'Administrator'];
                    foreach ($roleLabels as $r => $rl):
                    ?>
                        <option value="<?= $r ?>" <?= ($user['role'] ?? 'instruktor') === $r ? 'selected':'' ?>><?= $rl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Hasło <?= $mode === 'edit' ? '(zostaw puste = bez zmiany)' : '<span class="text-danger">*</span>' ?></label>
                <input type="password" name="password" class="form-control" autocomplete="new-password"
                       <?= $mode === 'create' ? 'required' : '' ?> minlength="8">
                <div class="form-text">Minimum 8 znaków.</div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Dodaj użytkownika' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('config/users') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
