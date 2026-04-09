<div class="card shadow-sm text-center border-danger" style="max-width:480px;margin:0 auto">
    <div class="card-body p-5">
        <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem"></i>
        <h4 class="mt-3">Błąd aktywacji</h4>
        <p class="text-muted"><?= e($message ?? 'Nieprawidłowy lub wygasły link aktywacyjny.') ?></p>
        <a href="<?= url('register') ?>" class="btn btn-outline-secondary btn-sm mt-2">
            <i class="bi bi-arrow-repeat"></i> Spróbuj ponownie
        </a>
    </div>
</div>
