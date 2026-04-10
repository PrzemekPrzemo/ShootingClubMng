<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row g-3">
    <!-- Lista typów -->
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
                                    ? '<span class="badge bg-success">aktywny</span>'
                                    : '<span class="badge bg-secondary">nieaktywny</span>' ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($canEdit): ?>
                                <a href="<?= url('config/member-types?edit=' . $item['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('config/member-types/' . $item['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć typ: <?= e($item['name']) ?>?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php elseif ($currentClubId !== null): ?>
                                <form method="post" action="<?= url('config/dictionary/exclude') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dictionary" value="member_types">
                                    <input type="hidden" name="entry_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/member-types">
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-1" title="Ukryj dla tego klubu"
                                            onclick="return confirm('Ukryć ten typ globalny dla Twojego klubu?')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Brak wpisów.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($excludedGlobal) && $currentClubId !== null): ?>
                <details class="border-top">
                    <summary class="px-3 py-2 small text-muted" style="cursor:pointer; list-style:none; user-select:none">
                        <i class="bi bi-eye-slash me-1"></i>Ukryte wpisy globalne (<?= count($excludedGlobal) ?>)
                    </summary>
                    <table class="table table-sm mb-0 bg-light">
                        <tbody>
                        <?php foreach ($excludedGlobal as $excl): ?>
                        <tr class="text-muted">
                            <td><?= e($excl['name']) ?> <span class="badge bg-secondary">Globalny</span></td>
                            <td class="text-end pe-3">
                                <form method="post" action="<?= url('config/dictionary/restore') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dictionary" value="member_types">
                                    <input type="hidden" name="entry_id" value="<?= $excl['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/member-types">
                                    <button class="btn btn-xs btn-outline-success py-0 px-1" title="Przywróć do słownika">
                                        <i class="bi bi-eye"></i> Przywróć
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Formularz -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <strong><?= $editItem ? 'Edytuj typ' : 'Dodaj typ' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/member-types') ?>" class="btn btn-xs btn-outline-secondary float-end py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/member-types') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="np. rekreacyjny, wyczynowy...">
                        <div class="form-text">Wartość zapisywana w bazie dla każdego zawodnika.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kolejność sortowania</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm"
                               value="<?= (int)($editItem['sort_order'] ?? 0) ?>" min="0">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="mt_is_active" class="form-check-input" value="1"
                               <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mt_is_active">Aktywny</label>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj typ' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle"></i>
            Typy globalne (rekreacyjny, wyczynowy) widoczne we wszystkich klubach.<br>
            Typy dodane z poziomu klubu widoczne tylko w tym klubie.<br>
            Nazwa wpisywana jest bezpośrednio do rekordu zawodnika — zmiana nazwy wpisu nie aktualizuje istniejących zawodników.
        </div>
    </div>
</div>
