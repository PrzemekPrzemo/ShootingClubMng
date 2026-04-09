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
</div>
