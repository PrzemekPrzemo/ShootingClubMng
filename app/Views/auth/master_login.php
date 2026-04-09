<div class="card shadow border-danger">
    <div class="card-header bg-danger text-white text-center py-3">
        <i class="bi bi-shield-lock-fill fs-3"></i>
        <h5 class="mb-0 mt-1 fw-bold">Panel administratora systemu</h5>
        <small class="opacity-75">Dostęp wyłącznie dla superadmina</small>
    </div>
    <div class="card-body p-4">
        <form method="post" action="<?= url('masterlogin') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Login</label>
                <input type="text" class="form-control" id="username" name="username"
                       required autofocus autocomplete="username">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Hasło</label>
                <input type="password" class="form-control" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-danger w-100 fw-semibold">
                <i class="bi bi-box-arrow-in-right"></i> Zaloguj się jako administrator
            </button>
        </form>
    </div>
</div>

<div class="text-center mt-3 small text-muted">
    <a href="<?= url('auth/login') ?>"><i class="bi bi-arrow-left"></i> Logowanie klubowe</a>
</div>
