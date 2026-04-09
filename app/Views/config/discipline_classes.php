<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row g-3">
    <!-- Lista klas -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nazwa</th>
                            <th>Kolejność</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentClubId = \App\Helpers\ClubContext::current();
                    foreach ($items as $item):
                        $isGlobal = empty($item['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null;
                    ?>
                        <tr class="<?= $item['is_active'] ? '' : 'text-muted' ?>">
                            <td>
                                <?= e($item['name']) ?>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis globalny — tylko do odczytu">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= (int)$item['sort_order'] ?></td>
                            <td>
                                <?= $item['is_active']
                                    ? '<span class="badge bg-success">aktywna</span>'
                                    : '<span class="badge bg-secondary">nieaktywna</span>' ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($canEdit): ?>
                                <a href="<?= url('config/discipline-classes?edit=' . $item['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('config/discipline-classes/' . $item['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć klasę <?= e($item['name']) ?>?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" title="Wpis globalny — tylko do odczytu"><i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Brak wpisów.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formularz -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <strong><?= $editItem ? 'Edytuj klasę' : 'Dodaj klasę' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/discipline-classes') ?>" class="btn btn-xs btn-outline-secondary float-end py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/discipline-classes') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="np. Master, A, B...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kolejność sortowania</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm"
                               value="<?= (int)($editItem['sort_order'] ?? 0) ?>" min="0">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="dc_is_active" class="form-check-input" value="1"
                               <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="dc_is_active">Aktywna</label>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj klasę' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle"></i>
            Klasy globalne (np. Master, A–D) są widoczne we wszystkich klubach.<br>
            Klasy dodane z poziomu klubu są widoczne tylko w tym klubie.
        </div>
    </div>
</div>
