<?php
// $clubBranding dostępny z BaseController::render() — logo, kolory per klub
$hasClubBranding = !empty($clubBranding['logo_path']);
$primaryColor    = $clubBranding['primary_color'] ?? '#dc3545';
?>
<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <?php if ($hasClubBranding): ?>
                <img src="<?= url('club/logo') ?>" alt="Logo" style="max-height:64px" class="mb-2">
            <?php else: ?>
                <i class="bi bi-bullseye text-danger" style="font-size:2.5rem"></i>
            <?php endif; ?>
            <h4 class="mt-2 mb-0 fw-bold"><?= e($clubBranding['club_name'] ?? 'Klub Strzelecki') ?></h4>
            <p class="text-muted small">System zarządzania</p>
        </div>
        <form method="post" action="<?= url('auth/login') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Login</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= e(old('username')) ?>" required autofocus autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Hasło</label>
                <input type="password" class="form-control" id="password" name="password"
                       required autocomplete="current-password">
            </div>
            <button type="submit" class="btn w-100" style="background-color:<?= e($primaryColor) ?>;color:#fff">
                <i class="bi bi-box-arrow-in-right"></i> Zaloguj się
            </button>
        </form>
    </div>
</div>
<p class="text-center text-muted small mt-3">&copy; <?= date('Y') ?> <?= e($clubBranding['club_name'] ?? 'Klub Strzelecki') ?></p>
