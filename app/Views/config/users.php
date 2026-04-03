<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h2 class="h4 mb-0"><i class="bi bi-gear"></i> Użytkownicy systemu</h2>
    </div>
    <a href="<?= url('config/users/create') ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg"></i> Dodaj użytkownika</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-auto"><a href="<?= url('config') ?>" class="btn btn-outline-secondary btn-sm">Ustawienia</a></div>
    <div class="col-auto"><a href="<?= url('config/categories') ?>" class="btn btn-outline-secondary btn-sm">Kategorie</a></div>
    <div class="col-auto"><a href="<?= url('config/users') ?>" class="btn btn-outline-primary btn-sm active">Użytkownicy</a></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-dark">
                <tr><th>Login</th><th>Imię i nazwisko</th><th>E-mail</th><th>Rola</th><th>Aktywny</th><th>Ostatnie logowanie</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr class="<?= $u['is_active'] ? '' : 'table-secondary text-muted' ?>">
                    <td><code><?= e($u['username']) ?></code></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td><span class="badge bg-<?= $u['role']==='admin' ? 'danger' : ($u['role']==='zarzad' ? 'warning text-dark' : 'secondary') ?>"><?= e($u['role']) ?></span></td>
                    <td><?= $u['is_active'] ? '<span class="text-success">Tak</span>' : '<span class="text-muted">Nie</span>' ?></td>
                    <td class="small"><?= $u['last_login'] ? format_date(substr($u['last_login'],0,10)) : '—' ?></td>
                    <td class="text-end">
                        <a href="<?= url('config/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-pencil"></i></a>
                        <?php if ($u['is_active']): ?>
                        <form method="post" action="<?= url('config/users/' . $u['id'] . '/delete') ?>"
                              class="d-inline" onsubmit="return confirm('Dezaktywować użytkownika?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-person-x"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
