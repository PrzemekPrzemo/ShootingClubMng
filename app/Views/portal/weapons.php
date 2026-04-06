<h2 class="h4 mb-3"><i class="bi bi-shield-lock"></i> Moja broń</h2>

<?php if (!empty($member['firearm_permit_number'])): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-card-text fs-5"></i>
    <div>
        <strong>Numer pozwolenia na broń:</strong> <?= e($member['firearm_permit_number']) ?>
        <div class="small text-muted">Aby zaktualizować numer pozwolenia, skontaktuj się z administracją klubu.</div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-secondary mb-3">
    <i class="bi bi-info-circle"></i>
    Nie masz zarejestrowanego numeru pozwolenia na broń. Skontaktuj się z administracją.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($weapons)): ?>
        <p class="text-muted p-3 mb-0">
            Brak zarejestrowanej broni. Aby dodać broń do swojego profilu, skontaktuj się z zarządem lub instruktorem klubu.
        </p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nazwa / model</th>
                    <th>Typ</th>
                    <th>Kaliber</th>
                    <th>Numer seryjny</th>
                    <th>Nr pozwolenia</th>
                    <th>Status</th>
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
                        <span class="badge bg-secondary">Wycofana</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($w['notes'])): ?>
                <tr class="<?= $w['is_active'] ? '' : 'table-secondary text-muted' ?>">
                    <td colspan="6" class="small text-muted pt-0 pb-2">
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

<p class="text-muted small mt-3">
    <i class="bi bi-info-circle"></i>
    Rejestr broni jest zarządzany przez administrację klubu. W razie pytań lub konieczności aktualizacji danych, skontaktuj się z zarządem.
</p>
