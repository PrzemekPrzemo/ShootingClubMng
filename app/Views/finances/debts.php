<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('finances') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Zaległości składkowe <?= $year ?></h2>
    <form method="get" action="<?= url('finances/debts') ?>" class="ms-auto d-flex gap-2">
        <select name="year" class="form-select form-select-sm" style="width:auto">
            <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                <option value="<?= $y ?>" <?= $year == $y ? 'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Pokaż</button>
        <a href="<?= url('reports/finances?type=debts&year=' . $year) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-arrow-down"></i> CSV
        </a>
    </form>
</div>

<div class="alert alert-warning">
    <i class="bi bi-info-circle"></i>
    Poniżej widoczni są aktywni zawodnicy, którzy <strong>nie mają żadnej wpłaty</strong> tytułem składki rocznej za rok <?= $year ?>.
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nr</th>
                    <th>Zawodnik</th>
                    <th>E-mail</th>
                    <th>Telefon</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($debtors as $d): ?>
                <tr class="table-warning">
                    <td class="small text-muted"><?= e($d['member_number']) ?></td>
                    <td>
                        <a href="<?= url('members/' . $d['id']) ?>" class="fw-semibold text-decoration-none">
                            <?= e($d['last_name']) ?> <?= e($d['first_name']) ?>
                        </a>
                    </td>
                    <td class="small"><?= e($d['email'] ?? '—') ?></td>
                    <td class="small"><?= e($d['phone'] ?? '—') ?></td>
                    <td class="text-end">
                        <a href="<?= url('finances/create?member_id=' . $d['id']) ?>" class="btn btn-sm btn-success py-0">
                            <i class="bi bi-plus"></i> Zarejestruj wpłatę
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($debtors)): ?>
                <tr><td colspan="5" class="text-center text-success py-4">
                    <i class="bi bi-check-circle"></i> Brak zaległości — wszyscy aktywni zawodnicy opłacili składkę!
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-2">Zalegających: <?= count($debtors) ?></p>
