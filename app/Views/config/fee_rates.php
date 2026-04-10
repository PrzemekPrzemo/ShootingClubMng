<?php
$navItems = [
    'config'                    => ['bi-sliders',      'Ustawienia'],
    'config/categories'         => ['bi-tags',         'Kategorie wiekowe'],
    'config/disciplines'        => ['bi-bullseye',     'Dyscypliny'],
    'config/member-classes'     => ['bi-award',        'Klasy zawodników'],
    'config/medical-exam-types' => ['bi-heart-pulse',  'Typy badań'],
    'config/license-types'      => ['bi-card-checklist','Typy licencji'],
    'config/fee-rates'          => ['bi-cash-coin',    'Cennik składek'],
    'config/fee-config'         => ['bi-calculator',   'Kalkulator składek'],
    'config/users'              => ['bi-people',       'Użytkownicy'],
];
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-gear"></i> Konfiguracja systemu</h2>
</div>

<div class="row g-2 mb-4">
    <?php foreach ($navItems as $path => [$icon, $label]): ?>
    <div class="col-auto">
        <a href="<?= url($path) ?>"
           class="btn btn-outline-secondary btn-sm <?= str_ends_with($currentPath, ltrim($path, '/')) ? 'active' : '' ?>">
            <i class="bi <?= $icon ?>"></i> <?= $label ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-calculator fs-4 flex-shrink-0"></i>
    <div>
        <strong>Szukasz kalkulatora składek per zawodnik?</strong><br>
        Ta strona to <em>cennik</em> — statyczna matryca kwot używana jako podpowiedź przy rejestracji wpłaty.<br>
        Konfiguracja stawki maksymalnej, wczesnej wpłaty i zniżek za klasę / osiągnięcia znajduje się w
        <a href="<?= url('config/fee-config') ?>" class="alert-link fw-bold">Kalkulatorze składek <i class="bi bi-arrow-right"></i></a>
    </div>
</div>

