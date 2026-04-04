<div class="row justify-content-center mt-5">
<div class="col-md-5 col-lg-4">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-1"><i class="bi bi-arrow-counterclockwise"></i> Reset hasła</h2>
            <p class="text-muted small mb-3">Podaj swój adres e-mail i numer PESEL — hasło zostanie zresetowane do numeru PESEL.</p>
            <form method="post" action="<?= url('portal/reset-password') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Adres e-mail</label>
                    <input type="email" name="email" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Numer PESEL</label>
                    <input type="text" name="pesel" class="form-control" required pattern="\d{11}" maxlength="11"
                           placeholder="11 cyfr">
                </div>
                <button type="submit" class="btn btn-danger w-100">Zresetuj hasło</button>
                <a href="<?= url('portal/login') ?>" class="btn btn-outline-secondary w-100 mt-2">Powrót do logowania</a>
            </form>
        </div>
    </div>
</div>
</div>
