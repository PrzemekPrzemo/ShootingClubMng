<div class="d-flex align-items-center mb-4 gap-2">
    <h2 class="mb-0"><i class="bi bi-joystick"></i> Środowiska demo</h2>
    <a href="<?= url('admin/demos/activity') ?>" class="btn btn-sm btn-outline-primary ms-auto">
        <i class="bi bi-activity"></i> Raport aktywności
    </a>
</div>

<!-- Create form -->
<div class="card mb-4" style="max-width:600px">
    <div class="card-header"><h5 class="mb-0">Utwórz nowe środowisko demo</h5></div>
    <div class="card-body">
        <form method="post" action="<?= url('admin/demos') ?>">
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Nazwa (opcjonalna)</label>
                    <input type="text" name="name" class="form-control" placeholder="Demo <?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Czas trwania (godziny)</label>
                    <select name="expires_hours" class="form-select">
                        <option value="24" selected>24h</option>
                        <option value="48">48h</option>
                        <option value="72">72h (3 dni)</option>
                        <option value="168">168h (7 dni)</option>
                        <option value="720">720h (30 dni)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg"></i> Utwórz
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Demos list -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Aktywne środowiska demo (<?= count($demos) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th>Użytkownicy</th>
                    <th>Zawodnicy</th>
                    <th>Token / Link demo</th>
                    <th>Wygasa</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demos as $demo): ?>
                <?php
                    $expired = $demo['demo_expires_at'] && strtotime($demo['demo_expires_at']) < time();
                    $rowClass = $expired ? 'table-secondary' : '';
                ?>
                <tr class="<?= $rowClass ?>">
                    <td>
                        <strong><?= e($demo['name']) ?></strong>
                        <?php if ($expired): ?>
                            <span class="badge bg-danger ms-1">Wygasłe</span>
                        <?php else: ?>
                            <span class="badge bg-success ms-1">Aktywne</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$demo['user_count'] ?></td>
                    <td><?= (int)$demo['member_count'] ?></td>
                    <td>
                        <?php if ($demo['demo_token']): ?>
                        <a href="<?= url('demo?token=' . urlencode($demo['demo_token'])) ?>" target="_blank" class="font-monospace small">
                            <i class="bi bi-link-45deg"></i> /demo?token=<?= e(substr($demo['demo_token'], 0, 8)) ?>…
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($demo['demo_expires_at']): ?>
                            <?php $ts = strtotime($demo['demo_expires_at']); ?>
                            <span class="<?= $expired ? 'text-danger' : 'text-muted' ?>">
                                <?= date('d.m.Y H:i', $ts) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">bez limitu</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <!-- Quick login buttons -->
                        <div class="btn-group btn-group-sm me-1">
                            <a href="<?= url("admin/demos/{$demo['id']}/login?role=zarzad") ?>"
                               class="btn btn-outline-primary" title="Zaloguj jako zarząd">
                                <i class="bi bi-person-fill-gear"></i> Zarząd
                            </a>
                            <a href="<?= url("admin/demos/{$demo['id']}/login?role=instruktor") ?>"
                               class="btn btn-outline-success" title="Zaloguj jako instruktor">
                                Instr.
                            </a>
                            <a href="<?= url("admin/demos/{$demo['id']}/login-portal") ?>"
                               class="btn btn-outline-info" title="Zaloguj do portalu zawodnika">
                                Portal
                            </a>
                        </div>

                        <!-- Activity -->
                        <a href="<?= url("admin/demos/{$demo['id']}/activity") ?>"
                           class="btn btn-sm btn-outline-primary" title="Raport aktywności">
                            <i class="bi bi-activity"></i>
                        </a>

                        <!-- Extend form -->
                        <form method="post" action="<?= url("admin/demos/{$demo['id']}/extend") ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hours" value="24">
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="+24h">
                                <i class="bi bi-clock-history"></i>
                            </button>
                        </form>

                        <!-- Reset -->
                        <form method="post" action="<?= url("admin/demos/{$demo['id']}/reset") ?>" class="d-inline"
                              onsubmit="return confirm('Zresetować dane demo? Wszystkie zmiany zostaną usunięte.')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Resetuj dane">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </form>

                        <!-- Delete -->
                        <form method="post" action="<?= url("admin/demos/{$demo['id']}/delete") ?>" class="d-inline"
                              onsubmit="return confirm('Usunąć to środowisko demo?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($demos)): ?>
                <tr><td colspan="6" class="text-muted text-center py-4">
                    <i class="bi bi-joystick"></i> Brak środowisk demo. Utwórz pierwsze powyżej.
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 text-muted small">
    <i class="bi bi-info-circle"></i>
    Środowiska demo są automatycznie usuwane przez cron po wygaśnięciu.
    Możesz też przedłużyć lub usunąć je ręcznie.
    Link publiczny: <code><?= url('demo') ?></code> (pokazuje najnowsze aktywne demo) lub z tokenem.
</div>
