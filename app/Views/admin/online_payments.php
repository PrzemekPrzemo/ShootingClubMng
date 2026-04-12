<?php
$statusLabels = [
    'verified'  => ['label' => 'Zweryfikowana', 'badge' => 'bg-success'],
    'pending'   => ['label' => 'Oczekująca',    'badge' => 'bg-warning text-dark'],
    'failed'    => ['label' => 'Nieudana',       'badge' => 'bg-danger'],
    'cancelled' => ['label' => 'Anulowana',      'badge' => 'bg-secondary'],
];
$typeLabels = [
    'fee'               => 'Składka',
    'competition_entry' => 'Opłata startowa',
    'other'             => 'Inna',
];

// Global totals
$totalVerified = $stats['verified']['total'] ?? 0;
$totalPending  = $stats['pending']['total']  ?? 0;
$cntVerified   = (int)($stats['verified']['cnt'] ?? 0);
$cntPending    = (int)($stats['pending']['cnt']  ?? 0);
$cntFailed     = (int)($stats['failed']['cnt']   ?? 0);
?>

<h2 class="h4 mb-4"><i class="bi bi-credit-card-2-front me-2"></i>Płatności online — Przelewy24</h2>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="fw-bold" style="font-size:1.6rem;color:#22c55e"><?= number_format((float)$totalVerified, 2, ',', ' ') ?> zł</div>
                <div class="text-muted small mt-1"><i class="bi bi-check-circle me-1"></i>Zweryfikowane (<?= $cntVerified ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="fw-bold" style="font-size:1.6rem;color:#f59e0b"><?= number_format((float)$totalPending, 2, ',', ' ') ?> zł</div>
                <div class="text-muted small mt-1"><i class="bi bi-hourglass-split me-1"></i>Oczekujące (<?= $cntPending ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="fw-bold" style="font-size:1.6rem;color:#ef4444"><?= $cntFailed ?></div>
                <div class="text-muted small mt-1"><i class="bi bi-x-circle me-1"></i>Nieudane</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="fw-bold" style="font-size:1.6rem"><?= $total ?></div>
                <div class="text-muted small mt-1"><i class="bi bi-list-ul me-1"></i>Łącznie transakcji</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="get" class="d-flex flex-wrap gap-2 mb-4 align-items-end">
    <div>
        <label class="form-label small text-muted mb-1">Klub</label>
        <select name="club_id" class="form-select form-select-sm" style="min-width:180px">
            <option value="">Wszystkie kluby</option>
            <?php foreach ($clubs as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $clubFilter === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">Wszystkie</option>
            <?php foreach ($statusLabels as $key => $lbl): ?>
                <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                    <?= $lbl['label'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-funnel"></i> Filtruj
        </button>
        <a href="<?= url('admin/online-payments') ?>" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
    </div>
</form>

<!-- Table -->
<?php if ($payments): ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Klub</th>
                        <th>Zawodnik</th>
                        <th>Typ</th>
                        <th>Kwota</th>
                        <th>P24 Order</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $p):
                    $sl = $statusLabels[$p['status']] ?? ['label' => $p['status'], 'badge' => 'bg-secondary'];
                ?>
                    <tr>
                        <td class="ps-3 text-muted small font-monospace">#<?= $p['id'] ?></td>
                        <td class="small"><?= e($p['club_name']) ?></td>
                        <td>
                            <span class="small"><?= e($p['member_name']) ?></span>
                            <?php if ($p['payer_email']): ?>
                            <div class="text-muted" style="font-size:.72rem"><?= e($p['payer_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= $typeLabels[$p['payment_type']] ?? e($p['payment_type']) ?></td>
                        <td class="fw-semibold"><?= number_format((float)$p['amount'], 2, ',', ' ') ?> zł</td>
                        <td class="font-monospace small text-muted">
                            <?= $p['p24_order_id'] ? '#' . $p['p24_order_id'] : '—' ?>
                        </td>
                        <td>
                            <span class="badge <?= $sl['badge'] ?>"><?= $sl['label'] ?></span>
                        </td>
                        <td class="small text-muted">
                            <?= date('d.m.Y H:i', strtotime($p['created_at'])) ?>
                        </td>
                        <td class="text-end pe-3">
                            <?php if ($p['status'] === 'pending'): ?>
                            <form method="post" action="<?= url('admin/online-payments/' . $p['id'] . '/cancel') ?>"
                                  onsubmit="return confirm('Anulować tę transakcję?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2 small">
                                    <i class="bi bi-x"></i> Anuluj
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($p['description'])): ?>
                    <tr class="table-secondary">
                        <td colspan="9" class="ps-3 py-1">
                            <span class="text-muted small"><i class="bi bi-chat-left-text me-1"></i><?= e($p['description']) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url("admin/online-payments?page=$i&club_id=$clubFilter&status=$statusFilter") ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php else: ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak transakcji spełniających kryteria.
</div>
<?php endif; ?>

<!-- Config hint for super admin -->
<div class="alert alert-secondary small mt-4">
    <i class="bi bi-gear me-1"></i>
    Aby włączyć Przelewy24 dla danego klubu, przejdź do
    <a href="<?= url('admin/clubs') ?>">Zarządzanie klubami</a>
    i skonfiguruj sekcję <strong>Przelewy24</strong> w edycji klubu.
</div>
