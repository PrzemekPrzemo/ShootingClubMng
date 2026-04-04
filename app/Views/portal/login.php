<div class="row justify-content-center mt-5">
<div class="col-md-5 col-lg-4">
    <div class="text-center mb-4">
        <i class="bi bi-bullseye text-danger" style="font-size: 2.5rem"></i>
        <h1 class="h4 mt-2">Portal Zawodnika</h1>
        <p class="text-muted small">Zaloguj się, by zarządzać swoim kontem</p>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="post" action="<?= url('portal/login') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Adres e-mail</label>
                    <input type="email" name="email" class="form-control" required autofocus
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                    <div class="form-text">Przy pierwszym logowaniu użyj swojego numeru PESEL jako hasła.</div>
                </div>
                <button type="submit" class="btn btn-danger w-100">Zaloguj się</button>
            </form>
        </div>
    </div>
    <div class="text-center mt-3">
        <a href="<?= url('portal/reset-password') ?>" class="small text-muted">Zapomniałem/am hasła</a>
    </div>
</div>
</div>
