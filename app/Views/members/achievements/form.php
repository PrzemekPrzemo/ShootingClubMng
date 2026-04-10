<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . $member['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-trophy"></i> Dodaj osiągnięcie</h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">

<div class="card mb-3 border-0 bg-light">
    <div class="card-body py-2 small">
        <strong><?= e($member['last_name']) ?> <?= e($member['first_name']) ?></strong>
        <span class="text-muted ms-2"><?= e($member['member_number']) ?></span>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Nowe osiągnięcie</strong></div>
    <div class="card-body">
        <form method="post" action="<?= url('members/' . $member['id'] . '/achievements') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Rodzaj osiągnięcia <span class="text-danger">*</span></label>
                <select name="achievement_type" class="form-select" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Kategoria zawodów zgodna z klasyfikacją PZSS.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Miejsce</label>
                    <select name="place" class="form-select">
                        <option value="">— bez podium / inne —</option>
                        <?php foreach ($places as $num => $lbl): ?>
                        <option value="<?= $num ?>"><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Rok <span class="text-danger">*</span></label>
                    <input type="number" name="year" class="form-control" required
                           min="1900" max="<?= date('Y') ?>" value="<?= date('Y') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Nazwa zawodów / imprezy</label>
                <input type="text" name="competition_name" class="form-control"
                       placeholder="np. Mistrzostwa Polski Seniorów 2024, Kraków"
                       maxlength="200">
            </div>

            <div class="mb-4">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="np. dyscyplina, klasa, dodatkowe informacje"></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-trophy"></i> Zapisz osiągnięcie
                </button>
                <a href="<?= url('members/' . $member['id']) ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
