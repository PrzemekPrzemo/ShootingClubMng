<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-trophy"></i> Zawody</h2>
    <a href="<?= url('competitions/create') ?>" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Utwórz zawody
    </a>
</div>

<form method="get" action="<?= url('competitions') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Szukaj nazwy…"
                       value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Wszystkie statusy</option>
                    <?php foreach (['planowane','otwarte','zamkniete','zakonczone'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status']===$s ? 'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y')+1; $y >= date('Y')-3; $y--): ?>
                        <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Szukaj</button>
                <a href="<?= url('competitions') ?>" class="btn btn-outline-secondary btn-sm">Resetuj</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Nazwa</th>
                        <th>Dyscyplina</th>
                        <th>Miejsce</th>
                        <th>Status</th>
                        <th>Zgłoszeń</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $c): ?>
                    <?php
                    $sc = match($c['status']) {
                        'planowane'  => 'secondary',
                        'otwarte'    => 'success',
                        'zamkniete'  => 'warning',
                        'zakonczone' => 'dark',
                        default      => 'secondary',
                    };
                    ?>
                    <tr>
                        <td class="small"><?= format_date($c['competition_date']) ?></td>
                        <td>
                            <a href="<?= url('competitions/' . $c['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= e($c['name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= e($c['discipline_name'] ?? '—') ?></td>
                        <td class="small text-muted"><?= e($c['location'] ?? '—') ?></td>
                        <td><span class="badge bg-<?= $sc ?>"><?= e($c['status']) ?></span></td>
                        <td class="small">
                            <?= $c['entry_count'] ?>
                            <?php if ($c['max_entries']): ?><span class="text-muted">/ <?= $c['max_entries'] ?></span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('competitions/' . $c['id'] . '/entries') ?>" class="btn btn-outline-primary btn-sm py-0" title="Zgłoszenia">
                                <i class="bi bi-person-plus"></i>
                            </a>
                            <a href="<?= url('competitions/' . $c['id'] . '/results') ?>" class="btn btn-outline-success btn-sm py-0" title="Wyniki">
                                <i class="bi bi-list-ol"></i>
                            </a>
                            <a href="<?= url('competitions/' . $c['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm py-0">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Brak zawodów.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="text-muted small mt-2">Łącznie: <?= $result['total'] ?> zawodów</p>
