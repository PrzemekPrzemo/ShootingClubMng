<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row g-3">
    <!-- Lista klas -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Kolejność</th>
                            <th>Nazwa</th>
                            <th>Kod</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $currentClubId = \App\Helpers\ClubContext::current();
                    foreach ($classes as $c):
                        $isGlobal = empty($c['club_id']);
                        $canEdit  = !$isGlobal || $currentClubId === null;
                    ?>
                        <tr class="<?= $c['is_active'] ? '' : 'text-muted' ?>">
                            <td class="text-center"><?= $c['sort_order'] ?></td>
                            <td>
                                <?= e($c['name']) ?>
                                <?php if ($isGlobal && $currentClubId !== null): ?>
                                    <span class="badge bg-secondary ms-1" title="Wpis globalny — tylko do odczytu">Globalny</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($c['short_code']) ?></code></td>
                            <td>
                                <?= $c['is_active']
                                    ? '<span class="badge bg-success">aktywna</span>'
                                    : '<span class="badge bg-secondary">nieaktywna</span>' ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($canEdit): ?>
                                <a href="<?= url('config/member-classes?edit=' . $c['id']) ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('config/member-classes/' . $c['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć tę klasę zawodników?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php elseif ($currentClubId !== null): ?>
                                <form method="post" action="<?= url('config/dictionary/exclude') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dictionary" value="member_classes">
                                    <input type="hidden" name="entry_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/member-classes">
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-1" title="Ukryj dla tego klubu"
                                            onclick="return confirm('Ukryć tę klasę globalną dla Twojego klubu?')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Brak klas zawodników.</td></tr>
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
                            <td><?= e($excl['name']) ?> <code class="small"><?= e($excl['short_code']) ?></code> <span class="badge bg-secondary">Globalny</span></td>
                            <td class="text-end pe-3">
                                <form method="post" action="<?= url('config/dictionary/restore') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="dictionary" value="member_classes">
                                    <input type="hidden" name="entry_id" value="<?= $excl['id'] ?>">
                                    <input type="hidden" name="redirect" value="config/member-classes">
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
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <strong><?= $editItem ? 'Edytuj klasę' : 'Dodaj klasę' ?></strong>
                <?php if ($editItem): ?>
                    <a href="<?= url('config/member-classes') ?>" class="btn btn-xs btn-outline-secondary float-end py-0">Anuluj</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('config/member-classes') ?>">
                    <?= csrf_field() ?>
                    <?php if ($editItem): ?>
                        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               value="<?= e($editItem['name'] ?? '') ?>" placeholder="np. Senior PZSS">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kod skrócony <span class="text-danger">*</span></label>
                        <input type="text" name="short_code" class="form-control form-control-sm" required
                               maxlength="20" style="text-transform:uppercase"
                               value="<?= e($editItem['short_code'] ?? '') ?>" placeholder="np. SEN">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kolejność wyświetlania</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" min="0"
                               value="<?= $editItem['sort_order'] ?? 0 ?>">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="is_active_cls" class="form-check-input" value="1"
                               <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_cls">Aktywna</label>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <?= $editItem ? 'Zapisz zmiany' : 'Dodaj klasę' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <strong>Przykłady:</strong><br>
            JUN — Junior PZSS<br>
            SEN — Senior PZSS<br>
            WET — Weteran<br>
            OPEN — Zawodnik Open<br>
            K — Kobiety<br>
            M — Mężczyźni<br>
            <br>
            Klasa jest przypisywana do profilu zawodnika i wyświetlana na metryczce startowej.
        </div>
    </div>
</div>
