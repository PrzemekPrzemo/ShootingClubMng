<h2 class="h4 mb-4"><i class="bi bi-cash"></i> Opłaty i składki</h2>

<div class="d-flex gap-2 mb-3">
    <?php foreach (range(date('Y') - 1, date('Y') + 1) as $y): ?>
        <a href="<?= url('portal/fees?year=' . $y) ?>"
           class="btn btn-sm <?= $year === $y ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= $y ?></a>
    <?php endforeach; ?>
</div>

<?php if ($payments): ?>
<div class="card mb-3">
    <div class="card-header"><strong>Wpłaty w <?= $year ?> roku</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Typ</th>
                    <th>Kwota</th>
                    <th>Data</th>
                    <th>Uwagi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['type_name'] ?? '—') ?></td>
                    <td><strong><?= format_money($p['amount']) ?></strong></td>
                    <td class="small"><?= format_date($p['payment_date']) ?></td>
                    <td class="small text-muted"><?= e($p['notes'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <th>Suma</th>
                    <th><?= format_money(array_sum(array_column($payments, 'amount'))) ?></th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Brak wpłat w <?= $year ?> roku.</div>
<?php endif; ?>

<?php if (!empty($p24Enabled)): ?>
<!-- ── Płatność online (Przelewy24) ───────────────────────────────────────── -->
<div class="card mb-3" style="border-color:rgba(212,163,115,.3)">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-credit-card-2-front" style="color:var(--sht-gold,#D4A373)"></i>
        <strong>Zapłać online — Przelewy24</strong>
        <?php if (!empty($p24Sandbox)): ?>
            <span class="badge bg-warning text-dark ms-auto">Tryb testowy</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Możesz uiścić składkę lub inną opłatę klubową bezpiecznie przez Przelewy24
            (BLIK, karta, przelew online i ponad 100 innych metod).
        </p>

        <!-- Recent online payments -->
        <?php if (!empty($onlinePayments)): ?>
        <div class="mb-3">
            <p class="small fw-semibold text-muted mb-2">Historia płatności online:</p>
            <?php foreach ($onlinePayments as $op):
                $badgeMap = ['verified'=>'bg-success','pending'=>'bg-warning text-dark','failed'=>'bg-danger','cancelled'=>'bg-secondary'];
                $labelMap = ['verified'=>'Zweryfikowana','pending'=>'Oczekuje','failed'=>'Nieudana','cancelled'=>'Anulowana'];
            ?>
            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <div>
                    <span class="small"><?= e($op['description']) ?></span>
                    <span class="text-muted small ms-2"><?= date('d.m.Y', strtotime($op['created_at'])) ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <strong class="small"><?= number_format((float)$op['amount'], 2, ',', ' ') ?> zł</strong>
                    <span class="badge <?= $badgeMap[$op['status']] ?? 'bg-secondary' ?>">
                        <?= $labelMap[$op['status']] ?? $op['status'] ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Initiate payment form -->
        <button class="btn btn-sm btn-outline-primary mb-2" type="button"
                data-bs-toggle="collapse" data-bs-target="#p24Form">
            <i class="bi bi-plus-circle me-1"></i>Nowa płatność online
        </button>

        <div class="collapse" id="p24Form">
            <div class="card card-body mt-2" style="background:rgba(255,255,255,.04)">
                <form method="post" action="<?= url('portal/payment/initiate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_type" value="fee">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Opis płatności</label>
                        <input type="text" class="form-control form-control-sm" name="description"
                               value="Składka członkowska <?= date('Y') ?>"
                               placeholder="Np. Składka roczna <?= date('Y') ?>"
                               required maxlength="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kwota (PLN)</label>
                        <div class="input-group input-group-sm" style="max-width:200px">
                            <input type="number" class="form-control" name="amount"
                                   min="1" max="9999" step="0.01"
                                   placeholder="0,00" required>
                            <span class="input-group-text">zł</span>
                        </div>
                        <div class="form-text">Minimalna kwota: 1,00 PLN</div>
                    </div>

                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-lock me-1"></i>Przejdź do płatności P24
                    </button>
                    <p class="text-muted mt-2 mb-0" style="font-size:.72rem">
                        Po kliknięciu zostaniesz przekierowany na bezpieczną stronę Przelewy24.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="alert alert-secondary small">
    <i class="bi bi-info-circle"></i>
    W razie pytań dotyczących opłat prosimy o kontakt z biurem klubu.
</div>
