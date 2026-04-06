<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="h4 mb-0"><?= e($title) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <a href="?type=members" class="btn btn-sm btn-<?= ($type ?? 'members') === 'members' ? 'danger' : 'outline-secondary' ?>">Zawodnicy</a>
        <a href="?type=results" class="btn btn-sm btn-<?= ($type ?? '') === 'results' ? 'danger' : 'outline-secondary' ?>">Wyniki zawodów</a>
        <?php if (($type ?? 'members') === 'members'): ?>
        <a href="<?= url('reports/pzss?format=csv&type=members') ?>" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-file-earmark-arrow-down"></i> Pobierz CSV
        </a>
        <?php else: ?>
        <a href="?type=results&year=<?= $year ?? date('Y') ?>&format=csv" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-file-earmark-arrow-down"></i> Pobierz CSV
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (($type ?? 'members') === 'members'): ?>
<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    Lista aktywnych zawodników w formacie dla PZSS. Plik CSV zawiera: numer członkowski, dane osobowe, klasę i licencje.
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nr członk.</th>
                    <th>Nazwisko</th>
                    <th>Imię</th>
                    <th>Data ur.</th>
                    <th>Klasa</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $m): ?>
            <tr>
                <td class="font-monospace small"><?= e($m['member_number']) ?></td>
                <td><?= e($m['last_name']) ?></td>
                <td><?= e($m['first_name']) ?></td>
                <td class="small"><?= e($m['birth_date'] ?? '—') ?></td>
                <td class="small"><?= e($m['member_class_name'] ?? '—') ?></td>
                <td><span class="badge bg-success">aktywny</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak danych</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2 text-muted small">Łącznie: <?= count($data) ?> zawodników</div>

<?php else: ?>

<?php $year = $year ?? date('Y'); ?>
<div class="mb-3">
    <form class="d-flex gap-2 align-items-center">
        <input type="hidden" name="type" value="results">
        <label class="form-label mb-0">Rok:</label>
        <select name="year" class="form-select form-select-sm" style="width:100px" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nazwa zawodów</th>
                    <th>Data</th>
                    <th>Dyscyplina</th>
                    <th>Miejsce</th>
                    <th>Status</th>
                    <th>Zgłoszeń</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $c): ?>
            <tr>
                <td><?= e($c['name']) ?></td>
                <td class="small"><?= format_date($c['competition_date']) ?></td>
                <td class="small"><?= e($c['discipline_name'] ?? '—') ?></td>
                <td class="small"><?= e($c['location'] ?? '—') ?></td>
                <td><span class="badge bg-secondary"><?= e($c['status']) ?></span></td>
                <td><?= (int)$c['entry_count'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak zawodów w <?= $year ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
