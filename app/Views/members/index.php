<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-people"></i> Zawodnicy</h2>
    <div class="d-flex gap-2">
        <a href="<?= url('members/import') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-upload"></i> Import CSV
        </a>
        <a href="<?= url('members/create') ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-plus-lg"></i> Dodaj zawodnika
        </a>
    </div>
</div>

<!-- Filters -->
<form method="get" action="<?= url('members') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Szukaj (nazwisko, imię, nr karty…)"
                       value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Wszystkie statusy</option>
                    <option value="aktywny"     <?= $filters['status']==='aktywny'     ? 'selected':'' ?>>Aktywni</option>
                    <option value="zawieszony"  <?= $filters['status']==='zawieszony'  ? 'selected':'' ?>>Zawieszeni</option>
                    <option value="wykreslony"  <?= $filters['status']==='wykreslony'  ? 'selected':'' ?>>Wykreśleni</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="member_type" class="form-select form-select-sm">
                    <option value="">Wszystkie typy</option>
                    <option value="rekreacyjny" <?= $filters['member_type']==='rekreacyjny' ? 'selected':'' ?>>Rekreacyjni</option>
                    <option value="wyczynowy"   <?= $filters['member_type']==='wyczynowy'   ? 'selected':'' ?>>Wyczynowi</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="age_category_id" class="form-select form-select-sm">
                    <option value="">Wszystkie kategorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filters['age_category_id'] == $cat['id'] ? 'selected':'' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Szukaj</button>
                <a href="<?= url('members') ?>" class="btn btn-outline-secondary btn-sm">Resetuj</a>
            </div>
        </div>
    </div>
</form>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nr</th>
                        <th>Nazwisko i imię</th>
                        <th>Typ</th>
                        <th>Kategoria</th>
                        <th>Nr karty</th>
                        <th>Status</th>
                        <th>Data wstąpienia</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $m): ?>
                    <tr>
                        <td class="text-muted small"><?= e($m['member_number']) ?></td>
                        <td>
                            <a href="<?= url('members/' . $m['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= e($m['last_name']) ?> <?= e($m['first_name']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($m['member_type'] === 'wyczynowy'): ?>
                                <span class="badge bg-danger">Wyczynowy</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Rekreacyjny</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e($m['age_category_name'] ?? '—') ?></td>
                        <td class="small text-muted"><?= e($m['card_number'] ?? '—') ?></td>
                        <td>
                            <?php
                                $statusClass = match($m['status']) {
                                    'aktywny'    => 'success',
                                    'zawieszony' => 'warning',
                                    'wykreslony' => 'danger',
                                    default      => 'secondary',
                                };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= e($m['status']) ?></span>
                        </td>
                        <td class="small"><?= format_date($m['join_date']) ?></td>
                        <td class="text-end">
                            <?php if (!empty($isSuperAdmin)): ?>
                            <a href="<?= url('admin/impersonate/member/' . $m['id']) ?>"
                               class="btn btn-outline-warning btn-sm py-0 me-1"
                               title="Portal zawodnika"
                               onclick="return confirm('Zalogować się jako <?= e(addslashes($m['last_name'] . ' ' . $m['first_name'])) ?>?')">
                                <i class="bi bi-person-fill-gear"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?= url('members/' . $m['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm py-0">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak zawodników spełniających kryteria.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($result['last_page'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $result['last_page']; $p++): ?>
            <li class="page-item <?= $p === $result['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('members?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<p class="text-muted small mt-2">Łącznie: <?= $result['total'] ?> zawodników</p>
