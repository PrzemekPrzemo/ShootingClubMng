<h2 class="mb-4"><i class="bi bi-people"></i> Kadra klubu</h2>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Imię i nazwisko</th>
                    <th>Login</th>
                    <th>E-mail</th>
                    <th>Rola</th>
                    <th>Ostatnie logowanie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['full_name']) ?></td>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><span class="badge bg-info"><?= e($user['club_role']) ?></span></td>
                    <td><?= $user['last_login'] ? e($user['last_login']) : '<span class="text-muted">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="5" class="text-muted text-center py-3">Brak przypisanych użytkowników</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    <i class="bi bi-info-circle"></i>
    Zarządzanie użytkownikami (dodawanie/usuwanie) jest dostępne dla administratora systemu.
</p>
