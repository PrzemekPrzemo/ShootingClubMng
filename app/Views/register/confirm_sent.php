<div class="card shadow-sm text-center" style="max-width:480px;margin:0 auto">
    <div class="card-body p-5">
        <i class="bi bi-envelope-check text-success" style="font-size:3rem"></i>
        <h4 class="mt-3">Sprawdź skrzynkę e-mail</h4>
        <p class="text-muted">
            Wysłaliśmy link aktywacyjny na adres <strong><?= e($email) ?></strong>.
        </p>
        <p class="text-muted small">
            Kliknij link w wiadomości, aby aktywować konto klubu. Link jest ważny przez 48 godzin.
        </p>
        <p class="text-muted small">
            Jeśli nie widzisz wiadomości, sprawdź folder SPAM.
        </p>
        <a href="<?= url('auth/login') ?>" class="btn btn-outline-secondary btn-sm mt-2">
            <i class="bi bi-arrow-left"></i> Powrót do logowania
        </a>
    </div>
</div>
