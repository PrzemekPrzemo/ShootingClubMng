<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('judges') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('judges/create') : url('judges/' . $license['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Zawodnik <span class="text-danger">*</span></label>
                <select name="member_id" class="form-select" required>
                    <option value="">— wybierz zawodnika —</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= ($license['member_id'] ?? null) == $m['id'] ? 'selected' : '' ?>>
                        <?= e($m['full_name']) ?> [<?= e($m['member_number']) ?>]
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Klasa sędziowska <span class="text-danger">*</span></label>
                    <select name="judge_class" class="form-select" required>
                        <?php foreach (['III' => 'III (podstawowa)', 'II' => 'II', 'I' => 'I', 'P' => 'P (państwowy)'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($license['judge_class'] ?? 'III') === $val ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dyscyplina</label>
                    <select name="discipline_id" class="form-select">
                        <option value="">— wszystkie —</option>
                        <?php foreach ($disciplines as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($license['discipline_id'] ?? null) == $d['id'] ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Numer licencji</label>
                <input type="text" name="license_number" class="form-control"
                       value="<?= e($license['license_number'] ?? '') ?>"
                       placeholder="np. PomZSS/S/2024/001">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data wystawienia <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" class="form-control"
                           value="<?= e($license['issue_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ważna do <span class="text-danger">*</span></label>
                    <input type="date" name="valid_until" class="form-control"
                           value="<?= e($license['valid_until'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Uwagi</label>
                <textarea name="notes" class="form-control" rows="3"><?= e($license['notes'] ?? '') ?></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Dodaj licencję' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('judges') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
