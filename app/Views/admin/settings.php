<h2 class="mb-4"><i class="bi bi-gear"></i> Ustawienia globalne</h2>

<a href="<?= url('admin/dashboard') ?>" class="btn btn-outline-secondary btn-sm mb-3">
    <i class="bi bi-arrow-left"></i> Panel admina
</a>

<form method="post" action="<?= url('admin/settings') ?>">
    <?= csrf_field() ?>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">System</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label for="base_domain" class="form-label">Domena bazowa (np. system.pl)</label>
                <input type="text" class="form-control" id="base_domain" name="base_domain"
                       value="<?= e($settings['base_domain'] ?? '') ?>"
                       placeholder="system.pl">
                <div class="form-text">Subdomeny klubów: mks-gdansk.<strong>system.pl</strong></div>
            </div>
            <div class="form-check mb-3">
                <input type="hidden" name="allow_club_smtp" value="0">
                <input type="checkbox" class="form-check-input" id="allow_club_smtp" name="allow_club_smtp" value="1"
                       <?= !empty($settings['allow_club_smtp']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="allow_club_smtp">
                    Zezwól klubom na własną konfigurację SMTP
                </label>
            </div>
        </div>
    </div>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">Globalny SMTP</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="smtp_host" class="form-label">Host</label>
                    <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                           value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-4">
                    <label for="smtp_port" class="form-label">Port</label>
                    <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                           value="<?= e($settings['smtp_port'] ?? '587') ?>">
                </div>
            </div>
            <div class="row g-3 mt-2">
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
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Zapisz ustawienia
    </button>
</form>
