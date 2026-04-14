<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . (int)$member['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">
        <i class="bi bi-shield-lock"></i>
        Broń osobista — <?= e($member['full_name']) ?>
    </h2>
    <button class="btn btn-danger btn-sm ms-auto" type="button"
            data-bs-toggle="collapse" data-bs-target="#addWeaponForm">
        <i class="bi bi-plus-lg"></i> Dodaj broń
    </button>
</div>

<?php if (!empty($member['firearm_permit_number'])): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-card-text fs-5"></i>
    <div><strong>Numer pozwolenia na broń:</strong> <?= e($member['firearm_permit_number']) ?></div>
</div>
<?php endif; ?>

<!-- Inline add form -->
<div class="collapse mb-4" id="addWeaponForm">
    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <strong><i class="bi bi-plus-circle"></i> Dodaj broń — <?= e($member['full_name']) ?></strong>
        </div>
        <div class="card-body">
            <form method="post" action="<?= url('members/' . (int)$member['id'] . '/weapons') ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Nazwa / model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required
                               placeholder="np. Glock 17, CZ 75">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Typ</label>
                        <select class="form-select" name="type">
                            <?php foreach ($types as $k => $v): ?>
                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kaliber</label>
                        <input type="text" class="form-control" name="caliber" placeholder="np. 9mm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Producent</label>
                        <input type="text" class="form-control" name="manufacturer"
                               placeholder="np. Glock, CZ, H&K">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Numer seryjny</label>
                        <input type="text" class="form-control" name="serial_number"
                               placeholder="Nr seryjny">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nr pozwolenia na broń</label>
                        <input type="text" class="form-control" name="permit_number"
                               placeholder="Nr decyzji">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nr książeczki broni</label>
                        <input type="text" class="form-control" name="booklet_number"
                               placeholder="Nr karty rejestracyjnej">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Uwagi</label>
                        <input type="text" class="form-control" name="notes"
                               placeholder="Opcjonalne uwagi">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="newWeaponActive" value="1" checked>
                            <label class="form-check-label" for="newWeaponActive">
                                Broń aktywna (w posiadaniu)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-plus-circle"></i> Dodaj broń
                    </button>
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-toggle="collapse" data-bs-target="#addWeaponForm">Anuluj</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Weapon list -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($weapons)): ?>
        <p class="text-muted p-3 mb-0">Brak zarejestrowanej broni osobistej dla tego zawodnika.</p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nazwa / model</th>
                    <th>Typ</th>
                    <th>Kaliber</th>
                    <th>Numer seryjny</th>
                    <th>Pozwolenie</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($weapons as $w): ?>
                <tr class="<?= $w['is_active'] ? '' : 'table-secondary text-muted' ?>">
                    <td>
                        <span class="fw-semibold"><?= e($w['name']) ?></span>
                        <?php if (!empty($w['manufacturer'])): ?>
                        <div class="small text-muted"><?= e($w['manufacturer']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($types[$w['type']] ?? $w['type']) ?></td>
                    <td><?= $w['caliber'] ? e($w['caliber']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?php if (!empty($w['serial_number'])): ?>
                        <code><?= e($w['serial_number']) ?></code>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($w['permit_number'])): ?>
                        <span class="small"><?= e($w['permit_number']) ?></span>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($w['is_active']): ?>
                        <span class="badge bg-success">Aktywna</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Nieaktywna</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('members/' . (int)$member['id'] . '/weapons/' . (int)$w['id'] . '/edit') ?>"
                           class="btn btn-sm btn-outline-primary" title="Edytuj">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="post"
                              action="<?= url('members/' . (int)$member['id'] . '/weapons/' . (int)$w['id'] . '/delete') ?>"
                              class="d-inline"
                              onsubmit="return confirm('Usunąć tę broń z rejestru?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php if (!empty($w['notes'])): ?>
                <tr class="<?= $w['is_active'] ? '' : 'table-secondary text-muted' ?>">
                    <td colspan="7" class="small text-muted pt-0 pb-2">
                        <i class="bi bi-chat-left-text"></i> <?= e($w['notes']) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
