<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('equipment') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><i class="bi bi-archive"></i> Amunicja</h2>
</div>

<div class="row g-3 mb-3">
    <!-- Summary cards -->
    <?php if (!empty($summary)): ?>
    <?php foreach ($summary as $row): ?>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center border-<?= (int)$row['balance'] <= 0 ? 'danger' : 'success' ?>">
            <div class="card-body py-2">
                <div class="fw-bold fs-4 <?= (int)$row['balance'] <= 0 ? 'text-danger' : 'text-success' ?>">
                    <?= (int)$row['balance'] ?>
                </div>
                <div class="small text-muted"><?= e($row['caliber']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="col-12">
        <div class="alert alert-info mb-0">Brak zapisów amunicji.</div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Add movement form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Zarejestruj ruch</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('equipment/ammo') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Kaliber <span class="text-danger">*</span></label>
                        <?php if (!empty($calibers)): ?>
                        <input type="text" name="caliber" class="form-control form-control-sm"
                               list="caliber-list" required
                               placeholder="np. 9mm Luger">
                        <datalist id="caliber-list">
                            <?php foreach ($calibers as $c): ?>
                            <option value="<?= e($c) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <?php else: ?>
                        <input type="text" name="caliber" class="form-control form-control-sm" required
                               placeholder="np. 9mm Luger">
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Typ naboju</label>
                        <input type="text" name="type" class="form-control form-control-sm"
                               placeholder="np. FMJ, HP (opcjonalnie)">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Ilość <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control form-control-sm"
                               required placeholder="+ przyjęcie / − wydanie">
                        <div class="form-text">Wpisz liczbę dodatnią (przyjęcie) lub ujemną (wydanie).</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Data</label>
                        <input type="date" name="recorded_at" class="form-control form-control-sm"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Uwagi</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"
                                  placeholder="Źródło, odbiorca…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg"></i> Zapisz ruch
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- History table -->
    <div class="col-md-8">
        <!-- Filters -->
        <form method="get" class="row g-2 mb-2">
            <div class="col-auto">
                <select name="caliber" class="form-select form-select-sm">
                    <option value="">Wszystkie kalibry</option>
                    <?php foreach ($calibers as $c): ?>
                    <option value="<?= e($c) ?>" <?= $filters['caliber'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= e($filters['date_from']) ?>" placeholder="Od daty">
            </div>
            <div class="col-auto">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= e($filters['date_to']) ?>" placeholder="Do daty">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtruj</button>
                <a href="<?= url('equipment/ammo') ?>" class="btn btn-sm btn-link">Wyczyść</a>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($result['data'])): ?>
                    <p class="text-muted p-3 mb-0">Brak zapisów.</p>
                <?php else: ?>
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Kaliber</th>
                            <th>Typ</th>
                            <th class="text-end">Ilość</th>
                            <th>Uwagi</th>
                            <th>Zapisał</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($result['data'] as $row): ?>
                        <tr>
                            <td class="small"><?= format_date($row['recorded_at']) ?></td>
                            <td><?= e($row['caliber']) ?></td>
                            <td class="small text-muted"><?= e($row['type'] ?? '—') ?></td>
                            <td class="text-end fw-bold <?= (int)$row['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                <?= (int)$row['quantity'] > 0 ? '+' : '' ?><?= (int)$row['quantity'] ?>
                            </td>
                            <td class="small text-muted"><?= e($row['notes'] ?? '') ?></td>
                            <td class="small text-muted"><?= e($row['recorded_by_name'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($result['last_page'] > 1): ?>
                <nav class="p-2">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $result['last_page']; $p++): ?>
                        <li class="page-item <?= $p === $result['current_page'] ? 'active' : '' ?>">
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
