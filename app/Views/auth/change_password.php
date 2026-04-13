<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-key me-2"></i>Zmiana hasła</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('auth/change-password') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Obecne hasło <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required autocomplete="current-password">
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label class="form-label">Nowe hasło <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="8" autocomplete="new-password">
                        <div class="form-text">Minimum 8 znaków.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Powtórz nowe hasło <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Zmień hasło
                        </button>
                        <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
