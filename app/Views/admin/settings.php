<h2 class="mb-4"><i class="bi bi-gear"></i> Ustawienia globalne</h2>

<a href="<?= url('admin/dashboard') ?>" class="btn btn-outline-secondary btn-sm mb-3">
    <i class="bi bi-arrow-left"></i> Panel admina
</a>

<form method="post" action="<?= url('admin/settings') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-palette"></i> Branding systemowy</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label for="system_name" class="form-label">Nazwa systemu</label>
                <input type="text" class="form-control" id="system_name" name="system_name"
                       value="<?= e($settings['system_name'] ?? 'Shootero') ?>" placeholder="Shootero">
                <div class="form-text">Wyświetlana na ekranie logowania i w pasku bocznym.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Logo systemu</label>
                <?php
                $logoValue   = $settings['system_logo'] ?? '';
                $logoInDb    = $logoValue !== '';
                $logoIsDb    = $logoValue === 'db';   // stored as base64 in DB
                $logoDir     = ROOT_PATH . '/storage/system/';
                $logoOnDisk  = !$logoIsDb && $logoInDb
                               && file_exists($logoDir . basename($logoValue));
                $logoActive  = $logoIsDb || $logoOnDisk;
                $dirWritable = is_dir($logoDir) && is_writable($logoDir);
                ?>

                <?php if ($logoActive): ?>
                <div class="mb-2 d-flex align-items-center gap-3">
                    <div class="p-2 rounded" style="background:#0F172A;border:1px solid rgba(255,255,255,.1)">
                        <img src="<?= url('admin/system-logo') ?>?v=<?= time() ?>"
                             alt="Logo systemu" style="height:48px; max-width:200px; object-fit:contain; display:block">
                    </div>
                    <div>
                        <div class="small text-success mb-1">
                            <i class="bi bi-check-circle me-1"></i>
                            Logo aktywne
                            <?= $logoIsDb
                                ? '<span class="badge bg-info text-dark ms-1">zapisane w bazie danych</span>'
                                : '<span class="badge bg-secondary ms-1">plik na dysku</span>' ?>
                        </div>
                        <button type="submit" name="delete_logo" value="1"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Usunąć logo systemu?')">
                            <i class="bi bi-trash me-1"></i>Usuń logo
                        </button>
                    </div>
                </div>
                <div class="small text-muted mb-2">Prześlij nowy plik, aby zastąpić aktualne logo.</div>
                <?php elseif ($logoInDb && !$logoActive): ?>
                <div class="alert alert-warning small py-2 mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Logo zapisane w bazie, ale plik nie istnieje na dysku. Wgraj ponownie.
                </div>
                <?php endif; ?>

                <!-- Storage info -->
                <?php if (!$logoIsDb): ?>
                <div class="alert alert-<?= $dirWritable ? 'success' : 'info' ?> small py-2 mb-2">
                    <?php if ($dirWritable): ?>
                        <i class="bi bi-check-circle me-1"></i>
                        Katalog <code>storage/system/</code> zapisywalny — logo zapisane jako plik.
                        Limit upload: <strong><?= (int)ini_get('upload_max_filesize') ?>M</strong>.
                    <?php else: ?>
                        <i class="bi bi-info-circle me-1"></i>
                        Katalog <code>storage/system/</code> niezapisywalny — logo zostanie zapisane
                        <strong>w bazie danych</strong> (działa automatycznie, bez potrzeby konfiguracji serwera).
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <input type="file" class="form-control" name="system_logo" accept=".png,.jpg,.jpeg,.svg,.webp">
                <div class="form-text">
                    PNG / JPG / SVG / WebP — rekomendowana wysokość 48–64px, preferowane jasne logo na ciemnym tle.
                    Logo pojawi się na stronie logowania i w pasku bocznym.
                </div>
            </div>
        </div>
    </div>

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

<div class="card mt-4" style="max-width:700px">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-code-slash me-2"></i>Globalny klucz API</h5></div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Globalny klucz umożliwia dostęp do <code>/api/v1/</code> dla wszystkich klubów bez potrzeby
            ustawiania klucza per klub. Używaj wyłącznie w zaufanych integracjach.
        </p>
        <?php $gk = $settings['global_api_key'] ?? ''; ?>
        <?php if ($gk): ?>
        <div class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control font-monospace small" id="gApiKey"
                       value="<?= e($gk) ?>" readonly>
                <button class="btn btn-outline-secondary btn-sm"
                        onclick="navigator.clipboard.writeText(document.getElementById('gApiKey').value)">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
        <form method="post" action="<?= url('admin/settings/regenerate-api-key') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-warning btn-sm"
                    onclick="return confirm('Wygenerować nowy globalny klucz API?')">
                <i class="bi bi-arrow-repeat me-1"></i><?= $gk ? 'Wygeneruj nowy' : 'Wygeneruj globalny klucz API' ?>
            </button>
        </form>
    </div>
</div>
