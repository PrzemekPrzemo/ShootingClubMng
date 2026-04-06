<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . (int)$member['id'] . '/weapons') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create'
            ? url('members/' . (int)$member['id'] . '/weapons')
            : url('members/' . (int)$member['id'] . '/weapons/' . (int)$weapon['id']) ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Nazwa / model <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= e($weapon['name'] ?? '') ?>" required
                       placeholder="np. Glock 17, CZ 75, Remington 700">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Typ broni</label>
                    <select name="type" class="form-select">
                        <?php foreach ($types as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($weapon['type'] ?? 'inne') === $val ? 'selected' : '' ?>>
                            <?= e($lbl) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kaliber</label>
                    <input type="text" name="caliber" class="form-control"
                           value="<?= e($weapon['caliber'] ?? '') ?>"
                           placeholder="np. 9mm, .22 LR, 7.62×39">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Numer seryjny</label>
                    <input type="text" name="serial_number" class="form-control"
                           value="<?= e($weapon['serial_number'] ?? '') ?>"
                           placeholder="Nr seryjny z tabliczki znamionowej">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Producent</label>
                    <input type="text" name="manufacturer" class="form-control"
                           value="<?= e($weapon['manufacturer'] ?? '') ?>"
                           placeholder="np. Glock, CZ, Heckler & Koch">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Numer pozwolenia na tę broń
                    <span class="text-muted small">(opcjonalny — jeśli inny niż główny)</span>
                </label>
                <input type="text" name="permit_number" class="form-control"
                       value="<?= e($weapon['permit_number'] ?? '') ?>"
                       placeholder="Nr decyzji / pozwolenia">
            </div>

            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Dodatkowe informacje…"><?= e($weapon['notes'] ?? '') ?></textarea>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                           value="1" <?= ($weapon['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Broń aktywna (w posiadaniu)</label>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check-lg"></i>
                    <?= $mode === 'create' ? 'Dodaj broń' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('members/' . (int)$member['id'] . '/weapons') ?>"
                   class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>

<?php if ($mode === 'edit'): ?>
<div class="mt-3">
    <form method="post"
          action="<?= url('members/' . (int)$member['id'] . '/weapons/' . (int)$weapon['id'] . '/delete') ?>"
          onsubmit="return confirm('Usunąć tę broń z rejestru?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-trash"></i> Usuń broń z rejestru
        </button>
    </form>
</div>
<?php endif; ?>

</div>
</div>
