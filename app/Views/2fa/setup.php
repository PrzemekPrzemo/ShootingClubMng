<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-shield-lock"></i> Dwuskładnikowe uwierzytelnianie (2FA)</h2>
</div>

<?php if ($is_enabled): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill"></i> <strong>2FA jest aktywne</strong> na Twoim koncie.
</div>

<div class="card mb-3" style="max-width:400px">
    <div class="card-body">
        <h6>Wyłącz 2FA</h6>
        <p class="text-muted small">Wprowadź aktualny kod z aplikacji, aby wyłączyć 2FA.</p>
        <form method="post" action="<?= url('2fa/disable') ?>">
            <?= csrf_field() ?>
            <div class="mb-2">
                <input type="text" name="code" class="form-control" placeholder="000000"
                       maxlength="6" pattern="\d{6}" inputmode="numeric" required autofocus>
            </div>
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-shield-x"></i> Wyłącz 2FA
            </button>
        </form>
    </div>
</div>

<?php else: ?>

<div class="row g-4" style="max-width:700px">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body text-center">
                <p class="small text-muted mb-2">Zeskanuj kodem QR aplikacją:</p>
                <img src="<?= e($qrUrl) ?>" alt="QR Code" width="180" height="180" class="mb-2">
                <hr>
                <p class="small text-muted mb-1">Lub wpisz klucz ręcznie:</p>
                <code class="d-block fs-6 fw-bold letter-spacing-2"><?= e($secret) ?></code>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-body">
                <ol class="small mb-3">
                    <li>Zainstaluj Google Authenticator lub Authy.</li>
                    <li>Dodaj nowe konto — zeskanuj QR lub wpisz klucz.</li>
                    <li>Wprowadź kod 6-cyfrowy poniżej, aby aktywować.</li>
                </ol>
                <form method="post" action="<?= url('2fa/enable') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Kod weryfikacyjny</label>
                        <input type="text" name="code" class="form-control form-control-lg text-center"
                               placeholder="000000" maxlength="6" pattern="\d{6}"
                               inputmode="numeric" required autofocus style="letter-spacing:8px;font-size:1.5rem">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-check"></i> Aktywuj 2FA
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
