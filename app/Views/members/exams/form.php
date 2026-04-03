<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . $member['id'] . '/exams') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-body">
        <p class="text-muted mb-3">
            Zawodnik: <strong><?= e($member['last_name']) ?> <?= e($member['first_name']) ?></strong>
        </p>
        <form method="post" action="<?= $mode === 'create'
            ? url('members/' . $member['id'] . '/exams/create')
            : url('members/' . $member['id'] . '/exams/' . $exam['id'] . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Data badania <span class="text-danger">*</span></label>
                <input type="date" name="exam_date" class="form-control"
                       value="<?= e($exam['exam_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ważne do <span class="text-danger">*</span></label>
                <input type="date" name="valid_until" class="form-control"
                       value="<?= e($exam['valid_until'] ?? '') ?>" required>
                <div class="form-text">Typowo: 1 rok od daty badania.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($exam['notes'] ?? '') ?></textarea>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Dodaj badanie' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('members/' . $member['id'] . '/exams') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
