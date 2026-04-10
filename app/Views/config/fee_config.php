<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-calculator text-success"></i> Kalkulator składek</h2>

    <!-- Rok -->
    <form method="get" class="d-flex align-items-center gap-2 ms-auto">
        <label class="mb-0 small fw-semibold">Rok:</label>
        <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 3; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<?php if ($clubId === null): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Kalkulator składek dostępny tylko w kontekście konkretnego klubu. Przełącz się do wybranego klubu.
</div>
<?php else: ?>

<div class="alert alert-info py-2 small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Formuła:</strong> składka roczna = stawka bazowa (typ) − zniżka za klasę − zniżki za osiągnięcia.
    Miesięczna = roczna ÷ 12. Zniżki się <strong>kumulują</strong>; każdy unikalny typ osiągnięcia liczy się raz.
    Wyniki przeliczenia zapisywane są w profilach zawodników.
    <a href="<?= url('config/member-types') ?>" class="ms-2">Zarządzaj typami członkostwa →</a>
</div>

<form method="post" action="<?= url('config/fee-config/save') ?>">
<?= csrf_field() ?>
<input type="hidden" name="year" value="<?= $year ?>">

<!-- ═══ Karta 1: Stawki per typ członkostwa ═══════════════════════════════ -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <strong><i class="bi bi-person-badge me-1"></i> Stawki per typ członkostwa — <?= $year ?></strong>
        <span class="ms-2 text-muted small">Kolumna "Miesięczna" wyliczana automatycznie</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Typ członkostwa</th>
                    <th class="text-center" style="min-width:150px">Stawka maks. roczna (PLN)</th>
                    <th class="text-center" style="min-width:150px">Wczesna wpłata do końca lutego (PLN)</th>
                    <th class="text-center" style="min-width:110px">Mies. (auto)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($memberTypes)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Brak zdefiniowanych typów członkostwa.</td></tr>
            <?php endif; ?>
            <?php foreach ($memberTypes as $mt):
                $typeName    = $mt['name'];
                $cfgRow      = $feeConfig[$typeName] ?? [];
                $maxAnnual   = $cfgRow['max_annual_fee']    ?? 0;
                $earlyPayment= $cfgRow['early_payment_fee'] ?? 0;
            ?>
            <tr>
                <td>
                    <?= e($typeName) ?>
                    <?php if (empty($mt['club_id'])): ?>
                        <span class="badge bg-secondary ms-1" title="Typ globalny">Globalny</span>
                    <?php endif; ?>
                </td>
                <td class="p-1">
                    <div class="input-group input-group-sm">
                        <input type="number"
                               name="fee_config[<?= e($typeName) ?>][max_annual]"
                               class="form-control text-end fee-max-annual"
                               data-type="<?= e($typeName) ?>"
                               step="0.01" min="0"
                               value="<?= number_format($maxAnnual, 2, '.', '') ?>">
                        <span class="input-group-text">PLN</span>
                    </div>
                </td>
                <td class="p-1">
                    <div class="input-group input-group-sm">
                        <input type="number"
                               name="fee_config[<?= e($typeName) ?>][early_payment]"
                               class="form-control text-end"
                               step="0.01" min="0"
                               value="<?= number_format($earlyPayment, 2, '.', '') ?>">
                        <span class="input-group-text">PLN</span>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <span class="monthly-display text-muted small" data-type="<?= e($typeName) ?>">
                        <?= $maxAnnual > 0 ? number_format($maxAnnual / 12, 2, ',', ' ') . ' PLN' : '—' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ Karta 2: Zniżki za klasę sportową ════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-header">
        <strong><i class="bi bi-award me-1"></i> Zniżki za klasę sportową — <?= $year ?></strong>
        <span class="ms-2 text-muted small">0 PLN = brak zniżki dla tej klasy</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Klasa sportowa</th>
                    <th class="text-center" style="min-width:180px">Zniżka roczna (PLN)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($memberClasses)): ?>
                <tr><td colspan="2" class="text-center text-muted py-3">Brak zdefiniowanych klas sportowych.</td></tr>
            <?php endif; ?>
            <?php foreach ($memberClasses as $mc):
                $discount = $classDiscounts[$mc['id']] ?? 0;
            ?>
            <tr class="<?= $mc['is_active'] ? '' : 'text-muted table-secondary' ?>">
                <td>
                    <?= e($mc['name']) ?>
                    <code class="small ms-1">[<?= e($mc['short_code']) ?>]</code>
                    <?php if (!$mc['is_active']): ?><span class="badge bg-secondary ms-1">nieaktywna</span><?php endif; ?>
                    <?php if (empty($mc['club_id'])): ?><span class="badge bg-secondary ms-1">Globalna</span><?php endif; ?>
                </td>
                <td class="p-1">
                    <div class="input-group input-group-sm" style="max-width:200px;margin:0 auto">
                        <input type="number"
                               name="discount_class[<?= (int)$mc['id'] ?>]"
                               class="form-control text-end"
                               step="0.01" min="0"
                               value="<?= number_format($discount, 2, '.', '') ?>">
                        <span class="input-group-text">PLN</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ Karta 3: Zniżki za osiągnięcia ══════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-header">
        <strong><i class="bi bi-trophy me-1 text-warning"></i> Zniżki za osiągnięcia sportowe — <?= $year ?></strong>
        <span class="ms-2 text-muted small">Każdy unikalny typ osiągnięcia liczy się raz; zniżki kumulują się</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Rodzaj osiągnięcia</th>
                    <th class="text-center" style="min-width:180px">Zniżka roczna (PLN)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($achievementTypes as $achKey => $achLabel):
                $discount = $achDiscounts[$achKey] ?? 0;
            ?>
            <tr>
                <td class="small"><?= e($achLabel) ?></td>
                <td class="p-1">
                    <div class="input-group input-group-sm" style="max-width:200px;margin:0 auto">
                        <input type="number"
                               name="discount_achieve[<?= e($achKey) ?>]"
                               class="form-control text-end"
                               step="0.01" min="0"
                               value="<?= number_format($discount, 2, '.', '') ?>">
                        <span class="input-group-text">PLN</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Zapisz -->
