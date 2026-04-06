<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-journal-text"></i> Dziennik audytu</h2>
</div>

<!-- Config nav tabs -->
<div class="row g-2 mb-4 flex-wrap">
    <?php $uri = $_SERVER['REQUEST_URI']; ?>
    <div class="col-auto">
        <a href="<?= url('config') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-sliders"></i> Ustawienia
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/categories') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/categories') ? 'active':'' ?>">
            <i class="bi bi-tags"></i> Kategorie wiekowe
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/disciplines') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/disciplines') && !str_contains($uri,'/templates') ? 'active':'' ?>">
            <i class="bi bi-bullseye"></i> Dyscypliny
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/member-classes') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/member-classes') ? 'active':'' ?>">
            <i class="bi bi-award"></i> Klasy zawodników
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/users') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/users') ? 'active':'' ?>">
            <i class="bi bi-people"></i> Użytkownicy
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/notifications') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/notifications') ? 'active':'' ?>">
            <i class="bi bi-bell"></i> Powiadomienia
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/features') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($uri,'/features') ? 'active':'' ?>">
            <i class="bi bi-toggles"></i> Moduły
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/audit-log') ?>" class="btn btn-outline-secondary btn-sm active">
            <i class="bi bi-journal-text"></i> Dziennik audytu
        </a>
    </div>
</div>

<?php
// Normalise variable names — controller may pass 'entries' or 'logs'
$logs = $entries ?? $logs ?? [];
?>

<!-- Filter form -->
<form method="get" action="<?= url('config/audit-log') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">Użytkownik</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Wszyscy użytkownicy</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"
                            <?= ($filters['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['full_name'] ?: $u['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1">Typ encji</label>
                <select name="entity" class="form-select form-select-sm">
                    <option value="">Wszystkie</option>
                    <?php foreach ($entities as $ent): ?>
                        <option value="<?= e($ent) ?>"
                            <?= ($filters['entity'] ?? '') === $ent ? 'selected' : '' ?>>
                            <?= e($ent) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1">Akcja</label>
                <input type="text" name="action" class="form-control form-control-sm"
                       placeholder="np. login, create…"
                       value="<?= e($filters['action'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1">Od daty</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1">Do daty</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filtruj</button>
            </div>
            <div class="col-auto">
                <a href="<?= url('config/audit-log') ?>" class="btn btn-outline-secondary btn-sm">Resetuj</a>
            </div>
        </div>
    </div>
</form>

<!-- Log table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Wpisy dziennika</strong>
        <span class="badge bg-secondary"><?= count($logs) ?> wpisów (maks. 200)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:150px">Data / Czas</th>
                        <th style="width:160px">Użytkownik</th>
                        <th style="width:140px">Akcja</th>
                        <th style="width:120px">Encja</th>
                        <th style="width:60px">ID</th>
                        <th>Szczegóły</th>
                        <th style="width:110px">Adres IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log):
                    // Color-code actions
                    $actionColor = 'secondary';
                    $action = strtolower($log['action'] ?? '');
                    if (str_contains($action, 'delete') || str_contains($action, 'destroy') || str_contains($action, 'remove')) {
                        $actionColor = 'danger';
                    } elseif (str_contains($action, 'create') || str_contains($action, 'insert') || str_contains($action, 'add') || str_contains($action, 'register')) {
                        $actionColor = 'success';
                    } elseif (str_contains($action, 'update') || str_contains($action, 'edit') || str_contains($action, 'save')) {
                        $actionColor = 'primary';
                    } elseif (str_contains($action, 'login') || str_contains($action, 'logout')) {
                        $actionColor = 'info';
                    }
                ?>
                    <tr>
                        <td class="small text-muted font-monospace" style="white-space:nowrap">
                            <?= e($log['created_at'] ?? '') ?>
                        </td>
                        <td class="small">
                            <?php if ($log['user_name']): ?>
                                <span class="fw-semibold"><?= e($log['user_name']) ?></span>
                                <div class="text-muted" style="font-size:.8em"><?= e($log['username'] ?? '') ?></div>
                            <?php elseif ($log['user_id']): ?>
                                <span class="text-muted">UID <?= (int)$log['user_id'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $actionColor ?>" style="font-size:.72rem">
                                <?= e($log['action'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="small">
                            <?php if ($log['entity']): ?>
                                <code class="text-dark"><?= e($log['entity']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-center text-muted">
                            <?= $log['entity_id'] ? (int)$log['entity_id'] : '—' ?>
                        </td>
                        <td class="small text-muted">
                            <?php if ($log['details']): ?>
                                <?= e(mb_strimwidth($log['details'], 0, 120, '…')) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="small font-monospace text-muted">
                            <?= e($log['ip_address'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-journal-x"></i> Brak wpisów dziennika dla wybranych filtrów.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($logs)): ?>
    <div class="card-footer text-muted small">
        Wyświetlono <?= count($logs) ?> najnowszych wpisów. Zmień filtry aby zawęzić wyniki.
    </div>
    <?php endif; ?>
</div>
