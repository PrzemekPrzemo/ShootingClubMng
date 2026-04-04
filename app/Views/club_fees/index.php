<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-bank"></i> Opłaty PZSS/PomZSS — <?= $year ?></h2>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <!-- Wybór roku -->
        <form method="get" class="d-flex gap-2">
            <select name="year_redirect" class="form-select form-select-sm" style="width:auto"
                    onchange="window.location='<?= url('club-fees/') ?>'+this.value">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
                <?php if (!in_array($year, $years)): ?>
                <option value="<?= $year ?>" selected><?= $year ?></option>
                <?php endif; ?>
            </select>
        </form>
    </div>
</div>

<!-- Karta kalkulatora -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-danger"><?= format_money($totalDue) ?></div>
                <div class="text-muted small">Łączne zobowiązania <?= $year ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-success"><?= format_money($totalPaid) ?></div>
                <div class="text-muted small">Łącznie zapłacono</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php $remaining = $totalDue - $totalPaid; ?>
                <div class="fs-4 fw-bold <?= $remaining > 0 ? 'text-warning' : 'text-success' ?>">
                    <?= format_money(max(0, $remaining)) ?>
                </div>
                <div class="text-muted small">Pozostało do zapłaty</div>
            </div>
        </div>
    </div>
</div>

<!-- Przycisk oblicz -->
<div class="mb-3">
    <form method="post" action="<?= url('club-fees/calculate') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="year" value="<?= $year ?>">
        <button type="submit" class="btn btn-outline-primary btn-sm"
                onclick="return confirm('Przeliczyć zobowiązania na rok <?= $year ?> na podstawie aktualnej liczby zawodników i sędziów?')">
            <i class="bi bi-calculator"></i> Oblicz/aktualizuj zobowiązania
        </button>
    </form>
</div>

<!-- Tabela opłat -->
<div class="card">
    <div class="card-header"><strong>Zobowiązania na rok <?= $year ?></strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Pozycja</th>
                    <th class="text-end">Kwota należna</th>
                    <th>Termin</th>
                    <th class="text-center">Status</th>
                    <th>Zapłacono</th>
                    <th>Nr przelewu</th>
                    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $feeTypes = ['licencja_pzss','czlonek_pzss','czlonek_pomzss','licencje_zawodnicze','licencje_sedziowskie'];
            foreach ($feeTypes as $ft):
                $fee = $fees[$ft] ?? null;
                $calcItem = $calc[$ft] ?? null;
                if (!$fee && !$calcItem) continue;
            ?>
            <tr>
                <td>
                    <?= e($feeLabels[$ft] ?? $ft) ?>
                    <?php if ($ft === 'licencje_zawodnicze' && $calcItem): ?>
                        <small class="text-muted">(<?= $calcItem['count'] ?> zawodników × 25 PLN)</small>
                    <?php elseif ($ft === 'licencje_sedziowskie' && $calcItem): ?>
                        <small class="text-muted">(<?= $calcItem['count'] ?> sędziów × 50 PLN)</small>
                    <?php endif; ?>
                </td>
                <td class="text-end fw-bold">
                    <?= $fee ? format_money((float)$fee['amount_due']) : ($calcItem ? format_money($calcItem['amount_due']) : '—') ?>
                </td>
                <td class="small">
                    <?php if ($fee): ?>
                        <?= format_date($fee['due_date']) ?>
                        <?php
                        $dueDays = (int)ceil((strtotime($fee['due_date']) - time()) / 86400);
                        if (!$fee['paid_date'] && $dueDays < 0): ?>
                            <span class="badge bg-danger">Przeterminowane</span>
                        <?php elseif (!$fee['paid_date'] && $dueDays <= 30): ?>
                            <span class="badge bg-warning text-dark">za <?= $dueDays ?> dni</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $calcItem ? format_date($calcItem['due_date']) : '—' ?>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($fee): ?>
                        <?php if ($fee['paid_date']): ?>
                            <span class="badge bg-success"><i class="bi bi-check"></i> Zapłacone</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Oczekuje</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-secondary">Brak danych</span>
                    <?php endif; ?>
                </td>
                <td class="small">
                    <?php if ($fee && $fee['paid_date']): ?>
                        <?= format_date($fee['paid_date']) ?>
                        (<?= format_money((float)$fee['paid_amount']) ?>)
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="small text-muted">
                    <?= $fee ? e($fee['reference'] ?? '—') : '—' ?>
                </td>
                <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                <td>
                    <?php if ($fee && !$fee['paid_date']): ?>
                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-2"
                            data-bs-toggle="modal"
                            data-bs-target="#payModal"
                            data-fee-id="<?= $fee['id'] ?>"
                            data-fee-amount="<?= $fee['amount_due'] ?>"
                            data-fee-year="<?= $year ?>">
                        <i class="bi bi-check-circle"></i> Zapłacono
                    </button>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Info -->
<div class="mt-3 alert alert-info alert-sm small">
    <i class="bi bi-info-circle"></i>
    <strong>Terminy PZSS/PomZSS:</strong>
    Licencja klubowa (1 000 PLN), składka PZSS (500 PLN), składka PomZSS (350 PLN) — do <strong>31 marca</strong>.
    Licencje zawodnicze 25 PLN/os. — od listopada.
    Licencje sędziowskie 50 PLN/os. — do <strong>30 czerwca</strong>.
</div>

<?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
<!-- Modal: Oznacz jako zapłacone -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Oznacz jako zapłacone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= url('club-fees/paid') ?>" id="payForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="payFeeId">
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Data zapłaty</label>
                        <input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kwota zapłacona (PLN)</label>
                        <input type="number" name="paid_amount" id="payAmount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Numer przelewu / referencja</label>
                        <input type="text" name="reference" class="form-control" placeholder="np. 2026/03/001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('payModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('payFeeId').value   = btn.dataset.feeId;
    document.getElementById('payAmount').value  = btn.dataset.feeAmount;
    document.getElementById('payForm').action   = '<?= url('club-fees/') ?>' + btn.dataset.feeId + '/paid';
});
</script>
<?php endif; ?>
