<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/analytics') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-currency-dollar"></i> Przychody</h2>
</div>

<div class="row g-3">
    <!-- Invoice stats -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Faktury wg statusu</h6></div>
            <div class="card-body">
                <?php
                $statusColors = ['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger'];
                foreach ($invoices as $row): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="badge bg-<?= $statusColors[$row['status']] ?? 'secondary' ?>"><?= e($row['status']) ?></span>
                    <span class="fw-bold"><?= number_format((float)$row['total'], 2) ?> PLN <small class="text-muted">(<?= $row['cnt'] ?> szt.)</small></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?><p class="text-muted small mt-2">Brak faktur.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Revenue by plan -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Przychód wg planu</h6></div>
            <div class="card-body">
                <?php foreach ($byPlan as $row): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span><code><?= e($row['plan_key']) ?></code> <small class="text-muted">(<?= $row['invoices'] ?> fakt.)</small></span>
                    <span class="fw-bold"><?= number_format((float)$row['total'], 2) ?> PLN</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($byPlan)): ?><p class="text-muted small mt-2">Brak danych.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly revenue -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Przychód miesięczny (opłacone)</h6></div>
            <div class="card-body">
                <?php foreach ($monthly as $row): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="small"><?= e($row['month']) ?></span>
                    <span class="fw-bold small"><?= number_format((float)$row['total'], 2) ?> PLN</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($monthly)): ?><p class="text-muted small mt-2">Brak danych.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('admin/subscriptions/invoices') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-receipt"></i> Zarządzaj fakturami
    </a>
</div>
