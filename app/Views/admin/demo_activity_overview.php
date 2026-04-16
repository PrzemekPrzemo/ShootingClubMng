<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/demos') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><i class="bi bi-activity"></i> Aktywność wszystkich demo</h2>
    <form method="get" class="ms-auto d-flex gap-2">
        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto">
            <?php foreach ([1, 3, 7, 14, 30, 60, 90] as $d): ?>
                <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>>ostatnie <?= $d ?> dni</option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (empty($hasClubId)): ?>
<div class="alert alert-warning small py-2">
    <i class="bi bi-info-circle"></i>
    <strong>Tryb fallback:</strong> kolumna <code>activity_log.club_id</code> nie istnieje.
    Raport filtruje po powiązaniach <code>user_clubs</code>. Uruchom migrację v25, aby uzyskać pełny raport (także anonimowe zdarzenia).
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><strong>Aktywność per środowisko demo</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Demo</th>
                        <th class="text-end">Zdarzeń</th>
                        <th class="text-end">Użytkowników</th>
                        <th>Ostatnia aktywność</th>
                        <th>Wygasa</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stats as $s): ?>
                    <tr>
                        <td>
                            <strong><?= e($s['name']) ?></strong>
                            <?php if (!empty($s['short_name'])): ?>
                                <div class="small text-muted"><?= e($s['short_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><strong><?= (int)$s['event_count'] ?></strong></td>
                        <td class="text-end"><?= (int)$s['unique_users'] ?></td>
                        <td class="small text-muted"><?= e($s['last_activity'] ?? '—') ?></td>
                        <td class="small text-muted"><?= e($s['demo_expires_at'] ?? 'bez limitu') ?></td>
                        <td class="text-end">
                            <a href="<?= url('admin/demos/' . (int)$s['id'] . '/activity?days=' . $days) ?>"
                               class="btn btn-sm btn-outline-primary py-0">
                                <i class="bi bi-search"></i> Szczegóły
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$stats): ?>
                    <tr><td colspan="6" class="text-center text-muted p-4">Brak środowisk demo.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Najczęstsze akcje (wszystkie demo razem)</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Akcja</th><th class="text-end">Liczba</th></tr></thead>
            <tbody>
            <?php foreach ($topActions as $a): ?>
                <tr><td class="small"><code><?= e($a['action']) ?></code></td><td class="text-end"><strong><?= (int)$a['cnt'] ?></strong></td></tr>
            <?php endforeach; ?>
            <?php if (!$topActions): ?><tr><td colspan="2" class="text-center text-muted p-3">Brak danych.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
