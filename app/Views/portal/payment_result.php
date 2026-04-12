<?php
$status    = $payment['status'] ?? 'pending';
$amount    = number_format((float)($payment['amount'] ?? 0), 2, ',', ' ');
$orderId   = $payment['p24_order_id'] ?? null;
$sessionId = $payment['p24_session_id'] ?? '';
$desc      = e($payment['description'] ?? '');
?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        <?php if ($status === 'verified'): ?>
        <div class="text-center mb-4">
            <div style="font-size:4rem;line-height:1">&#9989;</div>
            <h2 class="mt-3 fw-bold" style="color:#22c55e">Płatność zrealizowana</h2>
            <p class="text-muted">Twoja wpłata została potwierdzona przez Przelewy24.</p>
        </div>

        <?php elseif ($status === 'pending'): ?>
        <div class="text-center mb-4">
            <div style="font-size:4rem;line-height:1">&#x23F3;</div>
            <h2 class="mt-3 fw-bold" style="color:#f59e0b">Oczekujemy na potwierdzenie</h2>
            <p class="text-muted">Twoja płatność jest przetwarzana. Status zostanie zaktualizowany automatycznie.</p>
        </div>

        <?php elseif ($status === 'failed'): ?>
        <div class="text-center mb-4">
            <div style="font-size:4rem;line-height:1">&#10060;</div>
            <h2 class="mt-3 fw-bold" style="color:#ef4444">Płatność nieudana</h2>
            <p class="text-muted">Transakcja nie powiodła się lub została przerwana.</p>
        </div>

        <?php else: ?>
        <div class="text-center mb-4">
            <div style="font-size:4rem;line-height:1">&#x1F6AB;</div>
            <h2 class="mt-3 fw-bold">Transakcja anulowana</h2>
            <p class="text-muted">Transakcja została anulowana.</p>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-receipt me-2"></i>Szczegóły transakcji
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted small">Opis</dt>
                    <dd class="col-7"><?= $desc ?></dd>

                    <dt class="col-5 text-muted small">Kwota</dt>
                    <dd class="col-7 fw-bold"><?= $amount ?> PLN</dd>

                    <?php if ($orderId): ?>
                    <dt class="col-5 text-muted small">Nr transakcji P24</dt>
                    <dd class="col-7 font-monospace small"><?= e($orderId) ?></dd>
                    <?php endif; ?>

                    <dt class="col-5 text-muted small">ID sesji</dt>
                    <dd class="col-7 font-monospace small" style="font-size:.72rem;word-break:break-all"><?= e($sessionId) ?></dd>

                    <dt class="col-5 text-muted small">Status</dt>
                    <dd class="col-7">
                        <?php
                        $badgeMap = [
                            'verified'  => 'bg-success',
                            'pending'   => 'bg-warning text-dark',
                            'failed'    => 'bg-danger',
                            'cancelled' => 'bg-secondary',
                        ];
                        $labelMap = [
                            'verified'  => 'Zweryfikowana',
                            'pending'   => 'Oczekująca',
                            'failed'    => 'Nieudana',
                            'cancelled' => 'Anulowana',
                        ];
                        ?>
                        <span class="badge <?= $badgeMap[$status] ?? 'bg-secondary' ?>">
                            <?= $labelMap[$status] ?? $status ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>

        <?php if ($status === 'failed'): ?>
        <div class="alert alert-warning mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            Możesz spróbować ponownie lub skontaktować się z biurem klubu.
        </div>
        <?php endif; ?>

        <?php if ($status === 'pending'): ?>
        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            Jeśli płatność została zrealizowana, status zaktualizuje się automatycznie w ciągu kilku minut.
        </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= url('portal/fees') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Wróć do opłat
            </a>
            <?php if ($status === 'failed'): ?>
            <a href="<?= url('portal/fees') ?>" class="btn btn-outline-warning">
                <i class="bi bi-arrow-repeat me-1"></i> Spróbuj ponownie
            </a>
            <?php endif; ?>
        </div>

    </div>
</div>
