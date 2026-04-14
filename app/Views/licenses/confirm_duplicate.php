<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('licenses/create') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> <?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="alert alert-warning">
    <strong>Zawodnik <?= e($existing['last_name']) ?> <?= e($existing['first_name']) ?></strong>
    posiada już licencję tego samego typu:
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Istniejąca licencja</strong></div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="text-muted small">Typ</div>
                <div><?= e($existing['type_name'] ?? $existing['license_type'] ?? '—') ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Numer</div>
                <div><code><?= e($existing['license_number']) ?></code></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Data wydania</div>
                <div><?= e($existing['issue_date'] ?? '—') ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Ważna do</div>
                <div>
                    <?php if ($existing['valid_until']): ?>
                        <?= e($existing['valid_until']) ?>
                        <?php $days = (int)((strtotime($existing['valid_until']) - time()) / 86400); ?>
                        <span class="badge bg-<?= $days < 0 ? 'danger' : ($days < 60 ? 'warning' : 'success') ?>">
                            <?= $days >= 0 ? "za {$days} dni" : 'WYGASŁA' ?>
                        </span>
                    <?php else: ?>
                        bezterminowa
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Status</div>
                <div><?= e($existing['status']) ?></div>
            </div>
            <?php if (!empty($existing['discipline_names'])): ?>
            <div class="col-md-6">
                <div class="text-muted small">Dyscypliny</div>
                <div><?= e($existing['discipline_names']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Co chcesz zrobić?</strong></div>
    <div class="card-body">
        <div class="d-grid gap-3">

            <!-- Option 1: Extend -->
            <form method="post" action="<?= url('licenses/create') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="force_action" value="extend">
                <input type="hidden" name="existing_id" value="<?= $existing['id'] ?>">
                <input type="hidden" name="member_id" value="<?= $existing['member_id'] ?>">
                <button type="submit" class="btn btn-success w-100 text-start py-3">
                    <i class="bi bi-arrow-clockwise fs-4 me-2"></i>
                    <span>
                        <strong>Przedłuż obecną licencję</strong><br>
                        <small class="text-white-50">Data ważności zostanie ustawiona na 31.12.<?= date('Y') ?>
                        (lub 31.12.<?= date('Y') + 1 ?> jeśli obecna jest już ważna do końca roku).
                        Pozostałe dane bez zmian.</small>
                    </span>
                </button>
            </form>

            <!-- Option 2: Replace -->
            <form method="post" action="<?= url('licenses/create') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="force_action" value="replace">
                <input type="hidden" name="existing_id" value="<?= $existing['id'] ?>">
                <?php foreach ($pending as $key => $val): ?>
                    <?php if ($key === '_discipline_ids'): ?>
                        <?php foreach ((array)$val as $did): ?>
                            <input type="hidden" name="discipline_ids[]" value="<?= (int)$did ?>">
                        <?php endforeach; ?>
                    <?php elseif (!str_starts_with($key, '_')): ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e($val ?? '') ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-warning w-100 text-start py-3">
                    <i class="bi bi-arrow-repeat fs-4 me-2"></i>
                    <span>
                        <strong>Zastąp obecną licencję nowymi danymi</strong><br>
                        <small>Istniejąca licencja zostanie nadpisana danymi, które właśnie wprowadzono
                        (nowy numer, daty, dyscypliny itp.).</small>
                    </span>
                </button>
            </form>

            <!-- Option 3: Cancel -->
            <a href="<?= url('licenses/create') ?>" class="btn btn-outline-secondary w-100 py-2">
                <i class="bi bi-x-lg me-1"></i> Anuluj — wróć do formularza
            </a>

        </div>
    </div>
</div>

</div>
</div>
