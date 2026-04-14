<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url($mode === 'edit' ? 'startlist/' . $generator['id'] : 'startlist') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('startlist/create') : url('startlist/' . $generator['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Nazwa generatora <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= e($generator['name'] ?? '') ?>" required
                       placeholder="np. Zawody Okręgowe 2026 — lista startowa">
            </div>

            <div class="mb-3">
                <label class="form-label">Powiąż z zawodami <span class="text-muted small">(opcjonalnie)</span></label>
                <select name="competition_id" class="form-select">
                    <option value="">— brak —</option>
                    <?php foreach ($competitions as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= ($generator['competition_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?> (<?= e($c['competition_date']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Powiązanie jest opcjonalne — generator działa samodzielnie.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data startu <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?= e($generator['start_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Godzina rozpoczęcia</label>
                    <input type="time" name="start_time" class="form-control"
                           value="<?= e(substr($generator['start_time'] ?? '09:00', 0, 5)) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Przerwa między zmianami <span class="text-muted small">(minuty)</span>
                </label>
                <input type="number" name="break_minutes" class="form-control"
                       min="0" max="120" value="<?= (int)($generator['break_minutes'] ?? 10) ?>">
                <div class="form-text">Globalny czas na sprawy techniczne między każdą zmianą.</div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? '<i class="bi bi-arrow-right-circle"></i> Utwórz i przejdź do dyscyplin' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url($mode === 'edit' ? 'startlist/' . $generator['id'] : 'startlist') ?>"
                   class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