<div class="row g-4">

    <!-- ═══ Lewa kolumna: lista typów składek + formularz ═══════════ -->
    <div class="col-xl-4">

        <!-- Formularz dodaj/edytuj typ -->
        <div class="card mb-3">
            <div class="card-header">
                <strong><?= $editItem ? 'Edytuj typ składki' : 'Dodaj typ składki' ?></strong>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/fee-rates/type') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>"
                               placeholder="np. Składka roczna">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kategoria</label>
                        <select name="category" class="form-select form-select-sm">
                            <?php foreach ($categories as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($editItem['category'] ?? 'inne') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Opis / notatka</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"><?= e($editItem['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kwota domyślna (PLN)</label>
                        <input type="number" name="amount" class="form-control form-control-sm"
                               step="0.01" min="0"
                               value="<?= number_format((float)($editItem['amount'] ?? 0), 2, '.', '') ?>">
                        <div class="form-text">Używana gdy nie ma stawki per rok/klasa.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kolejność</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" min="0"
                               value="<?= e($editItem['sort_order'] ?? 0) ?>">
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" name="is_per_class" class="form-check-input" id="perClass" value="1"
                               <?= ($editItem['is_per_class'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="perClass">
                            Kwota zależy od klasy zawodnika
                        </label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1"
                               <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">Aktywny</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <?= $editItem ? 'Zapisz zmiany' : 'Dodaj typ składki' ?>
                        </button>
                        <?php if ($editItem): ?>
                        <a href="<?= url('config/fee-rates') ?>" class="btn btn-outline-secondary btn-sm">Anuluj</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista typów składek -->
        <div class="card">
            <div class="card-header"><strong>Typy składek</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-dark">
                        <tr><th>Nazwa</th><th>Kat.</th><th>Domyślna</th><th class="text-center">Kl.</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $catIcons = ['skladka'=>'💳','pzss'=>'🏛️','pomzss'=>'⚓','inne'=>'📄'];
                    foreach ($paymentTypes as $pt):
                        // Defaults for rows loaded before migration_v4
                        $pt['category']     ??= 'inne';
                        $pt['is_per_class'] ??= 0;
                        $pt['sort_order']   ??= 0;
                        $pt['description']  ??= null;
                    ?>
                        <tr class="<?= $pt['is_active'] ? '' : 'table-secondary text-muted' ?>">
                            <td>
                                <span title="<?= e($pt['description'] ?? '') ?>"><?= e($pt['name']) ?></span>
                            </td>
                            <td class="small text-muted text-center" title="<?= $categories[$pt['category']] ?? '' ?>">
                                <?= $catIcons[$pt['category']] ?? '📄' ?>
                            </td>
                            <td class="small"><?= format_money((float)$pt['amount']) ?></td>
                            <td class="text-center">
                                <?php if ($pt['is_per_class']): ?>
                                <span class="badge bg-info text-dark" title="Stawka per klasa">K</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <a href="<?= url('config/fee-rates?edit=' . $pt['id']) ?>"
                                   class="btn btn-xs btn-outline-secondary py-0 px-1"><i class="bi bi-pencil"></i></a>
                                <form method="post"
                                      action="<?= url('config/fee-rates/type/' . $pt['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć/dezaktywować ten typ składki?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $pt['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-0 px-1">
                                        <i class="bi bi-<?= $pt['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paymentTypes)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Brak typów składek.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ═══ Prawa kolumna: matryca stawek ═══════════════════════════ -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3">
                <strong><i class="bi bi-table"></i> Stawki składek</strong>

                <!-- Wybór roku -->
                <form method="get" class="d-flex align-items-center gap-2 ms-auto">
                    <label class="mb-0 small">Rok:</label>
                    <select name="year" class="form-select form-select-sm" style="width:auto"
                            onchange="this.form.submit()">
                        <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <div class="card-body p-0">
                <form method="post" action="<?= url('config/fee-rates/save') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="year" value="<?= $year ?>">

                    <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="rateTable">
                        <thead class="table-dark">
                            <tr>
                                <th class="sticky-col">Typ składki</th>
                                <th class="text-center text-nowrap">
                                    Domyślna
                                    <div class="small fw-normal text-muted" style="font-size:8pt">wszyscy bez klasy</div>
                                </th>
                                <?php foreach ($memberClasses as $mc): ?>
                                <th class="text-center text-nowrap <?= $mc['is_active'] ? '' : 'text-muted' ?>">
                                    <?= e($mc['name']) ?>
                                    <div class="small fw-normal" style="font-size:8pt">[<?= e($mc['short_code']) ?>]</div>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $catLabels = [
                            'skladka' => ['Składki członkowskie', 'table-primary'],
                            'pzss'    => ['Opłaty PZSS', 'table-warning'],
                            'pomzss'  => ['Opłaty PomZSS', 'table-info'],
                            'inne'    => ['Inne', 'table-secondary'],
                        ];
                        $lastCat = null;

                        foreach ($paymentTypes as $pt):
                            if (!$pt['is_active']) continue;
                            // Defaults for columns added in migration_v4
                            $pt['category']     ??= 'inne';
                            $pt['is_per_class'] ??= 0;
                            $pt['description']  ??= null;

                            // Category separator row
                            if ($pt['category'] !== $lastCat):
                                $lastCat = $pt['category'];
                                [$catLabel, $catClass] = $catLabels[$pt['category']] ?? ['Inne', 'table-secondary'];
                        ?>
                        <tr class="<?= $catClass ?>">
                            <td colspan="<?= 2 + count($memberClasses) ?>" class="small fw-bold py-1">
                                <?= $catIcons[$pt['category']] ?? '' ?> <?= $catLabel ?>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <td class="sticky-col">
                                <div class="fw-semibold"><?= e($pt['name']) ?></div>
                                <?php if ($pt['description']): ?>
                                <div class="small text-muted"><?= e($pt['description']) ?></div>
                                <?php endif; ?>
                                <?php if ($pt['is_per_class']): ?>
                                <span class="badge bg-info text-dark" style="font-size:7pt">per klasa</span>
                                <?php endif; ?>
                            </td>

                            <?php
                            // Determine default rate: from fee_rates[typeId][0] or payment_types.amount
                            $defaultRate = $rateMatrix[$pt['id']][0] ?? (float)$pt['amount'];
                            ?>

                            <!-- Stawka domyślna (class_key=0) -->
                            <td class="p-1">
                                <div class="input-group input-group-sm">
                                    <input type="number"
                                           name="rates[<?= $pt['id'] ?>][0]"
                                           class="form-control form-control-sm text-end rate-input"
                                           step="0.01" min="0"
                                           value="<?= number_format($defaultRate, 2, '.', '') ?>"
                                           data-type-id="<?= $pt['id'] ?>">
                                    <span class="input-group-text" style="font-size:8pt">PLN</span>
                                </div>
                            </td>

                            <!-- Stawka per klasa -->
                            <?php foreach ($memberClasses as $mc): ?>
                            <td class="p-1 <?= $mc['is_active'] ? '' : 'table-secondary' ?>">
                                <?php
                                // If type is not per_class and no specific override, inherit from default
                                $classRate = $rateMatrix[$pt['id']][$mc['id']] ?? null;
                                $isInherited = $classRate === null;
                                $displayRate = $classRate ?? $defaultRate;
                                ?>
                                <div class="input-group input-group-sm">
                                    <input type="number"
                                           name="rates[<?= $pt['id'] ?>][<?= $mc['id'] ?>]"
                                           class="form-control form-control-sm text-end rate-input <?= $isInherited && !$pt['is_per_class'] ? 'text-muted' : '' ?>"
                                           step="0.01" min="0"
                                           value="<?= number_format($displayRate, 2, '.', '') ?>"
                                           data-type-id="<?= $pt['id'] ?>"
                                           data-inherited="<?= $isInherited && !$pt['is_per_class'] ? '1' : '0' ?>"
                                           <?= (!$pt['is_per_class'] && $isInherited) ? 'data-auto="1"' : '' ?>
                                           title="<?= $isInherited ? 'Stawka dziedziczona z domyślnej — wpisz inną wartość aby nadpisać' : 'Stawka specyficzna dla klasy' ?>">
                                    <span class="input-group-text" style="font-size:8pt">PLN</span>
                                </div>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>

                    <div class="p-3 d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-check-lg"></i> Zapisz stawki <?= $year ?>
                        </button>
                        <div class="small text-muted">
                            <span class="badge bg-info text-dark me-1">K</span> = kwota zależy od klasy
                            &nbsp;|&nbsp;
                            Pola wyszarzone = dziedziczone z stawki domyślnej
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle"></i>
            <strong>Jak to działa:</strong>
            Stawki z tej tabeli są używane jako <em>podpowiedź kwoty</em> przy rejestracji wpłaty —
            system automatycznie proponuje odpowiednią kwotę w zależności od klasy zawodnika.
            Stawka szukana jest w kolejności: <strong>klasa zawodnika → domyślna → kwota z definicji typu</strong>.
        </div>
    </div>
</div>

<style>
.sticky-col {
    min-width: 180px;
    max-width: 220px;
}
#rateTable input[data-inherited="1"] {
    background-color: #f8f9fa;
    color: #6c757d;
}
#rateTable input[data-inherited="1"]:focus {
    background-color: #fff;
    color: #000;
}
</style>

<script>
// When default rate changes, update inherited fields in the same row
document.querySelectorAll('.rate-input').forEach(input => {
    if (!input.name.includes('][0]')) return; // only default inputs

    input.addEventListener('input', function () {
        const typeId  = this.dataset.typeId;
        const newVal  = this.value;
        document.querySelectorAll(`input[data-auto="1"][data-type-id="${typeId}"]`).forEach(el => {
            el.value = newVal;
        });
    });
});

// When user edits an inherited field, mark it as overridden
document.querySelectorAll('input[data-auto="1"]').forEach(input => {
    input.addEventListener('focus', function() {
        this.removeAttribute('data-auto');
        this.dataset.inherited = '0';
        this.classList.remove('text-muted');
    });
});
</script>
