<?php
$loginIcon = '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:64px;height:64px;display:block;margin:0 auto;filter:drop-shadow(0 4px 16px rgba(212,163,115,.3))">
  <path d="M22 3 L3 8 L8 20 L30 12 Z" fill="#D4A373"/>
  <path d="M38 57 L57 52 L52 40 L30 48 Z" fill="#D4A373"/>
  <circle cx="30" cy="30" r="18" stroke="rgba(226,232,240,.4)" stroke-width="2" fill="none"/>
  <path d="M42.7 17.3 A18 18 0 0 1 47.3 30" stroke="rgba(226,232,240,.75)" stroke-width="2.2" fill="none" stroke-linecap="round"/>
  <circle cx="30" cy="30" r="11" stroke="rgba(226,232,240,.88)" stroke-width="2.4" fill="none"/>
  <line x1="30" y1="12" x2="30" y2="19" stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <line x1="30" y1="41" x2="30" y2="48" stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <line x1="12" y1="30" x2="19" y2="30" stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <line x1="41" y1="30" x2="48" y2="30" stroke="rgba(226,232,240,.9)" stroke-width="2.2" stroke-linecap="round"/>
  <circle cx="30" cy="30" r="5" fill="#D4A373"/>
  <circle cx="30" cy="30" r="2.5" fill="#E6C200"/>
</svg>';
?>
<div class="text-center mb-4">
    <?php if (!empty($systemBranding['logo'])): ?>
        <img src="<?= url('system-logo') ?>" alt="<?= e($systemBranding['name']) ?>"
             style="height:54px;max-width:200px;object-fit:contain" class="mb-3 d-block mx-auto">
    <?php else: ?>
        <?= $loginIcon ?>
    <?php endif; ?>
    <h4 class="mt-3 mb-0" style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.65rem;letter-spacing:4px;text-transform:uppercase;color:#fff;line-height:1.1">
        <?= e($systemBranding['name'] ?? 'SHOOTERO') ?>
    </h4>
    <p class="mt-2 mb-0" style="color:#D4A373;font-family:'Poppins',sans-serif;font-weight:500;letter-spacing:2px;font-size:.7rem;text-transform:uppercase">
        Zarządzaj klubem&nbsp;&nbsp;•&nbsp;&nbsp;Wspieraj ludzi
    </p>
</div>

<!-- Context selector -->
<div class="btn-group w-100 mb-4" role="group">
    <button type="button" class="btn btn-primary ctx-btn active" data-ctx="member">
        <i class="bi bi-person-fill me-1"></i>Zawodnik
    </button>
    <button type="button" class="btn btn-outline-secondary ctx-btn" data-ctx="staff">
        <i class="bi bi-shield-lock me-1"></i>Zarządzanie
    </button>
</div>

<!-- Member login form -->
<div id="form-member">
    <div class="mb-3">
        <label class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">Adres e-mail</label>
        <form method="post" action="<?= url('portal/login') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="_ctx" value="member">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" required autofocus
                       placeholder="twoj@email.pl"
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">Hasło</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password"
                       placeholder="Hasło lub PESEL (pierwsze logowanie)">
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold"
                    style="font-family:'Poppins',sans-serif;letter-spacing:.5px;padding:.65rem">
                <i class="bi bi-box-arrow-in-right me-1"></i>Zaloguj się
            </button>
        </form>
    </div>
    <div class="text-center mt-3">
        <a href="<?= url('portal/reset-password') ?>" class="small" style="color:#475569">Zapomniałem/am hasła</a>
    </div>
</div>

<!-- Staff login form -->
<div id="form-staff" style="display:none">
    <form method="post" action="<?= url('auth/login') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">Login</label>
            <input type="text" name="username" class="form-control" required autocomplete="username"
                   placeholder="Login administratora"
                   value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold small" style="color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;font-size:.72rem">Hasło</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password"
                   placeholder="Hasło">
        </div>
        <button type="submit" class="btn btn-outline-secondary w-100 fw-semibold"
                style="font-family:'Poppins',sans-serif;letter-spacing:.5px;padding:.65rem">
            <i class="bi bi-box-arrow-in-right me-1"></i>Zaloguj się
        </button>
    </form>
</div>

<div class="text-center mt-4 pt-2" style="border-top:1px solid rgba(255,255,255,.06)">
    <p class="mb-0" style="color:#334155;font-size:.72rem">
        &copy; <?= date('Y') ?>
        <span style="font-family:'Poppins',sans-serif;font-weight:800;letter-spacing:1px;color:#1e293b">
            <?= e($systemBranding['name'] ?? 'Shootero') ?>
        </span>
    </p>
</div>

<script>
(function () {
    var btns    = document.querySelectorAll('.ctx-btn');
    var fMember = document.getElementById('form-member');
    var fStaff  = document.getElementById('form-staff');

    function activate(ctx) {
        btns.forEach(function (b) {
            var isThis = b.dataset.ctx === ctx;
            b.classList.toggle('active', isThis);
            if (b.dataset.ctx === 'member') {
                b.classList.toggle('btn-primary', isThis);
                b.classList.toggle('btn-outline-secondary', !isThis);
            } else {
                b.classList.toggle('btn-secondary', isThis);
                b.classList.toggle('btn-outline-secondary', !isThis);
            }
        });
        fMember.style.display = ctx === 'member' ? '' : 'none';
        fStaff.style.display  = ctx === 'staff'  ? '' : 'none';
        var first = (ctx === 'member' ? fMember : fStaff).querySelector('input');
        if (first) first.focus();
        try { localStorage.setItem('loginCtx', ctx); } catch(e) {}
    }

    btns.forEach(function (b) {
        b.addEventListener('click', function () { activate(b.dataset.ctx); });
    });

    try {
        var saved = localStorage.getItem('loginCtx');
        if (saved === 'staff') { activate('staff'); }
    } catch(e) {}
})();
</script>
