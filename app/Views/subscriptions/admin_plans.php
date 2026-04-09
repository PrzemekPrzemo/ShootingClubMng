<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/subscriptions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-sliders"></i> Plany subskrypcji — ceny i limity</h2>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= url('admin/subscriptions/plans') ?>">
            <?= csrf_field() ?>
            <div class="table-responsive">
            <table class="table table-bordered align-middle" id="plansTable">
                <thead class="table-dark">
                    <tr>
                        <th>Klucz</th>
                        <th>Nazwa</th>
                        <th>Cena mies. (PLN)</th>
                        <th>Cena roczna (PLN)</th>
                        <th>Max zawodników</th>
                        <th>Opis</th>
                        <th>Aktywny</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($plans as $key => $plan): ?>
                <tr>
                    <td>
                        <input type="hidden" name="key[]" value="<?= e($key) ?>">
                        <code><?= e($key) ?></code>
                    </td>
                    <td>
                        <input type="text" name="label[]" class="form-control form-control-sm"
                               value="<?= e($plan['label']) ?>" required>
                    </td>
                    <td>
                        <input type="number" name="price_pln[]" class="form-control form-control-sm"
                               value="<?= e($plan['price_pln']) ?>" min="0" step="0.01" style="width:100px">
                    </td>
                    <td>
                        <input type="number" name="price_annual[]" class="form-control form-control-sm"
                               value="<?= e($plan['price_annual'] ?? 0) ?>" min="0" step="0.01" style="width:110px">
                    </td>
                    <td>
                        <input type="number" name="max_members[]" class="form-control form-control-sm"
                               value="<?= e($plan['max_members'] ?? '') ?>" min="1" placeholder="∞" style="width:90px">
                    </td>
                    <td>
                        <input type="text" name="description[]" class="form-control form-control-sm"
                               value="<?= e($plan['description'] ?? '') ?>" placeholder="Opis (opcjonalnie)">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" name="is_active[<?= e($key) ?>]" class="form-check-input"
                               <?= ($plan['is_active'] ?? true) ? 'checked' : '' ?>>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <div class="alert alert-info small mb-3">
                <i class="bi bi-info-circle"></i>
                Klucze planów (trial, basic, standard, premium) są stałe — zmiana nazwy lub ceny jest propagowana do nowych subskrypcji.
                Istniejące subskrypcje zachowują swój przypisany plan.
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2"></i> Zapisz plany
            </button>
            <a href="<?= url('admin/subscriptions') ?>" class="btn btn-outline-secondary ms-2">Anuluj</a>
        </form>
    </div>
</div>
