<?php
$navItems = [
    'config'                    => ['bi-sliders',      'Ustawienia'],
    'config/categories'         => ['bi-tags',         'Kategorie wiekowe'],
    'config/disciplines'        => ['bi-bullseye',     'Dyscypliny'],
    'config/member-classes'     => ['bi-award',        'Klasy zawodników'],
    'config/medical-exam-types' => ['bi-heart-pulse',  'Typy badań'],
    'config/license-types'      => ['bi-card-checklist','Typy licencji'],
    'config/fee-rates'          => ['bi-cash-coin',    'Cennik składek'],
    'config/users'              => ['bi-people',       'Użytkownicy'],
];
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-gear"></i> Konfiguracja systemu</h2>
</div>

<div class="row g-2 mb-4">
    <?php foreach ($navItems as $path => [$icon, $label]): ?>
    <div class="col-auto">
        <a href="<?= url($path) ?>"
           class="btn btn-outline-secondary btn-sm <?= str_ends_with($currentPath, ltrim($path, '/')) ? 'active' : '' ?>">
            <i class="bi <?= $icon ?>"></i> <?= $label ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">

    <!-- ── Lista typów licencji ──────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><strong>Zdefiniowane typy licencji</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Kol.</th>
                            <th>Nazwa</th>
                            <th>Kod</th>
                            <th class="text-center">Ważność (mies.)</th>
                            <th>Opis</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentClubId = \App\Helpers\ClubContext::current();
                    foreach ($licenseTypes as $lt):
                        $isGlobal = empty($lt['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null;
                    ?>
                        <tr class="<?= $lt['is_active'] ? '' : 'table-secondary text-muted' ?>">
                            <td class="text-center text-muted small"><?= $lt['sort_order'] ?></td>
                            <td>
                                <strong><?= e($lt['name']) ?></strong>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis globalny — tylko do odczytu">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($lt['short_code']) ?></code></td>
                            <td class="text-center">
                                <?= $lt['validity_months'] !== null
                                    ? $lt['validity_months'] . ' mies.'
                                    : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="small text-muted"><?= e($lt['description'] ?? '') ?></td>
                            <td>
                                <?php if ($lt['is_active']): ?>
                                    <span class="badge bg-success">aktywny</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">nieaktywny</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($lt['id'] !== null && $canEdit): ?>
                                <a href="<?= url('config/license-types?edit=' . $lt['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post"
                                      action="<?= url('config/license-types/' . $lt['id'] . '/toggle') ?>"
                                      class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $lt['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-0 px-1"
                                            title="<?= $lt['is_active'] ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                        <i class="bi bi-<?= $lt['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>
                                <form method="post"
                                      action="<?= url('config/license-types/' . $lt['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć/dezaktywować ten typ licencji?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php elseif (!$canEdit): ?>
                                <span class="text-muted" title="Wpis globalny — tylko do odczytu"><i class="bi bi-lock"></i></span>
                                <?php else: ?>
                                <span class="text-muted small">wbudowany</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($licenseTypes)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">Brak typów licencji.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Formularz dodaj/edytuj ─────────────────────────────────────── -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <strong><?= $editItem ? 'Edytuj typ licencji' : 'Dodaj typ licencji' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/license-types') ?>"
                       class="btn btn-xs btn-outline-secondary ms-auto py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/license-types') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>"
                               placeholder="np. Zawodnicza PZSS">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Kod skrócony <span class="text-danger">*</span></label>
                        <input type="text" name="short_code" class="form-control form-control-sm" required
                               value="<?= e($editItem['short_code'] ?? '') ?>"
                               placeholder="np. zawodnicza">
                        <div class="form-text">Unikalny identyfikator, małe litery, bez spacji</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Ważność (miesiące)</label>
                        <input type="number" name="validity_months" class="form-control form-control-sm"
                               min="1" max="120"
                               value="<?= e($editItem['validity_months'] ?? '') ?>"
                               placeholder="np. 12 (puste = bezterminowa)">
                        <div class="form-text">Domyślna ważność przy wystawianiu licencji</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"
                                  placeholder="np. Licencja sportowa PZSS uprawniająca do startów w zawodach"><?= e($editItem['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Kolejność</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm"
                                   min="0" value="<?= e($editItem['sort_order'] ?? 0) ?>">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="ltActive" value="1"
                                       <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ltActive">Aktywny</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-<?= $editItem ? 'check-lg' : 'plus-lg' ?>"></i>
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj typ licencji' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <strong>Standardowe typy PZSS:</strong><br>
            • <strong>Zawodnicza</strong> — uprawnia do startów w zawodach<br>
            • <strong>Trenerska</strong> — licencja kadry trenerskiej<br>
            • <strong>Patent</strong> — patent strzelecki (rekreacja)<br>
            Możesz dodać własne typy, np. „Klub" lub „Junior".
        </div>
    </div>

</div>
