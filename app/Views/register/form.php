<div class="card shadow-sm" style="max-width:600px;margin:0 auto">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-bullseye text-danger" style="font-size:2.5rem"></i>
            <h4 class="mt-2 mb-0 fw-bold">Rejestracja klubu</h4>
            <p class="text-muted small">Bezpłatny okres próbny 30 dni · Bez karty kredytowej</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= url('register') ?>">
            <?= csrf_field() ?>

            <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-1 mb-3">Dane klubu</h6>
            <div class="mb-3">
                <label class="form-label">Nazwa klubu <span class="text-danger">*</span></label>
                <input type="text" name="club_name" class="form-control" required
                       value="<?= e($old['club_name'] ?? '') ?>" placeholder="np. MKS Orzeł Warszawa">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-7">
                    <label class="form-label">E-mail klubu <span class="text-danger">*</span></label>
                    <input type="email" name="club_email" class="form-control" required
                           value="<?= e($old['club_email'] ?? '') ?>" placeholder="biuro@klub.pl">
                    <div class="form-text">Na ten adres wyślemy link aktywacyjny.</div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="club_phone" class="form-control"
                           value="<?= e($old['club_phone'] ?? '') ?>" placeholder="500 000 000">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">NIP</label>
                <input type="text" name="club_nip" class="form-control" style="max-width:180px"
                       value="<?= e($old['club_nip'] ?? '') ?>" placeholder="1234567890">
            </div>

            <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-1 mb-3 mt-4">Konto administratora</h6>
            <div class="mb-3">
                <label class="form-label">Imię i nazwisko <span class="text-danger">*</span></label>
                <input type="text" name="admin_name" class="form-control" required
                       value="<?= e($old['admin_name'] ?? '') ?>" placeholder="Jan Kowalski">
            </div>
            <div class="mb-3">
                <label class="form-label">E-mail administratora <span class="text-danger">*</span></label>
                <input type="email" name="admin_email" class="form-control" required
                       value="<?= e($old['admin_email'] ?? '') ?>" placeholder="admin@klub.pl">
                <div class="form-text">Ten adres służy do logowania do systemu.</div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Hasło <span class="text-danger">*</span></label>
                    <input type="password" name="admin_pass" class="form-control" required
                           placeholder="min. 8 znaków" autocomplete="new-password">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Powtórz hasło <span class="text-danger">*</span></label>
                    <input type="password" name="admin_pass2" class="form-control" required
                           placeholder="powtórz hasło" autocomplete="new-password">
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="acceptTerms" required>
                <label class="form-check-label" for="acceptTerms">
                    Akceptuję warunki korzystania z usługi i politykę prywatności (RODO).
                </label>
            </div>

            <button type="submit" class="btn btn-danger w-100">
                <i class="bi bi-check2-circle"></i> Zarejestruj klub
            </button>
        </form>

        <div class="text-center mt-3 small text-muted">
            Masz już konto? <a href="<?= url('auth/login') ?>">Zaloguj się</a>
        </div>
    </div>
</div>

<div class="text-center mt-4 small text-muted">
    <div class="d-flex justify-content-center gap-4">
        <div><i class="bi bi-shield-check text-success"></i> Dane bezpieczne (RODO)</div>
        <div><i class="bi bi-calendar-check text-success"></i> 30 dni bezpłatnie</div>
        <div><i class="bi bi-headset text-success"></i> Wsparcie techniczne</div>
    </div>
</div>
