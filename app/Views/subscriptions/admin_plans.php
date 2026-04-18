<?php
// Group plans by category for display (base, scaled, flat)
$grouped = ['base' => [], 'scaled' => [], 'flat' => []];
foreach ($plans as $key => $plan) {
    $cat = $plan['category'] ?? 'base';
    if (!isset($grouped[$cat])) $grouped[$cat] = [];
    $grouped[$cat][$key] = $plan;
}

$sections = [
    'base' => [
        'title' => 'Opłaty bazowe',
        'icon'  => 'bi-house-gear',
        'desc'  => 'Opłata wdrożeniowa (jednorazowo, pierwszy rok), subskrypcja bazowa (rocznie, od 2. roku), dodatkowe paczki po 200 członków (naliczane miesięcznie × 12).',
        'color' => 'primary',
    ],
    'scaled' => [
        'title' => 'Moduły skalowane (per 200 członków / rok)',
        'icon'  => 'bi-puzzle',
        'desc'  => 'Cena roczna naliczana za każde rozpoczęte 200 członków. W cenie bazowej (gratis): zarządzanie członkami, licencje PZSS, badania, konfiguracja, kalendarz, ogłoszenia, dashboard bezpieczeństwa.',
        'color' => 'warning',
    ],
    'flat' => [
        'title' => 'Moduły jednorazowe / ryczałtowe',
        'icon'  => 'bi-box-seam',
        'desc'  => 'Nie skalują się z liczbą członków — mają stałą opłatę wdrożeniową (jednorazowo) i opcjonalną opłatę roczną (od 1. roku).',
        'color' => 'info',
    ],
];
?>

<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
    <a href="<?= url('admin/subscriptions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0 flex-grow-1"><i class="bi bi-sliders"></i> Plany subskrypcji — ceny i moduły</h2>
    <a href="<?= url('admin/pricing-calculator') ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-calculator"></i> Przejdź do kalkulatora
    </a>
</div>

<div class="alert alert-info d-flex gap-2 align-items-start">
    <i class="bi bi-info-circle fs-5"></i>
    <div class="small">
        Struktura cennika zgodna z <a href="https://shootero.pl/cennik/" target="_blank" rel="noopener">shootero.pl/cennik/</a>.
        Edytuj ceny i włącz/wyłącz moduły. Zmiany zapisywane są w tabeli <code>subscription_plans</code> i wykorzystywane przez kalkulator oraz proces wystawiania faktur.
    </div>
</div>

<form method="post" action="<?= url('admin/subscriptions/plans') ?>">
    <?= csrf_field() ?>

    <?php foreach ($sections as $catKey => $sec): if (empty($grouped[$catKey])) continue; ?>
    <div class="card mb-4">
        <div class="card-header bg-<?= e($sec['color']) ?> bg-opacity-10 d-flex align-items-center gap-2">
            <i class="bi <?= e($sec['icon']) ?> text-<?= e($sec['color']) ?> fs-5"></i>
            <h5 class="mb-0"><?= e($sec['title']) ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3"><?= e($sec['desc']) ?></p>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 32px;"></th>
                            <th>Klucz</th>
                            <th>Nazwa</th>
                            <th>Wdrożenie<br><small class="text-muted">(1-razowo)</small></th>
                            <th>Rocznie<br><small class="text-muted">(od 1. lub 2. roku)</small></th>
                            <?php if ($catKey === 'base' || $catKey === 'scaled'): ?>
                            <th class="text-center" style="width: 80px;">× 200 czł.</th>
                            <?php endif; ?>
                            <th>Opis / notka</th>
                            <th class="text-center" style="width: 60px;">Aktywny</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($grouped[$catKey] as $key => $plan):
                        $icon = !empty($plan['icon']) ? $plan['icon'] : 'bi-circle';
                    ?>
                    <tr>
                        <td class="text-center">
                            <i class="bi <?= e($icon) ?> text-<?= e($sec['color']) ?>"></i>
                            <input type="hidden" name="icon[]" value="<?= e($icon) ?>">
                            <input type="hidden" name="category[]" value="<?= e($catKey) ?>">
                            <input type="hidden" name="sort_order[]" value="<?= (int)($plan['sort_order'] ?? 0) ?>">
                            <input type="hidden" name="block_size[]" value="<?= (int)($plan['block_size'] ?? 200) ?>">
                            <input type="hidden" name="max_members[]" value="<?= e($plan['max_members'] ?? '') ?>">
                            <input type="hidden" name="price_pln[]" value="<?= e($plan['price_pln'] ?? 0) ?>">
                        </td>
                        <td>
                            <input type="hidden" name="key[]" value="<?= e($key) ?>">
                            <code class="small"><?= e($key) ?></code>
                        </td>
                        <td>
                            <input type="text" name="label[]" class="form-control form-control-sm" value="<?= e($plan['label']) ?>" required>
                        </td>
                        <td>
                            <div class="input-group input-group-sm" style="min-width: 130px;">
                                <input type="number" name="price_setup[]" class="form-control form-control-sm text-end" value="<?= e($plan['price_setup'] ?? 0) ?>" min="0" step="1">
                                <span class="input-group-text small">PLN</span>
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm" style="min-width: 130px;">
                                <input type="number" name="price_yearly_recurring[]" class="form-control form-control-sm text-end" value="<?= e($plan['price_yearly_recurring'] ?? $plan['price_annual'] ?? 0) ?>" min="0" step="1">
                                <span class="input-group-text small">PLN</span>
                            </div>
                            <input type="hidden" name="price_annual[]" value="<?= e($plan['price_annual'] ?? 0) ?>">
                        </td>
                        <?php if ($catKey === 'base' || $catKey === 'scaled'): ?>
                        <td class="text-center">
                            <input type="checkbox" name="per_block[<?= e($key) ?>]" value="1" class="form-check-input" <?= !empty($plan['per_block']) ? 'checked' : '' ?>>
                        </td>
                        <?php else: ?>
                        <input type="hidden" name="per_block[<?= e($key) ?>]" value="0">
                        <?php endif; ?>
                        <td>
                            <input type="text" name="description[]" class="form-control form-control-sm mb-1" value="<?= e($plan['description'] ?? '') ?>" placeholder="Opis">
                            <input type="text" name="note[]" class="form-control form-control-sm" value="<?= e($plan['note'] ?? '') ?>" placeholder="Notka cenowa (np. '500 PLN/SMS')">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="is_active[<?= e($key) ?>]" class="form-check-input" <?= ($plan['is_active'] ?? true) ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="d-flex gap-2 sticky-bottom bg-body py-3 border-top">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2"></i> Zapisz plany i ceny
        </button>
        <a href="<?= url('admin/subscriptions') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <a href="<?= url('admin/pricing-calculator') ?>" class="btn btn-outline-info ms-auto">
            <i class="bi bi-calculator"></i> Kalkulator cen
        </a>
    </div>
</form>
