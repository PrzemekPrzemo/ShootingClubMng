<h2 class="h4 mb-4"><i class="bi bi-heart-pulse"></i> Badania lekarskie</h2>

<div class="row g-3">
<div class="col-lg-8">
    <div class="card">
        <div class="card-header"><strong>Historia badań</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Typ badania</th>
                        <th>Data</th>
                        <th>Ważne do</th>
                        <th>Status</th>
                        <th>Plik</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($exams as $ex): ?>
                    <?php
                    $until = $ex['valid_until'] ?? null;
                    $days  = $until ? days_until($until) : null;
                    $cls   = $days === null ? 'secondary' : alert_class($days, 30);
                    ?>
                    <tr>
                        <td><?= e($ex['exam_type_name'] ?? $ex['exam_type'] ?? '—') ?></td>
                        <td class="small"><?= format_date($ex['exam_date']) ?></td>
                        <td class="small"><?= $until ? format_date($until) : '—' ?></td>
                        <td>
                            <?php if ($days !== null): ?>
                                <span class="badge bg-<?= $cls ?>">
                                    <?= $days >= 0 ? "za {$days} dni" : abs($days) . ' dni temu' ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($ex['file_path']) || !empty($ex['document_path'])): ?>
                                <a href="<?= url('members/' . $memberUser['id'] . '/exams/' . $ex['id'] . '/file') ?>" class="btn btn-outline-secondary btn-sm py-0" target="_blank">
                                    <i class="bi bi-file-earmark"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Brak wpisów badań.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <div class="card">
        <div class="card-header"><strong><i class="bi bi-upload"></i> Prześlij zaświadczenie</strong></div>
        <div class="card-body">
            <form method="post" action="<?= url('portal/exams/upload') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <?php if ($examTypes): ?>
                <div class="mb-2">
                    <label class="form-label">Typ badania <span class="text-danger">*</span></label>
                    <select name="exam_type_id" class="form-select form-select-sm" required>
                        <option value="">— wybierz —</option>
                        <?php foreach ($examTypes as $et): ?>
                            <option value="<?= $et['id'] ?>"><?= e($et['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-2">
                    <label class="form-label">Data badania <span class="text-danger">*</span></label>
                    <input type="date" name="exam_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label">Data ważności</label>
                    <input type="date" name="valid_until" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label">Plik (PDF/JPG/PNG, max 5MB) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control form-control-sm" required
                           accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <button type="submit" class="btn btn-danger btn-sm w-100">
                    <i class="bi bi-upload"></i> Prześlij badanie
                </button>
            </form>
            <p class="text-muted small mt-2">Po przesłaniu zaświadczenie zostanie sprawdzone przez biuro klubu.</p>
        </div>
    </div>
</div>
</div>
