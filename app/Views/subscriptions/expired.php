<div class="d-flex justify-content-center align-items-center" style="min-height:60vh">
    <div class="text-center" style="max-width:500px">
        <div class="mb-4">
            <i class="bi bi-lock-fill text-danger" style="font-size:4rem"></i>
        </div>
        <h2 class="h3 mb-3">Subskrypcja wygasła</h2>
        <p class="text-muted mb-4">
            Subskrypcja Twojego klubu wygasła lub osiągnięto limit planu.
            Aby odblokować dostęp, skontaktuj się z administratorem systemu.
        </p>
        <div class="alert alert-info text-start">
            <i class="bi bi-info-circle"></i>
            <strong>Co dalej?</strong><br>
            Dane Twojego klubu są bezpieczne. Po odnowieniu subskrypcji
            wszystkie funkcje zostaną przywrócone automatycznie.
        </div>
        <a href="<?= url('auth/logout') ?>" class="btn btn-outline-secondary mt-2">
            <i class="bi bi-box-arrow-left"></i> Wyloguj się
        </a>
    </div>
</div>
