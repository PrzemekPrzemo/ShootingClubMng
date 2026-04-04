<div class="row justify-content-center mt-5">
<div class="col-md-5 col-lg-4">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-3"><i class="bi bi-key"></i> Zmiana hasła</h2>
            <?php if ($firstLogin ?? false): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                Pierwsze logowanie wymaga ustawienia własnego hasła. Hasło musi mieć min. 8 znaków i nie może być numerem PESEL.
            </div>
            <?php endif; ?>
            <form method="post" action="<?= url('portal/change-password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Nowe hasło <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                    <div class="form-text">Minimum 8 znaków.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Powtórz hasło <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-danger w-100">Zapisz nowe hasło</button>
            </form>
        </div>
    </div>
</div>
</div>
