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
        <form method="post" enctype="multipart/form-data"
              action="<?= $mode === 'create'
                ? url('members/' . $member['id'] . '/exams/create')
                : url('members/' . $member['id'] . '/exams/' . $exam['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <?php if (!empty($examTypes)): ?>
            <div class="mb-3">
                <label class="form-label">Typ badania</label>
                <select name="exam_type_id" class="form-select" id="examTypeSelect">
                    <option value="">— ogólne (bez typu) —</option>
                    <?php foreach ($examTypes as $t): ?>
                    <option value="<?= $t['id'] ?>"
                            data-months="<?= $t['validity_months'] ?>"
                            <?= ($exam['exam_type_id'] ?? null) == $t['id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?> (<?= $t['validity_months'] ?> mies.)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Data badania <span class="text-danger">*</span></label>
                <input type="date" name="exam_date" id="examDate" class="form-control"
                       value="<?= e($exam['exam_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ważne do <span class="text-danger">*</span></label>
                <input type="date" name="valid_until" id="validUntil" class="form-control"
                       value="<?= e($exam['valid_until'] ?? '') ?>" required>
                <div class="form-text">
                    Wybór typu badania automatycznie uzupełnia datę ważności.
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Zaświadczenie (PDF/JPG/PNG, max 5 MB)</label>
                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($exam['file_path'])): ?>
                <div class="form-text">
                    <i class="bi bi-paperclip"></i>
                    Obecny plik:
                    <a href="<?= url('members/' . $member['id'] . '/exams/' . $exam['id'] . '/file') ?>" target="_blank">
                        Pobierz / podgląd
                    </a>
                    — wgraj nowy by zastąpić.
                </div>
                <?php endif; ?>
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

<script>
(function() {
    const typeSelect = document.getElementById('examTypeSelect');
    const examDate   = document.getElementById('examDate');
    const validUntil = document.getElementById('validUntil');

    function recalcValid() {
        if (!typeSelect) return;
        const opt    = typeSelect.options[typeSelect.selectedIndex];
        const months = parseInt(opt.dataset.months || '0');
        const dateVal = examDate ? examDate.value : '';
        if (!months || !dateVal) return;

        const d = new Date(dateVal);
        d.setMonth(d.getMonth() + months);
        const pad = n => String(n).padStart(2, '0');
        validUntil.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    if (typeSelect) typeSelect.addEventListener('change', recalcValid);
    if (examDate)   examDate.addEventListener('change', recalcValid);
})();
</script>
