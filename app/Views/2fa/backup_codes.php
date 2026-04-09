<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-key-fill"></i> Kody zapasowe 2FA</h2>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>Zapisz te kody w bezpiecznym miejscu!</strong><br>
    Każdy kod można użyć tylko raz. Jeśli utracisz dostęp do aplikacji 2FA, skorzystaj z kodu zapasowego.
</div>

<?php if (!empty($codes)): ?>
<div class="card" style="max-width:360px">
    <div class="card-body">
        <div class="row g-2">
        <?php foreach ($codes as $code): ?>
            <div class="col-6">
                <code class="d-block text-center py-2 bg-light rounded fw-bold fs-6"><?= e($code) ?></code>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<a href="<?= url('dashboard') ?>" class="btn btn-primary mt-3">
    <i class="bi bi-check2"></i> Zapisałem kody — przejdź dalej
</a>
<?php else: ?>
<div class="alert alert-secondary">
    Kody zostały już wyświetlone podczas aktywacji 2FA. Aby wygenerować nowe, wyłącz i włącz 2FA ponownie.
</div>
<a href="<?= url('2fa/setup') ?>" class="btn btn-outline-primary">← Wróć do ustawień 2FA</a>
<?php endif; ?>
