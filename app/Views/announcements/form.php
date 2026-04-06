<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('announcements') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card">
    <div class="card-body">
        <form method="post"
              action="<?= $mode === 'create' ? url('announcements/create') : url('announcements/' . $announcement['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Tytuł <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($announcement['title'] ?? '') ?>" required
                       placeholder="Tytuł ogłoszenia">
            </div>

            <div class="mb-3">
                <label class="form-label">Treść <span class="text-danger">*</span></label>
                <textarea name="body" class="form-control" rows="6" required
                          placeholder="Treść ogłoszenia…"><?= e($announcement['body'] ?? '') ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Priorytet</label>
                    <select name="priority" class="form-select">
                        <option value="normal" <?= ($announcement['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>
                            Normalne
                        </option>
                        <option value="wazne"  <?= ($announcement['priority'] ?? '') === 'wazne'  ? 'selected' : '' ?>>
                            Ważne
                        </option>
                        <option value="pilne"  <?= ($announcement['priority'] ?? '') === 'pilne'  ? 'selected' : '' ?>>
                            Pilne
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Wygasa</label>
                    <input type="date" name="expires_at" class="form-control"
                           value="<?= e($announcement['expires_at'] ?? '') ?>"
                           min="<?= date('Y-m-d') ?>">
                    <div class="form-text">Pozostaw puste jeśli bezterminowe.</div>
                </div>
                <div class="col-md-4 d-flex align-items-end pb-1">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_published"
                               id="is_published" role="switch"
                               <?= !empty($announcement['is_published']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Opublikuj teraz</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <?= $mode === 'create' ? 'Utwórz ogłoszenie' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('announcements') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
