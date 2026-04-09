<h2 class="mb-4"><i class="bi bi-envelope"></i> Konfiguracja SMTP</h2>

<?php if (!$allowSmtp): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Funkcja własnego SMTP jest wyłączona przez administratora systemu.
        E-maile będą wysyłane przez globalny serwer SMTP.
    </div>
<?php else: ?>
    <form method="post" action="<?= url('club/smtp') ?>">
        <?= csrf_field() ?>

        <div class="card mb-4" style="max-width:700px">
            <div class="card-header"><h5 class="mb-0">SMTP klubu</h5></div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="hidden" name="smtp_enabled" value="0">
                    <input type="checkbox" class="form-check-input" id="smtp_enabled" name="smtp_enabled" value="1"
                           <?= !empty($settings['smtp_enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="smtp_enabled">
                        <strong>Użyj własnego serwera SMTP</strong>
                    </label>
                    <div class="form-text">Gdy wyłączone, system korzysta z globalnego SMTP.</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="smtp_host" class="form-label">Host SMTP</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                               value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="col-md-4">
                        <label for="smtp_port" class="form-label">Port</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                               value="<?= e($settings['smtp_port'] ?? '587') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="smtp_secure" class="form-label">Szyfrowanie</label>
                        <select class="form-select" id="smtp_secure" name="smtp_secure">
                            <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Brak'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($settings['smtp_secure'] ?? 'tls') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="smtp_user" class="form-label">Login</label>
                        <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                               value="<?= e($settings['smtp_user'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="smtp_pass_enc" class="form-label">Hasło</label>
                        <input type="password" class="form-control" id="smtp_pass_enc" name="smtp_pass_enc"
                               value="<?= e($settings['smtp_pass_enc'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="smtp_from_email" class="form-label">E-mail nadawcy</label>
                        <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email"
                               value="<?= e($settings['smtp_from_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="smtp_from_name" class="form-label">Nazwa nadawcy</label>
                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name"
                               value="<?= e($settings['smtp_from_name'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Zapisz konfigurację
        </button>
    </form>
<?php endif; ?>
