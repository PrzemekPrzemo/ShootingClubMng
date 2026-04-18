<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-pencil-square"></i> Edytuj dane kontaktowe</h2>
    <a href="<?= url('portal/profile') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Wróć do profilu
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    Możesz edytować swoje dane kontaktowe (telefon, adres) oraz zdjęcie legitymacyjne.
    Imię, nazwisko, PESEL i e-mail może zmieniać wyłącznie administracja klubu.
</div>

<div class="card" style="max-width:540px">
    <div class="card-body">
        <form method="post" action="<?= url('portal/profile/edit') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="photo" class="form-label">Zdjęcie legitymacyjne</label>
                <?php if (!empty($member['photo_path'])): ?>
                <div class="mb-2">
                    <img src="<?= url('members/' . (int)$member['id'] . '/photo') ?>"
                         alt="Aktualne zdjęcie"
                         style="width:90px; height:110px; object-fit:cover; border:1px solid rgba(127,127,127,.2); border-radius:4px;">
                    <div class="small text-muted mt-1">Aktualne zdjęcie</div>
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png">
                <div class="form-text small">
                    Format: <strong>portretowe</strong> jak do dowodu osobistego (proporcja ok. 35×45 mm).
                    JPG lub PNG, maksymalnie <strong>2 MB</strong>.
                    Zdjęcie będzie widoczne dla administracji klubu przy weryfikacji na zawodach i do legitymacji.
                </div>
            </div>

            <hr class="my-3">

            <div class="mb-3">
                <label for="phone" class="form-label">Telefon</label>
                <input type="tel" class="form-control" id="phone" name="phone"
                       value="<?= e($member['phone'] ?? '') ?>" placeholder="+48 000 000 000">
            </div>

            <div class="mb-3">
                <label for="address_street" class="form-label">Ulica i numer</label>
                <input type="text" class="form-control" id="address_street" name="address_street"
                       value="<?= e($member['address_street'] ?? '') ?>" placeholder="ul. Przykładowa 1/2">
            </div>

            <div class="row g-2 mb-3">
                <div class="col-sm-4">
                    <label for="address_postal" class="form-label">Kod pocztowy</label>
                    <input type="text" class="form-control" id="address_postal" name="address_postal"
                           value="<?= e($member['address_postal'] ?? '') ?>" placeholder="00-000">
                </div>
                <div class="col-sm-8">
                    <label for="address_city" class="form-label">Miejscowość</label>
                    <input type="text" class="form-control" id="address_city" name="address_city"
                           value="<?= e($member['address_city'] ?? '') ?>" placeholder="Warszawa">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Zapisz zmiany
                </button>
                <a href="<?= url('portal/profile') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
