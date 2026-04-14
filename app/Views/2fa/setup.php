<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-shield-lock"></i> Bezpieczeństwo konta</h2>
</div>

<div class="row g-4" style="max-width:900px">

    <!-- ── Kolumna lewa: 2FA ──────────────────────────────────────── -->
    <div class="col-lg-6">
        <h5 class="mb-3"><i class="bi bi-phone"></i> Dwuskładnikowe uwierzytelnianie (2FA)</h5>

        <?php if ($is_enabled): ?>
        <div class="alert alert-success py-2">
            <i class="bi bi-check-circle-fill"></i> <strong>2FA jest aktywne</strong> na Twoim koncie.
        </div>
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Wyłącz 2FA</h6>
                <p class="text-muted small mb-2">Wprowadź aktualny kod z aplikacji, aby wyłączyć 2FA.</p>
                <form method="post" action="<?= url('2fa/disable') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <input type="text" name="code" class="form-control" placeholder="000000"
                               maxlength="6" pattern="\d{6}" inputmode="numeric" required>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-shield-x"></i> Wyłącz 2FA
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <div class="alert alert-warning py-2">
            <i class="bi bi-shield-exclamation"></i> 2FA nie jest aktywne — zalecamy włączenie.
        </div>
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-3">
                    <p class="small text-muted mb-2">Zeskanuj aplikacją Google Authenticator / Authy:</p>
                    <img src="<?= e($qrUrl) ?>" alt="QR Code" width="160" height="160" class="mb-2">
                    <hr>
                    <p class="small text-muted mb-1">Lub wpisz klucz ręcznie:</p>
                    <code class="d-block fw-bold"><?= e($secret) ?></code>
                </div>
                <form method="post" action="<?= url('2fa/enable') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label small">Kod weryfikacyjny</label>
                        <input type="text" name="code" class="form-control form-control-lg text-center"
                               placeholder="000000" maxlength="6" pattern="\d{6}"
                               inputmode="numeric" required style="letter-spacing:8px;font-size:1.5rem">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-shield-check"></i> Aktywuj 2FA
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Kolumna prawa: Zmiana hasła ───────────────────────────── -->
    <div class="col-lg-6">
        <h5 class="mb-3"><i class="bi bi-key"></i> Zmiana hasła</h5>

        <?php if (!empty($pw_error)): ?>
        <div class="alert alert-danger py-2"><?= e($pw_error) ?></div>
        <?php endif; ?>
        <?php if (!empty($pw_success)): ?>
        <div class="alert alert-success py-2"><?= e($pw_success) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= url('account/change-password') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Aktualne hasło</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nowe hasło</label>
                        <input type="password" name="new_password" class="form-control"
                               minlength="8" required>
                        <div class="form-text">Minimum 8 znaków.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Powtórz nowe hasło</label>
                        <input type="password" name="confirm_password" class="form-control"
                               minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-key"></i> Zmień hasło
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
