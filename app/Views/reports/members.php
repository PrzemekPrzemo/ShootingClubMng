<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="h4 mb-0">Raport — Zawodnicy</h2>
    </div>
    <a href="<?= url('reports/members?format=csv&' . http_build_query($filters)) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-arrow-down"></i> Eksport CSV
    </a>
</div>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Szukaj…" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Wszystkie statusy</option>
                    <option value="aktywny" <?= $filters['status']==='aktywny' ? 'selected':'' ?>>Aktywni</option>
                    <option value="zawieszony" <?= $filters['status']==='zawieszony' ? 'selected':'' ?>>Zawieszeni</option>
                    <option value="wykreslony" <?= $filters['status']==='wykreslony' ? 'selected':'' ?>>Wykreśleni</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="member_type" class="form-select form-select-sm">
                    <option value="">Wszystkie typy</option>
                    <option value="rekreacyjny" <?= $filters['member_type']==='rekreacyjny' ? 'selected':'' ?>>Rekreacyjni</option>
                    <option value="wyczynowy" <?= $filters['member_type']==='wyczynowy' ? 'selected':'' ?>>Wyczynowi</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm">Filtruj</button>
            </div>
        </div>
    </div>
</form>

<p class="text-muted">Znaleziono: <strong><?= $result['total'] ?></strong> zawodników</p>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr><th>Nr</th><th>Nazwisko i imię</th><th>Typ</th><th>Status</th><th>Kategoria</th><th>Nr karty</th><th>Wstąpił</th><th>E-mail</th></tr>
                </thead>
                <tbody>
                <?php foreach ($result['data'] as $m): ?>
                    <tr>
                        <td class="small"><?= e($m['member_number']) ?></td>
                        <td><a href="<?= url('members/' . $m['id']) ?>"><?= e($m['last_name']) ?> <?= e($m['first_name']) ?></a></td>
                        <td><span class="badge bg-<?= $m['member_type']==='wyczynowy' ? 'danger':'secondary' ?>"><?= e($m['member_type']) ?></span></td>
                        <td><?php $sc = match($m['status']) { 'aktywny'=>'success','zawieszony'=>'warning','wykreslony'=>'danger',default=>'secondary' };?><span class="badge bg-<?= $sc ?>"><?= e($m['status']) ?></span></td>
                        <td class="small"><?= e($m['age_category_name'] ?? '—') ?></td>
                        <td class="small"><?= e($m['card_number'] ?? '—') ?></td>
                        <td class="small"><?= format_date($m['join_date']) ?></td>
                        <td class="small"><?= e($m['email'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
