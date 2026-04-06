<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members/' . (int)$member['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">
        <i class="bi bi-shield-lock"></i>
        Broń osobista — <?= e($member['full_name']) ?>
    </h2>
    <a href="<?= url('members/' . (int)$member['id'] . '/weapons/create') ?>" class="btn btn-danger btn-sm ms-auto">
        <i class="bi bi-plus-lg"></i> Dodaj broń
    </a>
</div>

<?php if (!empty($member['firearm_permit_number'])): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-card-text fs-5"></i>
    <div>
        <strong>Numer pozwolenia na broń:</strong> <?= e($member['firearm_permit_number']) ?>
    </div>
</div>
<?php endif; ?>

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
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
