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

<hr class="my-4" style="max-width:700px">

<h3 class="h5 mb-3"><i class="bi bi-phone me-2"></i>Powiadomienia SMS (SMSAPI.pl)</h3>

<form method="post" action="<?= url('club/smtp') ?>" style="max-width:700px">
    <?= csrf_field() ?>
    <input type="hidden" name="_section" value="sms">

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check mb-3">
                <input type="hidden" name="sms_enabled" value="0">
                <input type="checkbox" class="form-check-input" id="sms_enabled" name="sms_enabled" value="1"
                       <?= !empty($settings['sms_enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="sms_enabled">
                    <strong>Włącz SMS dla tego klubu</strong>
                </label>
                <div class="form-text">
                    Wymaga konta w <a href="#" class="link-info">smsapi.pl</a>.
                    SMS są wysyłane automatycznie przy generowaniu przypomnień
                    (<code>cli/queue_reminders.php</code>).
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label for="sms_api_key" class="form-label">Klucz API SMSAPI.pl</label>
                    <input type="text" class="form-control font-monospace" id="sms_api_key" name="sms_api_key"
                           value="<?= e($settings['sms_api_key'] ?? '') ?>"
                           placeholder="Twój token OAuth z panelu SMSAPI">
                </div>
                <div class="col-md-4">
                    <label for="sms_sender" class="form-label">Nazwa nadawcy</label>
                    <input type="text" class="form-control" id="sms_sender" name="sms_sender"
                           value="<?= e($settings['sms_sender'] ?? '') ?>"
                           placeholder="MKS-Strzel" maxlength="11">
                    <div class="form-text">Maks. 11 znaków, tylko litery/cyfry.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted text-uppercase">
                    Typy przypomnień SMS
                </label>
                <ul class="text-muted small mb-0">
                    <li>Zawody — 3 dni przed startem (tylko zapisani zawodnicy z numerem telefonu)</li>
                    <li>Wygasająca licencja — 14 dni przed wygaśnięciem</li>
                    <li>Zaległa składka roczna — raz w roku</li>
                </ul>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-check-lg me-1"></i>Zapisz konfigurację SMS
    </button>
</form>
