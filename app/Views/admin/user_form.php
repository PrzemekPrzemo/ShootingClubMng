<?php
$isEdit    = !empty($user);
$allRoles  = [
    'zarzad'    => ['label' => 'Zarząd',     'color' => 'warning',   'desc' => 'Pełny dostęp administracyjny do klubu'],
    'sędzia'    => ['label' => 'Sędzia',     'color' => 'info',      'desc' => 'Wprowadzanie wyników zawodów'],
    'instruktor'=> ['label' => 'Instruktor', 'color' => 'success',   'desc' => 'Zarządzanie treningami i zawodnikami'],
    'zawodnik'  => ['label' => 'Zawodnik',   'color' => 'secondary', 'desc' => 'Dostęp do portalu zawodnika'],
];
?>
<div class="d-flex align-items-center mb-4 gap-2">
    <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">
        <i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?>"></i>
        <?= $isEdit ? 'Edycja: ' . e($user['full_name']) : 'Nowy użytkownik' ?>
    </h2>
</div>

<div class="row g-4">
<div class="col-lg-5">
<div class="card">
    <div class="card-header"><strong>Dane konta</strong></div>
    <div class="card-body">
        <form method="post" action="<?= $isEdit ? url("admin/users/{$user['id']}/edit") : url('admin/users/create') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Imię i nazwisko <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name"
                       value="<?= e($user['full_name'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Login <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username"
                           value="<?= e($user['username'] ?? '') ?>" required autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email"
                           value="<?= e($user['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Hasło <?= $isEdit ? '<span class="text-muted">(zostaw puste = bez zmiany)</span>' : '<span class="text-danger">*</span>' ?></label>
                <input type="password" class="form-control" name="password"
                       autocomplete="new-password" <?= !$isEdit ? 'required' : '' ?> minlength="8">
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active"
                       <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Konto aktywne</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Zapisz' : 'Utwórz konto' ?>
                </button>
                <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>

<div class="col-lg-7">
    <!-- Przypisanie do klubu z wyborem ról -->
    <div class="card mb-3">
        <div class="card-header"><strong><?= $isEdit ? 'Dodaj przypisanie do klubu' : 'Przypisz do klubu' ?></strong></div>
        <div class="card-body">
            <form method="post" action="<?= $isEdit ? url("admin/users/{$user['id']}/edit") : url('admin/users/create') ?>">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="username"  value="<?= e($user['username']) ?>">
                    <input type="hidden" name="email"     value="<?= e($user['email']) ?>">
                    <input type="hidden" name="full_name" value="<?= e($user['full_name']) ?>">
                    <input type="hidden" name="is_active" value="1">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Klub</label>
                    <select class="form-select" name="club_id" required>
                        <option value="">— Wybierz klub —</option>
                        <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role w klubie <span class="text-muted small">(wybierz co najmniej jedną)</span></label>
                    <div class="row g-2">
                        <?php foreach ($allRoles as $roleKey => $roleInfo): ?>
                        <div class="col-6">
                            <div class="form-check border rounded p-2 h-100">
                                <input class="form-check-input" type="checkbox"
                                       name="club_roles[]" value="<?= $roleKey ?>"
                                       id="role_<?= $roleKey ?>">
                                <label class="form-check-label w-100" for="role_<?= $roleKey ?>">
                                    <span class="badge bg-<?= $roleInfo['color'] ?> me-1"><?= $roleInfo['label'] ?></span>
                                    <span class="text-muted small d-block mt-1"><?= $roleInfo['desc'] ?></span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text mt-2">
                        <i class="bi bi-info-circle"></i>
                        Uprawnienia wynikają z najwyższej wybranej roli.
                        Zawodnik+Zarząd = uprawnienia Zarządu.
                    </div>
                </div>

                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-plus-lg"></i> <?= $isEdit ? 'Dodaj przypisanie' : 'Utwórz i przypisz' ?>
                </button>
            </form>
        </div>
    </div>

    <?php if ($isEdit && !empty($userClubs)): ?>
    <!-- Aktualne przypisania -->
    <div class="card">
        <div class="card-header"><strong>Aktualne przypisania do klubów</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Klub</th><th>Role</th><th>Najwyższa</th><th>Powiązany zawodnik</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($userClubs as $uc): ?>
                <tr>
                    <td><?= e($uc['club_name']) ?></td>
                    <td>
                        <?php foreach ($uc['roles'] as $r): ?>
                        <?php $rc = ['zarzad'=>'warning','sędzia'=>'info','instruktor'=>'success','zawodnik'=>'secondary','admin'=>'danger'][$r] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $rc ?>"><?= e($r) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php $hr = $uc['highest_role']; $hc = ['zarzad'=>'warning','sędzia'=>'info','instruktor'=>'success','zawodnik'=>'secondary','admin'=>'danger'][$hr] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $hc ?>"><i class="bi bi-shield-check"></i> <?= e($hr) ?></span>
                    </td>
                    <td>
                        <form method="post" action="<?= url("admin/users/{$user['id']}/clubs/{$uc['club_id']}/link-member") ?>"
                              class="d-flex gap-1 align-items-center">
                            <?= csrf_field() ?>
                            <select name="linked_member_id" class="form-select form-select-sm" style="max-width:220px">
                                <option value="">— brak —</option>
                                <?php foreach (($clubMembers[$uc['club_id']] ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"
                                    <?= ((int)($uc['linked_member_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>>
                                    <?= e($m['last_name'] . ' ' . $m['first_name']) ?>
                                    <?php if (!empty($m['member_number'])): ?>(<?= e($m['member_number']) ?>)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Zapisz powiązanie">
                                <i class="bi bi-link-45deg"></i>
                            </button>
                        </form>
                    </td>
                    <td class="text-end">
                        <form method="post" action="<?= url("admin/users/{$user['id']}/clubs/{$uc['club_id']}/remove") ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Usunąć wszystkie przypisania w tym klubie?')">
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
    <?php elseif ($isEdit): ?>
    <div class="alert alert-info small"><i class="bi bi-info-circle"></i> Użytkownik nie jest przypisany do żadnego klubu.</div>
    <?php endif; ?>
</div>
</div>
