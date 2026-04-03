<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('competitions/create') : url('competitions/' . $competition['id'] . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nazwa zawodów <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= e($competition['name'] ?? '') ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Dyscyplina</label>
                    <select name="discipline_id" class="form-select">
                        <option value="">— wielodyscyplinarne —</option>
                        <?php foreach ($disciplines as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($competition['discipline_id'] ?? '') == $d['id'] ? 'selected':'' ?>>
                                <?= e($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data zawodów <span class="text-danger">*</span></label>
                    <input type="date" name="competition_date" class="form-control"
                           value="<?= e($competition['competition_date'] ?? '') ?>" required>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Miejsce</label>
                    <input type="text" name="location" class="form-control"
                           value="<?= e($competition['location'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Maks. zgłoszeń</label>
                    <input type="number" name="max_entries" class="form-control" min="1"
                           value="<?= e($competition['max_entries'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['planowane','otwarte','zamkniete','zakonczone'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($competition['status'] ?? 'planowane') === $s ? 'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Opis</label>
                <textarea name="description" class="form-control" rows="3"><?= e($competition['description'] ?? '') ?></textarea>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Utwórz zawody' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('competitions') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
