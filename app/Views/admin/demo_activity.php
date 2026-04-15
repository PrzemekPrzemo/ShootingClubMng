<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/demos') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">
        <i class="bi bi-activity"></i> Aktywność demo: <strong><?= e($demo['name']) ?></strong>
    </h2>
    <form method="get" class="ms-auto d-flex gap-2">
        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto">
            <?php foreach ([1, 3, 7, 14, 30, 60, 90] as $d): ?>
                <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>>ostatnie <?= $d ?> dni</option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Zdarzeń w ostatnich <?= $days ?> dniach</div>
                <div class="h3 mb-0"><?= count($events) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Unikalnych użytkowników</div>
                <div class="h3 mb-0"><?= count($byUser) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Aktywnych dni</div>
                <div class="h3 mb-0"><?= count($byDay) ?> / <?= $days ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Najczęstsze akcje</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Akcja</th><th class="text-end">Liczba</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($byAction, 0, 15, true) as $a => $c): ?>
                        <tr><td class="small"><code><?= e($a) ?></code></td><td class="text-end"><strong><?= $c ?></strong></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$byAction): ?><tr><td colspan="2" class="text-center text-muted p-3">Brak danych</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Użytkownicy</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Login</th><th class="text-end">Zdarzeń</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($byUser, 0, 15, true) as $u => $c): ?>
                        <tr><td class="small"><?= e($u) ?></td><td class="text-end"><strong><?= $c ?></strong></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$byUser): ?><tr><td colspan="2" class="text-center text-muted p-3">Brak danych</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Log zdarzeń</strong>
        <small class="text-muted">ostatnie <?= min(1000, count($events)) ?> z <?= count($events) ?></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kiedy</th>
                        <th>Użytkownik</th>
                        <th>Akcja</th>
                        <th>Encja</th>
                        <th>Szczegóły</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td class="small text-muted"><?= e($e['created_at']) ?></td>
                        <td class="small">
                            <?php if ($e['user_id']): ?>
                                <?= e($e['username'] ?? '#' . $e['user_id']) ?>
                                <?php if (!empty($e['full_name'])): ?>
                                    <div class="text-muted" style="font-size:.72rem"><?= e($e['full_name']) ?></div>
                                <?php endif; ?>
                            <?php else: ?><span class="text-muted">anonim</span><?php endif; ?>
                        </td>
                        <td class="small"><code><?= e($e['action']) ?></code></td>
                        <td class="small text-muted">
                            <?= e($e['entity'] ?? '—') ?>
                            <?= $e['entity_id'] ? '#' . (int)$e['entity_id'] : '' ?>
                        </td>
                        <td class="small text-muted" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <span title="<?= e($e['details'] ?? '') ?>"><?= e($e['details'] ?? '—') ?></span>
                        </td>
                        <td class="small text-muted"><?= e($e['ip_address'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$events): ?>
                    <tr><td colspan="6" class="text-center text-muted p-4">Brak aktywności w tym okresie.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
