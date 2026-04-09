<div class="container" style="max-width:380px;margin-top:80px">
    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <i class="bi bi-shield-lock-fill text-primary" style="font-size:2.5rem"></i>
            <h4 class="mt-2 mb-1">Weryfikacja 2FA</h4>
            <p class="text-muted small mb-3">Wprowadź 6-cyfrowy kod z aplikacji autentykacyjnej lub jeden z kodów zapasowych.</p>

            <form method="post" action="<?= url('2fa/verify') ?>">
                <?= csrf_field() ?>
                <input type="text" name="code" class="form-control form-control-lg text-center mb-3"
                       placeholder="000000" maxlength="8" inputmode="numeric"
                       autofocus style="letter-spacing:6px;font-size:1.4rem">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-unlock"></i> Zweryfikuj
                </button>
            </form>

            <div class="mt-3 text-muted small">
                <i class="bi bi-info-circle"></i>
                Nie masz dostępu do aplikacji? Użyj jednego z 8-znakowych kodów zapasowych.
            </div>
        </div>
    </div>
</div>
