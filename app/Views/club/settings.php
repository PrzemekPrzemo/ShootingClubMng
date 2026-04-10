<h2 class="mb-4"><i class="bi bi-gear"></i> Ustawienia klubu</h2>

<form method="post" action="<?= url('club/settings') ?>">
    <?= csrf_field() ?>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">Dane klubu</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label for="name" class="form-label">Nazwa klubu *</label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?= e($club['name'] ?? '') ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="short_name" class="form-label">Skrót</label>
                    <input type="text" class="form-control" id="short_name" name="short_name"
                           value="<?= e($club['short_name'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                    <label for="email" class="form-label">E-mail klubu</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($club['email'] ?? '') ?>">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="<?= e($club['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label for="nip" class="form-label">NIP</label>
                    <input type="text" class="form-control" id="nip" name="nip"
                           value="<?= e($club['nip'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <textarea class="form-control" id="address" name="address" rows="2"><?= e($club['address'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">Powiadomienia</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="notify_comp_days" class="form-label">Zawody (dni przed)</label>
                    <input type="number" class="form-control" id="notify_comp_days" name="notify_comp_days"
                           value="<?= (int)($settings['notify_comp_days'] ?? 7) ?>" min="1">
                </div>
                <div class="col-md-4">
                    <label for="notify_lic_days" class="form-label">Licencje (dni przed)</label>
                    <input type="number" class="form-control" id="notify_lic_days" name="notify_lic_days"
                           value="<?= (int)($settings['notify_lic_days'] ?? 30) ?>" min="1">
                </div>
                <div class="col-md-4">
                    <label for="notify_med_days" class="form-label">Badania (dni przed)</label>
                    <input type="number" class="form-control" id="notify_med_days" name="notify_med_days"
                           value="<?= (int)($settings['notify_med_days'] ?? 30) ?>" min="1">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Zapisz
    </button>
</form>

<div class="card mt-4" style="max-width:700px">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-code-slash me-2"></i>API — klucz dostępu</h5></div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Klucz API umożliwia odczyt danych klubu przez aplikacje zewnętrzne (np. mobilne)
            przez endpointy <code>/api/v1/</code>. Trzymaj klucz w tajemnicy.
        </p>
        <?php
        $apiKey = $settings['api_key'] ?? '';
        ?>
        <?php if ($apiKey): ?>
        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted text-uppercase">Aktualny klucz</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace small" id="apiKeyVal"
                       value="<?= e($apiKey) ?>" readonly>
                <button class="btn btn-outline-secondary btn-sm"
                        onclick="navigator.clipboard.writeText(document.getElementById('apiKeyVal').value)">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
        <form method="post" action="<?= url('club/settings/regenerate-api-key') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-warning btn-sm"
                    onclick="return confirm('Wygenerować nowy klucz API? Stary klucz przestanie działać.')">
                <i class="bi bi-arrow-repeat me-1"></i><?= $apiKey ? 'Wygeneruj nowy klucz' : 'Wygeneruj klucz API' ?>
            </button>
        </form>
        <?php if ($apiKey): ?>
        <div class="mt-3">
            <p class="text-muted small mb-1">Przykładowe wywołanie:</p>
            <pre class="p-2 rounded small" style="background:rgba(0,0,0,.3);color:#94A3B8;font-size:.75rem">GET /api/v1/clubs/<?= e($club['short_name'] ?? 'klub') ?>/competitions
X-API-Key: <?= e($apiKey) ?></pre>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4 d-flex flex-wrap gap-2">
    <a href="<?= url('club/customization') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-palette"></i> Wygląd i branding
    </a>
    <a href="<?= url('club/smtp') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-send"></i> Konfiguracja SMTP
    </a>
    <a href="<?= url('club/email-templates') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-envelope-gear"></i> Szablony e-mail
    </a>
    <a href="<?= url('club/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people"></i> Użytkownicy klubu
    </a>
    <a href="<?= url('subscription') ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-credit-card-2-front"></i> Plan subskrypcji
    </a>
</div>