<div class="mb-4">
    <button type="submit" class="btn btn-success btn-lg">
        <i class="bi bi-check-lg"></i> Zapisz konfigurację <?= $year ?>
    </button>
</div>

</form>

<!-- ═══ Karta 4: Przelicz składki ════════════════════════════════════════ -->
<div class="card border-warning mb-4">
    <div class="card-header bg-warning bg-opacity-10">
        <strong><i class="bi bi-arrow-repeat me-1"></i> Przelicz składki zawodników</strong>
    </div>
    <div class="card-body">
        <?php if (!empty($recalcStats['cnt']) && $recalcStats['cnt'] > 0): ?>
        <div class="alert alert-info py-2 small mb-3">
            <i class="bi bi-clock-history me-1"></i>
            Ostatnie przeliczenie: <strong><?= (int)$recalcStats['cnt'] ?></strong> zawodników,
            łączna roczna: <strong><?= number_format((float)$recalcStats['total_annual'], 2, ',', ' ') ?> PLN</strong>.
            <?php if (!empty($recalcStats['last_at'])): ?>
            <span class="text-muted">(<?= date('d.m.Y H:i', strtotime($recalcStats['last_at'])) ?>)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <p class="small text-muted mb-3">
            Przeliczenie nadpisze zapisane składki dla wszystkich <strong>aktywnych</strong> zawodników tego klubu
            na rok <strong><?= $year ?></strong>, na podstawie aktualnej konfiguracji powyżej.
        </p>
        <form method="post" action="<?= url('config/fee-config/recalculate') ?>"
              onsubmit="return confirm('Przeliczyć składki dla wszystkich aktywnych zawodników za rok <?= $year ?>?')">
            <?= csrf_field() ?>
            <input type="hidden" name="year" value="<?= $year ?>">
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-arrow-repeat"></i> Przelicz teraz (<?= $year ?>)
            </button>
        </form>
    </div>
</div>

<?php endif; // $clubId !== null ?>

<script>
// Live update of monthly column when max_annual changes
document.querySelectorAll('.fee-max-annual').forEach(function(input) {
    function updateMonthly() {
        var val     = parseFloat(input.value) || 0;
        var monthly = val > 0 ? (val / 12).toFixed(2).replace('.', ',') + ' PLN' : '—';
        var type    = input.dataset.type;
        var display = document.querySelector('.monthly-display[data-type="' + CSS.escape(type) + '"]');
        if (display) display.textContent = monthly;
    }
    input.addEventListener('input', updateMonthly);
});
</script>
