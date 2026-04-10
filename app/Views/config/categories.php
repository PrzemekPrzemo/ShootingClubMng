<?php
$currentClubId = \App\Helpers\ClubContext::current();
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-tags"></i> Kategorie wiekowe</h2>
</div>

<?php if ($currentClubId !== null): ?>
<div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3 small">
    <i class="bi bi-info-circle fs-5 flex-shrink-0"></i>
    <div>
        Wpisy <span class="badge bg-secondary">Globalny</span> to systemowe kategorie widoczne we wszystkich klubach.
        Kliknij <strong>Kopiuj</strong>, aby utworzyć własną wersję, lub <i class="bi bi-eye-slash"></i> <strong>Ukryj</strong>, aby usunąć wpis globalny z widoku Twojego klubu.
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Lista kategorii -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nazwa</th>
                            <th class="text-center">Wiek od</th>
                            <th class="text-center">do</th>
                            <th class="text-center">Kolejność</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat):
                        $isGlobal = empty($cat['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null;
                    ?>
                        <tr>
                            <td>
                                <?= e($cat['name']) ?>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis systemowy — wzorzec dla wszystkich klubów">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= (int)$cat['age_from'] ?></td>
                            <td class="text-center"><?= (int)$cat['age_to'] ?></td>
                            <td class="text-center text-muted small"><?= (int)$cat['sort_order'] ?></td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($canEdit): ?>
                                    <a href="<?= url('config/categories?edit=' . $cat['id']) ?>"
                                       class="btn btn-xs btn-outline-primary py-0 px-1" title="Edytuj">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post"
                                          action="<?= url('config/categories/' . $cat['id'] . '/delete') ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Usunąć kategorię wiekową?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-xs btn-outline-danger py-0 px-1" title="Usuń">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php elseif ($currentClubId !== null): ?>
                                    <a href="<?= url('config/categories?copy=' . $cat['id']) ?>"
                                       class="btn btn-xs btn-outline-success py-0 px-1"
                                       title="Utwórz własną kopię tej kategorii dla klubu">
                                        <i class="bi bi-copy"></i> Kopiuj
                                    </a>
                                    <form method="post" action="<?= url('config/dictionary/exclude') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="dictionary" value="categories">
                                        <input type="hidden" name="entry_id" value="<?= $cat['id'] ?>">
                                        <input type="hidden" name="redirect" value="config/categories">
                                        <button class="btn btn-xs btn-outline-secondary py-0 px-1" title="Ukryj dla tego klubu"
                                                onclick="return confirm('Ukryć tę kategorię globalną dla Twojego klubu?')">
                                            <i class="bi bi-eye-slash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Brak kategorii wiekowych.</td></tr>
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
                            <td class="text-center"><?= (int)$excl['age_from'] ?></td>
                            <td class="text-center"><?= (int)$excl['age_to'] ?></td>
                            <td class="text-end pe-3" colspan="2">
                                <form method="post" action="<?= url('config/dictionary/restore') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dictionary" value="categories">
                                    <input type="hidden" name="entry_id" value="<?= $excl['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/categories">
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

    <!-- Formularz dodaj/edytuj -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <strong>
                    <?php if (!empty($editItem['_is_copy'])): ?>
                        <i class="bi bi-copy me-1"></i> Kopiuj jako własną
                    <?php elseif (!empty($editItem['id'])): ?>
                        Edytuj kategorię
                    <?php else: ?>
                        Dodaj kategorię
                    <?php endif; ?>
                </strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/categories') ?>" class="btn btn-xs btn-outline-secondary py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($editItem['_is_copy'])): ?>
                <div class="alert alert-success py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Tworzysz własną kopię globalnej kategorii. Edytuj ją dowolnie — zmiana dotyczy tylko Twojego klubu.
                </div>
                <?php endif; ?>

                <form method="post" action="<?= url('config/categories') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)($editItem['id'] ?? 0) ?>">

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>"
                               placeholder="np. Juniorzy">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Wiek od</label>
                            <input type="number" name="age_from" min="0" max="120"
                                   class="form-control form-control-sm" required
                                   value="<?= (int)($editItem['age_from'] ?? 0) ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Wiek do</label>
                            <input type="number" name="age_to" min="0" max="255"
                                   class="form-control form-control-sm" required
                                   value="<?= (int)($editItem['age_to'] ?? 99) ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Kolejność</label>
                            <input type="number" name="sort_order" min="0"
                                   class="form-control form-control-sm"
                                   value="<?= (int)($editItem['sort_order'] ?? 0) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <?php if (!empty($editItem['_is_copy'])): ?>
                            <i class="bi bi-plus-lg"></i> Utwórz własną kopię
                        <?php elseif (!empty($editItem['id'])): ?>
                            <i class="bi bi-check-lg"></i> Zapisz zmiany
                        <?php else: ?>
                            <i class="bi bi-plus-lg"></i> Dodaj kategorię
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>

        <?php if ($currentClubId !== null): ?>
        <div class="card mt-3 border-0 bg-light">
            <div class="card-body py-2 small text-muted">
                <i class="bi bi-lightbulb me-1 text-warning"></i>
                Własne kategorie klubu są niezależne od globalnych — możesz je swobodnie edytować i usuwać.
            </div>
        </div>

        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning bg-opacity-10 py-2">
                <strong><i class="bi bi-arrow-repeat me-1"></i> Przelicz kategorie zawodników</strong>
            </div>
            <div class="card-body py-3">
                <p class="small text-muted mb-3">
                    Aktualizuje pole <em>Kategoria wiekowa</em> dla wszystkich zawodników
                    tego klubu na podstawie ich daty urodzenia i bieżących kategorii ze słownika.
                    Zawodnicy bez daty urodzenia lub spoza zdefiniowanych zakresów wiekowych są pomijani.
                </p>
                <form method="post" action="<?= url('config/categories/recalculate') ?>"
                      onsubmit="return confirm('Przelicz kategorie wiekowe dla wszystkich zawodników klubu?\n\nOperacja nadpisze aktualne przypisania kategorii.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-arrow-repeat"></i> Przelicz teraz
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
