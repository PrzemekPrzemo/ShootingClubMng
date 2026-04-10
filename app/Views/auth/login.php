<?php
// Shootero brand icon: S-bolt blades + metallic crosshair (large 64px version)
$loginIcon = '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:68px;height:68px;display:block;margin:0 auto;filter:drop-shadow(0 4px 16px rgba(212,163,115,.3))">
  <!-- Upper-left S-bolt blade -->
  <path d="M22 3 L3 8 L8 20 L30 12 Z" fill="#D4A373"/>
  <!-- Lower-right S-bolt blade -->
  <path d="M38 57 L57 52 L52 40 L30 48 Z" fill="#D4A373"/>
  <!-- Outer metallic ring -->
  <circle cx="30" cy="30" r="18" stroke="rgba(226,232,240,.4)" stroke-width="2" fill="none"/>
  <!-- Outer ring highlight arc -->
  <path d="M42.7 17.3 A18 18 0 0 1 47.3 30" stroke="rgba(226,232,240,.75)" stroke-width="2.2" fill="none" stroke-linecap="round"/>
  <!-- Inner bright ring -->
  <circle cx="30" cy="30" r="11" stroke="rgba(226,232,240,.88)" stroke-width="2.4" fill="none"/>
  <!-- Crosshair tick marks (between rings) -->
  <line x1="30" y1="12" x2="30" y2="19"  stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <line x1="30" y1="41" x2="30" y2="48"  stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <line x1="12" y1="30" x2="19" y2="30"  stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <line x1="41" y1="30" x2="48" y2="30"  stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <!-- Center dot -->
  <circle cx="30" cy="30" r="5" fill="#D4A373"/>
  <circle cx="30" cy="30" r="2.5" fill="#E6C200"/>
</svg>';
?>
<div class="text-center mb-4">
    <?php if (!empty($systemBranding['logo'])): ?>
        <img src="<?= url('admin/system-logo') ?>" alt="<?= e($systemBranding['name']) ?>"
             style="height:54px; max-width:200px; object-fit:contain" class="mb-3 d-block mx-auto">
    <?php else: ?>
        <?= $loginIcon ?>
    <?php endif; ?>

    <?php if (!empty($subdomainClub)): ?>
        <?php if (!empty($subdomainClub['logo_path'])): ?>
        <div class="d-flex align-items-center justify-content-center gap-3 mt-3 mb-1">
            <div class="border-end border-secondary pe-3">
                <span class="fw-bold" style="font-family:'Poppins',sans-serif;font-size:.8rem;letter-spacing:2px;color:#E6C200">
                    <?= e($systemBranding['name'] ?? 'SHOOTERO') ?>
                </span>
            </div>
            <img src="<?= url('club/logo') ?>" alt="<?= e($subdomainClub['name']) ?>"
                 style="height:38px; max-width:110px; object-fit:contain">
        </div>
        <?php endif; ?>
        <h5 class="mt-3 mb-0 fw-bold" style="font-family:'Poppins',sans-serif;color:#fff">
            <?= e($subdomainClub['name']) ?>
        </h5>
        <p class="small mt-1 mb-0" style="color:#D4A373;font-family:'Poppins',sans-serif;letter-spacing:.5px">
            <?= e($systemBranding['name'] ?? 'SHOOTERO') ?> — System zarządzania klubem
        </p>
    <?php else: ?>
        <h4 class="mt-3 mb-0" style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.65rem;letter-spacing:4px;text-transform:uppercase;color:#fff;line-height:1.1">
            <?= e($systemBranding['name'] ?? 'SHOOTERO') ?>
        </h4>
        <p class="mt-2 mb-0" style="color:#D4A373;font-family:'Poppins',sans-serif;font-weight:500;letter-spacing:2px;font-size:.7rem;text-transform:uppercase">
            Zarządzaj klubem&nbsp;&nbsp;•&nbsp;&nbsp;Wspieraj ludzi
        </p>
    <?php endif; ?>
</div>

<form method="post" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>

    <?php if (!empty($subdomainClub)): ?>
        <input type="hidden" name="club_id" value="<?= (int)$subdomainClub['id'] ?>">
    <?php else: ?>
    <div class="mb-3">
        <label for="club_id" class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">
            Klub
        </label>
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
            <i class="bi bi-exclamation-triangle me-1"></i>
            Brak aktywnych klubów. Skontaktuj się z administratorem systemu.
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="username" class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">
            Login
        </label>
        <input type="text" class="form-control" id="username" name="username"
               value="<?= e($_POST['username'] ?? '') ?>"
               required autofocus autocomplete="username"
               placeholder="Wpisz login">
    </div>

    <div class="mb-4">
        <label for="password" class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">
            Hasło
        </label>
        <input type="password" class="form-control" id="password" name="password"
               required autocomplete="current-password"
               placeholder="Wpisz hasło">
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-semibold"
            style="font-family:'Poppins',sans-serif;letter-spacing:.5px;padding:.65rem"
            <?= (empty($clubs) && empty($subdomainClub)) ? 'disabled' : '' ?>>
        <i class="bi bi-box-arrow-in-right me-1"></i> Zaloguj się
    </button>
</form>

<div class="text-center mt-4 pt-2" style="border-top:1px solid rgba(255,255,255,.06)">
    <span class="small" style="color:#475569">Nowy klub?</span>
    <a href="<?= url('register') ?>" class="small ms-1" style="color:#D4A373">Zarejestruj się bezpłatnie →</a>
</div>
<p class="text-center mt-2 mb-0" style="color:#334155;font-size:.72rem">
    &copy; <?= date('Y') ?> <?= e($systemBranding['name'] ?? 'Shootero') ?>
</p>
