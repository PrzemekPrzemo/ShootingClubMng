<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config/disciplines') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><i class="bi bi-list-check"></i> Szablony konkurencji</h2>
    <span class="badge bg-secondary"><?= e($discipline['name']) ?></span>
    <code class="ms-1 small"><?= e($discipline['short_code']) ?></code>
</div>

<div class="alert alert-info small py-2">
    <i class="bi bi-info-circle"></i>
    Szablony służą jako baza do szybkiego dodawania konkurencji podczas tworzenia zawodów.
    Nie są widoczne w kartach zawodników.
</div>

<div class="row g-3">

    <!-- ── Lista szablonów ──────────────────────────────────────────── -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><strong>Zdefiniowane konkurencje</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Kol.</th>
                            <th>Nazwa konkurencji</th>
                            <th class="text-center">Strzałów</th>
                            <th>Punktacja</th>
                            <th class="text-center">Maks.</th>
                            <th>Opis</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stMap = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Traf./Chyb.'];
                    foreach ($templates as $t):
                    ?>
                        <tr class="<?= $t['is_active'] ? '' : 'text-muted table-secondary' ?>">
                            <td class="text-center text-muted"><?= $t['sort_order'] ?></td>
                            <td>
                                <strong><?= e($t['name']) ?></strong>
                            </td>
                            <td class="text-center">
                                <?= $t['shots_count'] ?? '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= $stMap[$t['scoring_type']] ?? $t['scoring_type'] ?>
                                </span>
                            </td>
                            <td class="text-center small text-muted">
                                <?= $t['max_score'] !== null ? number_format((float)$t['max_score'], 0, '.', '') : '—' ?>
                            </td>
                            <td class="small text-muted"><?= e($t['description'] ?? '') ?></td>
                            <td>
                                <?php if ($t['is_active']): ?>
                                    <span class="badge bg-success">aktywny</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">nieaktywny</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <a href="<?= url('config/disciplines/' . $discipline['id'] . '/templates?edit=' . $t['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1"><i class="bi bi-pencil"></i></a>

                                <form method="post"
                                      action="<?= url('config/disciplines/' . $discipline['id'] . '/templates/' . $t['id'] . '/toggle') ?>"
                                      class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs <?= $t['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> py-0 px-1"
                                            title="<?= $t['is_active'] ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                        <i class="bi bi-<?= $t['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>

                                <form method="post"
                                      action="<?= url('config/disciplines/' . $discipline['id'] . '/templates/' . $t['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć ten szablon?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Brak szablonów. Dodaj pierwszy po prawej.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Formularz dodaj/edytuj ───────────────────────────────────── -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <strong><?= $editItem ? 'Edytuj szablon' : 'Dodaj szablon' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/disciplines/' . $discipline['id'] . '/templates') ?>"
                       class="btn btn-xs btn-outline-secondary ms-auto py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/disciplines/' . $discipline['id'] . '/templates') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa konkurencji <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>"
                               placeholder="np. 10m Pistolet Pneumatyczny — 60 strzałów">
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">Liczba strzałów</label>
                            <input type="number" name="shots_count" class="form-control form-control-sm"
                                   min="1" max="255"
                                   value="<?= e($editItem['shots_count'] ?? '') ?>"
                                   placeholder="np. 60">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Maks. wynik</label>
                            <input type="number" name="max_score" class="form-control form-control-sm"
                                   min="0" step="0.1"
                                   value="<?= e($editItem['max_score'] ?? '') ?>"
                                   placeholder="np. 600">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Typ punktacji</label>
                        <select name="scoring_type" class="form-select form-select-sm">
                            <?php foreach (['decimal'=>'Dziesiętna (np. 10.9)','integer'=>'Całkowita (np. 98)','hit_miss'=>'Trafiony / Chybiony'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($editItem['scoring_type'] ?? 'decimal') === $v ? 'selected' : '' ?>>
                                <?= $l ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Opis / notatka</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"
                                  placeholder="np. Finał olimpijski, 3 serie × 20"><?= e($editItem['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Kolejność</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm"
                                   min="0" value="<?= e($editItem['sort_order'] ?? 0) ?>">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check mb-1">
                                <input type="checkbox" name="is_active" class="form-check-input" id="tplActive" value="1"
                                       <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tplActive">Aktywny</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-<?= $editItem ? 'check-lg' : 'plus-lg' ?>"></i>
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj szablon' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
