<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-tags"></i> Kategorie wiekowe</h2>
</div>

<div class="row g-3">
    <!-- Lista kategorii -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nazwa</th>
                            <th>Wiek od</th>
                            <th>do</th>
                            <th>Kolejność</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentClubId = \App\Helpers\ClubContext::current();
                    foreach ($categories as $cat):
                        $isGlobal = empty($cat['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null;
                    ?>
                        <tr>
                            <td>
                                <?= e($cat['name']) ?>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis globalny — tylko do odczytu">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$cat['age_from'] ?></td>
                            <td><?= (int)$cat['age_to'] ?></td>
                            <td><?= (int)$cat['sort_order'] ?></td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($canEdit): ?>
                                <a href="<?= url('config/categories?edit=' . $cat['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('config/categories/' . $cat['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć kategorię?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted" title="Wpis globalny — tylko do odczytu"><i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Brak kategorii.</td></tr>
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
                <strong><?= $editItem ? 'Edytuj kategorię' : 'Dodaj kategorię' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/categories') ?>" class="btn btn-xs btn-outline-secondary float-end py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/categories') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="np. Junior">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Wiek od</label>
                            <input type="number" name="age_from" min="0" max="120" class="form-control form-control-sm"
                                   value="<?= (int)($editItem['age_from'] ?? 0) ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Wiek do</label>
                            <input type="number" name="age_to" min="0" max="255" class="form-control form-control-sm"
                                   value="<?= (int)($editItem['age_to'] ?? 99) ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Kolejność</label>
                            <input type="number" name="sort_order" min="0" class="form-control form-control-sm"
                                   value="<?= (int)($editItem['sort_order'] ?? 0) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj kategorię' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
