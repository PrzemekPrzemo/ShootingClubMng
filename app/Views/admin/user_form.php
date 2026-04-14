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
                                       name="club_roles[]" value="<?= e($roleKey) ?>"
                                       id="role_<?= e($roleKey) ?>">
                                <label class="form-check-label w-100" for="role_<?= e($roleKey) ?>">
                                    <span class="badge bg-<?= e($roleInfo['color']) ?> me-1"><?= e($roleInfo['label']) ?></span>
                                    <span class="text-muted small d-block mt-1"><?= e($roleInfo['desc']) ?></span>
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

    <?php if ($isEdit): ?>
    <!-- Powiązanie z zawodnikiem -->
    <div class="card mb-3">
        <div class="card-header">
            <strong><i class="bi bi-person-lines-fill"></i> Powiązany zawodnik</strong>
        </div>
        <div class="card-body">

            <?php
            $linkedId   = (int)($user['member_id'] ?? 0);
            $linkedInfo = null;
            if ($linkedId) {
                $db = \App\Helpers\Database::pdo();
                $s  = $db->prepare(
                    "SELECT m.id, m.first_name, m.last_name, m.member_number, m.status, c.name AS club_name
                     FROM members m JOIN clubs c ON c.id = m.club_id WHERE m.id = ?"
                );
                $s->execute([$linkedId]);
                $linkedInfo = $s->fetch() ?: null;
            }
            ?>

            <!-- Current link status -->
            <?php if ($linkedInfo): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-3">
                <i class="bi bi-link-45deg fs-5"></i>
                <div>
                    <strong><?= e($linkedInfo['last_name'] . ' ' . $linkedInfo['first_name']) ?></strong>
                    <span class="text-muted ms-1">[<?= e($linkedInfo['member_number']) ?>]</span>
                    — <?= e($linkedInfo['club_name']) ?>
                    <?php if ($linkedInfo['status'] !== 'aktywny'): ?>
                    <span class="badge bg-warning ms-1"><?= e($linkedInfo['status']) ?></span>
                    <?php endif; ?>
                </div>
                <!-- Clear link button -->
                <form method="post" action="<?= url("admin/users/{$user['id']}/edit") ?>" class="ms-auto">
                    <?= csrf_field() ?>
                    <input type="hidden" name="username"  value="<?= e($user['username']) ?>">
                    <input type="hidden" name="email"     value="<?= e($user['email']) ?>">
                    <input type="hidden" name="full_name" value="<?= e($user['full_name']) ?>">
                    <input type="hidden" name="is_active" value="<?= (int)($user['is_active'] ?? 1) ?>">
                    <input type="hidden" name="member_id" value="">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Odłączyć zawodnika od tego konta?')">
                        <i class="bi bi-x-lg"></i> Odłącz
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-secondary py-2 mb-3">
                <i class="bi bi-link-45deg text-muted"></i>
                Brak powiązania — mostek automatyczny przez e-mail (jeśli istnieje).
            </div>
            <?php endif; ?>

            <!-- Search form -->
            <label class="form-label fw-semibold">Szukaj zawodnika</label>
            <div class="input-group mb-2">
                <input type="text" id="memberSearchInput" class="form-control"
                       placeholder="PESEL, nr członkowski lub nazwisko…"
                       autocomplete="off">
                <button type="button" class="btn btn-outline-primary" id="memberSearchBtn">
                    <i class="bi bi-search"></i> Szukaj
                </button>
            </div>
            <div class="form-text mb-3">Wpisz PESEL (dokładne dopasowanie), numer członkowski lub nazwisko.</div>

            <!-- Results -->
            <div id="memberSearchResults"></div>

            <!-- Hidden save form — submitted by JS after selection -->
            <form method="post" action="<?= url("admin/users/{$user['id']}/edit") ?>" id="memberLinkForm" class="d-none">
                <?= csrf_field() ?>
                <input type="hidden" name="username"  value="<?= e($user['username']) ?>">
                <input type="hidden" name="email"     value="<?= e($user['email']) ?>">
                <input type="hidden" name="full_name" value="<?= e($user['full_name']) ?>">
                <input type="hidden" name="is_active" value="<?= (int)($user['is_active'] ?? 1) ?>">
                <input type="hidden" name="member_id" id="memberLinkId" value="">
            </form>
        </div>
    </div>

    <script>
    (function () {
        var input   = document.getElementById('memberSearchInput');
        var btn     = document.getElementById('memberSearchBtn');
        var results = document.getElementById('memberSearchResults');
        var form    = document.getElementById('memberLinkForm');
        var hiddenId= document.getElementById('memberLinkId');
        var searchUrl = <?= json_encode(url('admin/users/member-search')) ?>;

        function doSearch() {
            var q = input.value.trim();
            if (q.length < 3) {
                results.innerHTML = '<p class="text-muted small">Wpisz co najmniej 3 znaki.</p>';
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch(searchUrl + '?q=' + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-search"></i> Szukaj';
                    if (!data.members || data.members.length === 0) {
                        results.innerHTML = '<p class="text-warning small"><i class="bi bi-exclamation-circle"></i> Nie znaleziono zawodnika.</p>';
                        return;
                    }
                    var html = '<div class="list-group">';
                    data.members.forEach(function(m) {
                        var peselBadge = m.pesel_match
                            ? '<span class="badge bg-success ms-1"><i class="bi bi-check-circle"></i> PESEL</span>'
                            : '';
                        var statusBadge = m.status !== 'aktywny'
                            ? '<span class="badge bg-warning ms-1">' + m.status + '</span>'
                            : '';
                        html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-id="' + m.id + '" data-name="' + m.full_name + '">'
                            + '<span>'
                            + '<strong>' + m.full_name + '</strong>'
                            + peselBadge + statusBadge
                            + '<span class="text-muted ms-2 small">[' + m.member_number + '] — ' + m.club_name + '</span>'
                            + '</span>'
                            + '<span class="badge bg-primary">Powiąż</span>'
                            + '</button>';
                    });
                    html += '</div>';
                    results.innerHTML = html;

                    results.querySelectorAll('[data-id]').forEach(function(el) {
                        el.addEventListener('click', function() {
                            if (!confirm('Powiązać konto z zawodnikiem: ' + this.dataset.name + '?')) return;
                            hiddenId.value = this.dataset.id;
                            form.submit();
                        });
                    });
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-search"></i> Szukaj';
                    results.innerHTML = '<p class="text-danger small">Błąd wyszukiwania. Spróbuj ponownie.</p>';
                });
        }

        btn.addEventListener('click', doSearch);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });
    })();
    </script>
    <?php endif; ?>

    <?php if ($isEdit && !empty($userClubs)): ?>
    <!-- Aktualne przypisania -->
    <div class="card">
        <div class="card-header"><strong>Aktualne przypisania do klubów</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Klub</th><th>Role</th><th>Najwyższa</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($userClubs as $uc): ?>
                <tr>
                    <td><?= e($uc['club_name']) ?></td>
                    <td>
                        <?php foreach ($uc['roles'] as $r): ?>
                        <?php $rc = ['zarzad'=>'warning','sędzia'=>'info','instruktor'=>'success','zawodnik'=>'secondary','admin'=>'danger'][$r] ?? 'secondary'; ?>
                        <span class="badge bg-<?= e($rc) ?>"><?= e($r) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php $hr = $uc['highest_role']; $hc = ['zarzad'=>'warning','sędzia'=>'info','instruktor'=>'success','zawodnik'=>'secondary','admin'=>'danger'][$hr] ?? 'secondary'; ?>
                        <span class="badge bg-<?= e($hc) ?>"><i class="bi bi-shield-check"></i> <?= e($hr) ?></span>
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
