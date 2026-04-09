<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('admin/subscriptions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-receipt"></i> Faktury i rozliczenia</h2>
</div>

<!-- Issue new invoice -->
<div class="card mb-4" style="max-width:700px">
    <div class="card-header"><h6 class="mb-0">Wystaw fakturę</h6></div>
    <div class="card-body">
        <form method="post" action="<?= url('admin/subscriptions/invoices') ?>" class="row g-2">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label class="form-label small">Klub</label>
                <select name="club_id" class="form-select form-select-sm" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Plan</label>
                <select name="plan_key" class="form-select form-select-sm" required>
                    <?php foreach ($plans as $key => $plan): ?>
                        <option value="<?= e($key) ?>"><?= e($plan['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Kwota (PLN)</label>
                <input type="number" name="amount_pln" class="form-control form-control-sm" min="0" step="0.01" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Okres od</label>
                <input type="date" name="period_from" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Okres do</label>
                <input type="date" name="period_to" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Uwagi</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opcjonalne">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Wystaw fakturę
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Invoices table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nr faktury</th>
                    <th>Klub</th>
                    <th>Plan</th>
                    <th>Kwota</th>
                    <th>Okres</th>
                    <th>Status</th>
                    <th>Wystawiona</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
                <?php
                $statusColors = ['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger'];
                ?>
                <tr>
                    <td><code><?= e($inv['invoice_number'] ?? '—') ?></code></td>
                    <td><?= e($inv['club_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($inv['plan_key']) ?></span></td>
                    <td class="fw-bold"><?= number_format((float)$inv['amount_pln'], 2) ?> PLN</td>
                    <td class="small"><?= e($inv['period_from']) ?> — <?= e($inv['period_to']) ?></td>
                    <td>
                        <span class="badge bg-<?= $statusColors[$inv['status']] ?? 'secondary' ?>">
                            <?= e($inv['status']) ?>
                        </span>
                    </td>
                    <td class="small"><?= $inv['issued_at'] ? date('d.m.Y', strtotime($inv['issued_at'])) : '—' ?></td>
                    <td>
                        <?php if ($inv['status'] === 'issued'): ?>
                        <form method="post" action="<?= url('admin/subscriptions/invoices/' . $inv['id'] . '/paid') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-xs btn-success py-0 px-1" title="Oznacz jako opłaconą">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="8" class="text-muted text-center py-3">Brak faktur.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
