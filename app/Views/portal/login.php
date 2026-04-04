<div class="text-center mb-4">
    <i class="bi bi-bullseye text-danger" style="font-size:2.75rem"></i>
    <h1 class="h4 mt-2 fw-bold">Klub Strzelecki</h1>
    <p class="text-muted small mb-0">Wybierz kontekst logowania</p>
</div>

<!-- Context selector -->
<div class="btn-group w-100 mb-4" role="group">
    <button type="button" class="btn btn-danger ctx-btn active" data-ctx="member">
        <i class="bi bi-person-fill"></i> Zawodnik
    </button>
    <button type="button" class="btn btn-outline-secondary ctx-btn" data-ctx="staff">
        <i class="bi bi-shield-lock"></i> Zarządzanie
    </button>
</div>

<!-- Member login form -->
<div id="form-member">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h6 class="text-muted small mb-3 text-uppercase fw-semibold">Logowanie zawodnika</h6>
            <form method="post" action="<?= url('portal/login') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_ctx" value="member">
                <div class="mb-3">
                    <label class="form-label">Adres e-mail</label>
                    <input type="email" name="email" class="form-control" required autofocus
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                    <div class="form-text">Przy pierwszym logowaniu użyj numeru PESEL jako hasła.</div>
                </div>
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Zaloguj się
                </button>
            </form>
        </div>
    </div>
    <div class="text-center mt-2">
        <a href="<?= url('portal/reset-password') ?>" class="small text-muted">Zapomniałem/am hasła</a>
    </div>
</div>

<!-- Staff login form -->
<div id="form-staff" style="display:none">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h6 class="text-muted small mb-3 text-uppercase fw-semibold">Logowanie — zarządzanie</h6>
            <form method="post" action="<?= url('auth/login') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Login</label>
                    <input type="text" name="username" class="form-control" required autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Zaloguj się
                </button>
            </form>
        </div>
    </div>
</div>

<p class="text-center text-muted small mt-4">&copy; <?= date('Y') ?> Klub Strzelecki</p>

<script>
(function () {
    var btns   = document.querySelectorAll('.ctx-btn');
    var fMember = document.getElementById('form-member');
    var fStaff  = document.getElementById('form-staff');

    function activate(ctx) {
        btns.forEach(function (b) {
            var isThis = b.dataset.ctx === ctx;
            b.classList.toggle('active', isThis);
            b.classList.toggle('btn-danger', isThis && ctx === 'member');
            b.classList.toggle('btn-secondary', isThis && ctx === 'staff');
            b.classList.toggle('btn-outline-secondary', !isThis && ctx === 'member');
            b.classList.toggle('btn-outline-danger', !isThis && ctx === 'staff');
        });
        fMember.style.display = ctx === 'member' ? '' : 'none';
        fStaff.style.display  = ctx === 'staff'  ? '' : 'none';
        // Auto-focus first input
        var first = (ctx === 'member' ? fMember : fStaff).querySelector('input');
        if (first) first.focus();
        try { localStorage.setItem('loginCtx', ctx); } catch(e) {}
    }

    btns.forEach(function (b) {
        b.addEventListener('click', function () { activate(b.dataset.ctx); });
    });

    // Restore last used context
    try {
        var saved = localStorage.getItem('loginCtx');
        if (saved === 'staff') { activate('staff'); }
    } catch(e) {}
})();
</script>
