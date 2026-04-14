<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-gear"></i> Konfiguracja systemu</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-auto">
        <a href="<?= url('config') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-sliders"></i> Ustawienia
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/categories') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-tags"></i> Kategorie wiekowe
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/disciplines') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bullseye"></i> Dyscypliny
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/member-classes') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-award"></i> Klasy zawodników
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/medical-exam-types') ?>" class="btn btn-outline-secondary btn-sm active">
            <i class="bi bi-heart-pulse"></i> Typy badań
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/users') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-people"></i> Użytkownicy
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Lista typów badań -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><strong>Typy badań lekarskich</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nazwa</th>
                            <th class="text-center">Wymagane dla</th>
                            <th class="text-center">Ważność (mies.)</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentClubId = \App\Helpers\ClubContext::current();
                    foreach ($examTypes as $t):
                        $isGlobal = empty($t['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null;
                    ?>
                        <tr class="<?= $t['is_active'] ? '' : 'table-secondary text-muted' ?>">
                            <td class="text-muted small"><?= $t['sort_order'] ?></td>
                            <td>
                                <?= e($t['name']) ?>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis globalny — tylko do odczytu">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $rfMap = ['patent' => 'Patent', 'license' => 'Licencja', 'both' => 'Obydwa'];
                                $rfCls = ['patent' => 'secondary', 'license' => 'primary', 'both' => 'info'];
                                ?>
                                <span class="badge bg-<?= $rfCls[$t['required_for']] ?? 'secondary' ?>">
                                    <?= $rfMap[$t['required_for']] ?? $t['required_for'] ?>
                                </span>
                            </td>
                            <td class="text-center"><?= $t['validity_months'] ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $t['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $t['is_active'] ? 'Aktywny' : 'Nieaktywny' ?>
                                </span>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($canEdit): ?>
                                <a href="<?= url('config/medical-exam-types?edit=' . $t['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('config/medical-exam-types/' . $t['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Zmienić status tego typu badania?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm <?= $t['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-0">
                                        <i class="bi bi-<?= $t['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <?php elseif ($currentClubId !== null): ?>
                                <form method="post" action="<?= url('config/dictionary/exclude') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dictionary" value="medical_exam_types">
                                    <input type="hidden" name="entry_id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/medical-exam-types">
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-1" title="Ukryj dla tego klubu"
                                            onclick="return confirm('Ukryć ten typ badania globalny dla Twojego klubu?')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($examTypes)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Brak zdefiniowanych typów badań.</td></tr>
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
                                    <input type="hidden" name="dictionary" value="medical_exam_types">
                                    <input type="hidden" name="entry_id" value="<?= $excl['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/medical-exam-types">
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
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <strong><?= $editItem ? 'Edytuj typ badania' : 'Dodaj typ badania' ?></strong>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/medical-exam-types') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($editItem['name'] ?? '') ?>" required
                               placeholder="np. Badanie ogólne (lekarz med. sportowej)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wymagane dla</label>
                        <select name="required_for" class="form-select">
                            <?php foreach (['both' => 'Patentu i Licencji', 'patent' => 'Tylko Patentu', 'license' => 'Tylko Licencji'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($editItem['required_for'] ?? 'both') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ważność (miesiące)</label>
                        <input type="number" name="validity_months" class="form-control" min="1" max="120"
                               value="<?= e($editItem['validity_months'] ?? 12) ?>">
                        <div class="form-text">Ile miesięcy ważne jest badanie tego typu?</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kolejność sortowania</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="<?= e($editItem['sort_order'] ?? 0) ?>">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1"
                               <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Aktywny</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <?= $editItem ? 'Zapisz zmiany' : 'Dodaj typ badania' ?>
                        </button>
                        <?php if ($editItem): ?>
                        <a href="<?= url('config/medical-exam-types') ?>" class="btn btn-outline-secondary">Anuluj</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><strong><i class="bi bi-info-circle"></i> Informacja</strong></div>
            <div class="card-body small text-muted">
                <p>Typy badań lekarskich wymagane przez PZSS:</p>
                <ul class="mb-0">
                    <li>Badanie ogólne — lekarz medycyny sportowej</li>
                    <li>Badanie psychologiczne</li>
                    <li>Badanie psychiatryczne (co 2 lata)</li>
                    <li>Badanie okulistyczne</li>
                </ul>
                <p class="mt-2 mb-0">Wymagane dla patentu strzeleckiego i licencji zawodniczej.</p>
            </div>
        </div>
    </div>
</div>
