<h2 class="mb-4"><i class="bi bi-palette"></i> Wygląd klubu</h2>

<form method="post" action="<?= url('club/customization') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">Logo i kolory</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label for="logo" class="form-label">Logo klubu</label>
                <?php if (!empty($custom['logo_path'])): ?>
                    <div class="mb-2">
                        <img src="<?= url('club/logo') ?>" alt="Logo" style="max-height:64px" class="border rounded p-1">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                <div class="form-text">PNG, JPG, SVG lub WebP. Zalecany rozmiar: 200x200px.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="primary_color" class="form-label">Kolor przewodni</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="primary_color_picker"
                               value="<?= e($custom['primary_color'] ?? '#0d6efd') ?>"
                               oninput="document.getElementById('primary_color').value=this.value">
                        <input type="text" class="form-control" id="primary_color" name="primary_color"
                               value="<?= e($custom['primary_color'] ?? '#0d6efd') ?>" maxlength="20">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="navbar_bg" class="form-label">Tło paska nawigacji</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="navbar_bg_picker"
                               value="<?= e($custom['navbar_bg'] ?? '#212529') ?>"
                               oninput="document.getElementById('navbar_bg').value=this.value">
                        <input type="text" class="form-control" id="navbar_bg" name="navbar_bg"
                               value="<?= e($custom['navbar_bg'] ?? '#212529') ?>" maxlength="20">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">Subdomena</h5></div>
        <div class="card-body">
            <div class="mb-3">
                <label for="subdomain" class="form-label">Subdomena klubu</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="subdomain" name="subdomain"
                           value="<?= e($custom['subdomain'] ?? '') ?>"
                           placeholder="moj-klub" pattern="[a-z0-9\-]+">
                    <span class="input-group-text">.system.pl</span>
                </div>
                <div class="form-text">Tylko małe litery, cyfry i myślniki. Zostaw puste aby wyłączyć.</div>
            </div>
        </div>
    </div>

    <div class="card mb-4" style="max-width:700px">
        <div class="card-header"><h5 class="mb-0">Własny CSS</h5></div>
        <div class="card-body">
            <textarea class="form-control font-monospace" name="custom_css" rows="5"
                      placeholder="/* dodatkowe style CSS */"><?= e($custom['custom_css'] ?? '') ?></textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Zapisz wygląd
    </button>
</form>
