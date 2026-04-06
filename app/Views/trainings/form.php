<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('trainings') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('trainings/create') : url('trainings/' . $training['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Tytuł treningu <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($training['title'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data <span class="text-danger">*</span></label>
                    <input type="date" name="training_date" class="form-control"
                           value="<?= e($training['training_date'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Godz. od</label>
                    <input type="time" name="time_start" class="form-control"
                           value="<?= e(substr($training['time_start'] ?? '', 0, 5)) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Godz. do</label>
                    <input type="time" name="time_end" class="form-control"
                           value="<?= e(substr($training['time_end'] ?? '', 0, 5)) ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Stanowisko / Tor</label>
                    <input type="text" name="lane" class="form-control"
                           value="<?= e($training['lane'] ?? '') ?>"
                           placeholder="np. Tarcza A, Tor 1–3">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Maks. uczestników</label>
                    <input type="number" name="max_participants" class="form-control" min="1"
                           value="<?= e($training['max_participants'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Instruktor</label>
                <select name="instructor_id" class="form-select">
                    <option value="">— brak —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= ($training['instructor_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['full_name'] ?: $u['username']) ?>
                            <<?= e($u['role']) ?>>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="planowany"  <?= ($training['status'] ?? 'planowany') === 'planowany'  ? 'selected' : '' ?>>Planowany</option>
                    <option value="odbyl_sie"  <?= ($training['status'] ?? '') === 'odbyl_sie'  ? 'selected' : '' ?>>Odbył się</option>
                    <option value="odwolany"   <?= ($training['status'] ?? '') === 'odwolany'   ? 'selected' : '' ?>>Odwołany</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($training['notes'] ?? '') ?></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Utwórz trening' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('trainings') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
