<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <?php if (!empty($systemBranding['logo'])): ?>
                <img src="<?= url('admin/system-logo') ?>" alt="<?= e($systemBranding['name']) ?>"
                     style="height:48px; max-width:180px; object-fit:contain" class="mb-2 d-block mx-auto">
            <?php else: ?>
                <i class="bi bi-bullseye text-danger" style="font-size:2.5rem"></i>
            <?php endif; ?>

            <?php if (!empty($subdomainClub)): ?>
                <?php if (!empty($subdomainClub['logo_path'])): ?>
                <div class="d-flex align-items-center justify-content-center gap-3 mt-2 mb-1">
                    <span class="text-muted small">+</span>
                    <img src="<?= url('club/logo') ?>" alt="<?= e($subdomainClub['name']) ?>"
                         style="height:40px; max-width:120px; object-fit:contain">
                </div>
                <?php endif; ?>
                <h4 class="mt-2 mb-0 fw-bold"><?= e($subdomainClub['name']) ?></h4>
                <p class="text-muted small"><?= e($systemBranding['name']) ?> — System zarządzania klubem</p>
            <?php else: ?>
                <h4 class="mt-2 mb-0 fw-bold"><?= e($systemBranding['name'] ?? 'Shootero') ?></h4>
                <p class="text-muted small">System zarządzania klubem</p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= url('auth/login') ?>">
            <?= csrf_field() ?>

            <?php if (!empty($subdomainClub)): ?>
                <input type="hidden" name="club_id" value="<?= (int)$subdomainClub['id'] ?>">
            <?php else: ?>
            <div class="mb-3">
                <label for="club_id" class="form-label fw-semibold">Klub</label>
                <?php if (!empty($clubs)): ?>
                <select class="form-select" id="club_id" name="club_id" required>
                    <option value="">— Wybierz klub —</option>
                    <?php foreach ($clubs as $club): ?>
                        <option value="<?= (int)$club['id'] ?>"
                            <?= ((int)($_POST['club_id'] ?? 0) === (int)$club['id']) ? 'selected' : '' ?>>
                            <?= e($club['name']) ?><?= $club['short_name'] ? ' (' . e($club['short_name']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <div class="alert alert-warning small py-2 mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Brak aktywnych klubów. Skontaktuj się z administratorem systemu.
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Login</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       required autofocus autocomplete="username">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Hasło</label>
                <input type="password" class="form-control" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-danger w-100 fw-semibold"
                    <?= (empty($clubs) && empty($subdomainClub)) ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-right"></i> Zaloguj się
            </button>
        </form>
    </div>
</div>

<div class="text-center mt-3 small text-muted">
    Nowy klub? <a href="<?= url('register') ?>">Zarejestruj się bezpłatnie</a>
</div>
<p class="text-center text-muted small mt-1">&copy; <?= date('Y') ?> <?= e($systemBranding['name'] ?? 'Shootero') ?></p>
