<?php
$statusLabels = ['pending'=>'Oczekująca','sent'=>'Wysłana','failed'=>'Błąd'];
$statusColors = ['pending'=>'warning','sent'=>'success','failed'=>'danger'];
$typeLabels   = [
    'competition_reminder' => 'Przypomnienie o zawodach',
    'payment_overdue'      => 'Zaległość składki',
    'license_expiry'       => 'Wygasająca licencja',
    'medical_expiry'       => 'Wygasające badania',
];
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-bell"></i> Powiadomienia e-mail</h2>
</div>

<div class="row g-3">

    <!-- Left: settings + actions -->
    <div class="col-md-4">

        <!-- Mail settings -->
        <div class="card mb-3">
            <div class="card-header"><strong>Ustawienia nadawcy</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('config/notifications/settings') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">E-mail nadawcy</label>
                        <input type="email" name="mail_from_email" class="form-control form-control-sm"
                               value="<?= e($settings['mail_from_email']['value'] ?? '') ?>"
                               placeholder="klub@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Nazwa nadawcy</label>
                        <input type="text" name="mail_from_name" class="form-control form-control-sm"
                               value="<?= e($settings['mail_from_name']['value'] ?? '') ?>"
                               placeholder="Klub Strzelecki">
                    </div>
                    <hr class="my-2">
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Zawody — dni przed</label>
                        <input type="number" name="notify_competition_days" class="form-control form-control-sm" min="1"
                               value="<?= e($settings['notify_competition_days']['value'] ?? 7) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Licencja — dni przed wygaśnięciem</label>
                        <input type="number" name="notify_license_days" class="form-control form-control-sm" min="1"
                               value="<?= e($settings['notify_license_days']['value'] ?? 30) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Badania — dni przed wygaśnięciem</label>
                        <input type="number" name="notify_medical_days" class="form-control form-control-sm" min="1"
                               value="<?= e($settings['notify_medical_days']['value'] ?? 30) ?>">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-check-lg"></i> Zapisz ustawienia
                    </button>
                </form>
            </div>
        </div>

        <!-- Generate queue -->
        <div class="card mb-3">
            <div class="card-header"><strong>Generuj powiadomienia</strong></div>
            <div class="card-body d-grid gap-2">
                <?php foreach ([
                    'competition' => ['Zawody (przypomnienia)',     'calendar-event', 'outline-info'],
                    'payment'     => ['Zaległości składek',         'cash-stack',     'outline-warning'],
                    'license'     => ['Wygasające licencje',        'card-checklist', 'outline-primary'],
                    'medical'     => ['Wygasające badania',         'heart-pulse',    'outline-secondary'],
                ] as $type => [$label, $icon, $btnClass]): ?>
                <form method="post" action="<?= url('config/notifications/populate/' . $type) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-<?= $btnClass ?> w-100 text-start">
                        <i class="bi bi-<?= $icon ?> me-2"></i><?= $label ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Send + stats -->
        <div class="card">
            <div class="card-header"><strong>Kolejka</strong></div>
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span class="badge bg-warning text-dark fs-6"><?= $counts['pending'] ?> oczekujące</span>
                    <span class="badge bg-success fs-6"><?= $counts['sent'] ?> wysłane</span>
                    <?php if ($counts['failed'] > 0): ?>
                    <span class="badge bg-danger fs-6"><?= $counts['failed'] ?> błędy</span>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= url('config/notifications/send') ?>" class="mb-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm w-100"
                            <?= $counts['pending'] === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-send"></i> Wyślij oczekujące (max 20)
                    </button>
                </form>
                <?php if ($counts['sent'] > 0): ?>
                <form method="post" action="<?= url('config/notifications/clear-sent') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100"
                            onclick="return confirm('Usunąć wszystkie wysłane z kolejki?')">
                        <i class="bi bi-trash"></i> Wyczyść wysłane
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: queue table -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <strong>Historia kolejki</strong>
                <!-- filter -->
                <form method="get" class="ms-auto d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                        <option value="">Wszystkie statusy</option>
                        <?php foreach ($statusLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($filters['status'] ?? '') === $val ? 'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                        <option value="">Wszystkie typy</option>
                        <?php foreach ($typeLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($filters['type'] ?? '') === $val ? 'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <?php if (empty($queueResult['data'])): ?>
                    <p class="text-muted p-3 mb-0">Kolejka jest pusta.</p>
                <?php else: ?>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Odbiorca</th>
                            <th>Temat</th>
                            <th>Typ</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($queueResult['data'] as $item): ?>
                        <tr>
                            <td class="small">
                                <?= e($item['to_name']) ?>
                                <div class="text-muted" style="font-size:.8em"><?= e($item['to_email']) ?></div>
                            </td>
                            <td class="small"><?= e($item['subject']) ?></td>
                            <td class="small">
                                <span class="badge bg-light border text-dark">
                                    <?= $typeLabels[$item['type']] ?? $item['type'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $statusColors[$item['status']] ?? 'secondary' ?>">
                                    <?= $statusLabels[$item['status']] ?? $item['status'] ?>
                                </span>
                                <?php if ($item['error']): ?>
                                    <span class="text-danger small d-block" title="<?= e($item['error']) ?>">
                                        <i class="bi bi-exclamation-circle"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?= $item['sent_at']
                                    ? format_date($item['sent_at'])
                                    : format_date($item['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($queueResult['last_page'] > 1): ?>
                <nav class="p-2">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $queueResult['last_page']; $p++): ?>
                        <li class="page-item <?= $p === $queueResult['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
