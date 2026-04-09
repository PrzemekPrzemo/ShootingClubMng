<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row g-3">
    <!-- Lista dyscyplin -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nazwa</th>
                            <th>Kod</th>
                            <th>Opis</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentClubId = \App\Helpers\ClubContext::current();
                    foreach ($disciplines as $d):
                        $isGlobal = empty($d['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null; // superadmin can edit global
                    ?>
                        <tr class="<?= $d['is_active'] ? '' : 'text-muted' ?>">
                            <td>
                                <?= e($d['name']) ?>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis globalny — tylko do odczytu">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($d['short_code']) ?></code></td>
                            <td class="small text-muted"><?= e($d['description'] ?? '') ?></td>
                            <td>
                                <?php if ($d['is_active']): ?>
                                    <span class="badge bg-success">aktywna</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">nieaktywna</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <a href="<?= url('config/disciplines/' . $d['id'] . '/templates') ?>"
                                   class="btn btn-xs btn-outline-info py-0 px-1"
                                   title="Szablony konkurencji dla tej dyscypliny">
                                    <i class="bi bi-list-check"></i>
                                </a>
                                <?php if ($canEdit): ?>
                                <a href="<?= url('config/disciplines?edit=' . $d['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1"><i class="bi bi-pencil"></i></a>

                                <form method="post" action="<?= url('config/disciplines/' . $d['id'] . '/toggle') ?>"
                                      class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $d['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-0 px-1"
                                            title="<?= $d['is_active'] ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                        <i class="bi bi-<?= $d['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>

                                <form method="post" action="<?= url('config/disciplines/' . $d['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć dyscyplinę? Jeśli ma powiązane dane zostanie dezaktywowana.')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" title="Wpis globalny — tylko do odczytu"><i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($disciplines)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Brak dyscyplin.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formularz dodaj/edytuj -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong><?= $editItem ? 'Edytuj dyscyplinę' : 'Dodaj dyscyplinę' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/disciplines') ?>" class="btn btn-xs btn-outline-secondary float-end py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/disciplines') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="np. Pistolet sportowy">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kod skrócony <span class="text-danger">*</span></label>
                        <input type="text" name="short_code" class="form-control form-control-sm" required
                               maxlength="20" style="text-transform:uppercase"
                               value="<?= e($editItem['short_code'] ?? '') ?>" placeholder="np. PS">
                        <div class="form-text">Unikalny, max 20 znaków</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"><?= e($editItem['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                               <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Aktywna</label>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj dyscyplinę' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <strong>Dyscypliny PZSS/ISSF:</strong><br>
            PS — Pistolet sportowy<br>
            KS — Karabin sportowy<br>
            TR — Trap<br>
            SK — Skeet<br>
            SD — Strzelanie dynamiczne<br>
            10AP — 10m Pistolet Pneumatyczny<br>
            10AK — 10m Karabin Pneumatyczny<br>
            50KL — 50m Karabin Leżąc<br>
            50K3 — 50m Karabin 3×40
        </div>
    </div>
</div>
