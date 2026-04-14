<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-card-checklist"></i> Licencje PZSS</h2>
    <div class="d-flex gap-2">
        <a href="<?= url('judges') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person-badge"></i> Licencje sędziowskie
        </a>
        <a href="<?= url('licenses/create') ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-plus-lg"></i> Dodaj licencję
        </a>
    </div>
</div>

<form method="get" action="<?= url('licenses') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Szukaj (nazwisko, nr licencji…)"
                       value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2">
                <select name="license_type" class="form-select form-select-sm">
                    <option value="">Wszystkie typy</option>
                    <?php foreach ($licenseTypes ?? [] as $lt): ?>
                    <option value="<?= e($lt['short_code']) ?>"
                            <?= $filters['license_type'] === $lt['short_code'] ? 'selected' : '' ?>>
                        <?= e($lt['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Wszystkie statusy</option>
                    <option value="aktywna"    <?= $filters['status']==='aktywna'    ? 'selected':'' ?>>Aktywna</option>
                    <option value="wygasla"    <?= $filters['status']==='wygasla'    ? 'selected':'' ?>>Wygasła</option>
                    <option value="zawieszona" <?= $filters['status']==='zawieszona' ? 'selected':'' ?>>Zawieszona</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Szukaj</button>
                <a href="<?= url('licenses') ?>" class="btn btn-outline-secondary btn-sm">Resetuj</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Zawodnik</th>
                        <th>Typ</th>
                        <th>Nr licencji</th>
                        <th>Dyscyplina</th>
                        <th>Wydana</th>
                        <th>Ważna do</th>
                        <th>Termin</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $lic): ?>
                    <?php $days = days_until($lic['valid_until']); ?>
                    <tr>
                        <td>
                            <a href="<?= url('members/' . $lic['member_id']) ?>" class="text-decoration-none">
                                <?= e($lic['last_name']) ?> <?= e($lic['first_name']) ?>
                            </a>
                            <br><small class="text-muted"><?= e($lic['member_number']) ?></small>
                        </td>
                        <td>
                            <?php
                            // Show name from licenseTypes if available, fall back to raw value
                            $ltName = $lic['license_type'];
                            foreach ($licenseTypes ?? [] as $lt) {
                                if ($lt['short_code'] === $lic['license_type']) { $ltName = $lt['name']; break; }
                            }
                            ?>
                            <span class="badge bg-secondary"><?= e($ltName) ?></span>
                        </td>
                        <td><code><?= e($lic['license_number']) ?></code>
                            <?php if ($lic['pzss_qr_code']): ?>
                                <a href="<?= e($lic['pzss_qr_code']) ?>" target="_blank" title="PZSS">
                                    <i class="bi bi-qr-code text-muted"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e($lic['discipline_names'] ?? '—') ?></td>
                        <td class="small"><?= format_date($lic['issue_date']) ?></td>
                        <td class="small"><?= format_date($lic['valid_until']) ?></td>
                        <td>
                            <span class="badge bg-<?= alert_class($days, 60) ?>">
                                <?= $days === null ? 'bezterminowa' : ($days >= 0 ? "za {$days} dni" : abs($days) . ' dni temu') ?>
                            </span>
                        </td>
                        <td>
                            <?php $sc = match($lic['status']) { 'aktywna'=>'success', 'wygasla'=>'danger', 'zawieszona'=>'warning', default=>'secondary' }; ?>
                            <span class="badge bg-<?= $sc ?>"><?= e($lic['status']) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('licenses/' . $lic['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm py-0">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="<?= url('licenses/' . $lic['id'] . '/delete') ?>"
                                  class="d-inline" onsubmit="return confirm('Usunąć licencję?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm py-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Brak licencji.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($result['last_page'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $result['last_page']; $p++): ?>
            <li class="page-item <?= $p === $result['current_page'] ? 'active':'' ?>">
                <a class="page-link" href="<?= url('licenses?' . http_build_query(array_merge($filters, ['page'=>$p]))) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<p class="text-muted small mt-2">Łącznie: <?= $result['total'] ?> licencji</p>
