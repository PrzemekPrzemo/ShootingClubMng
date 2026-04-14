<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>
<p class="text-muted">Kombinacje pozwalają uruchomić kilka dyscyplin w tym samym slocie czasowym z jednym wspólnym limitem zawodników. Przykład: <code>ppn</code> + <code>kpn</code> → wspólna zmiana.</p>

<?php if (empty($disciplines)): ?>
<div class="alert alert-warning">Brak zdefiniowanych dyscyplin. <a href="<?= url('startlist/' . $generator['id'] . '/disciplines') ?>">Dodaj dyscypliny w kroku 2.</a></div>
<?php else: ?>

<div class="row g-4">
    <!-- Current combos -->
    <div class="col-lg-8">
        <?php if (empty($combos)): ?>
        <div class="alert alert-info">Brak kombinacji — każda dyscyplina będzie harmonogramowana osobno.</div>
        <?php else: ?>
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Zdefiniowane kombinacje</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nazwa</th>
                            <th>Dyscypliny</th>
                            <th>Maks./zmiana</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($combos as $c):
                        $discIds = $c['discipline_ids'] ? array_map('intval', explode(',', $c['discipline_ids'])) : [];
                        $discNames = [];
                        foreach ($disciplines as $d) {
                            if (in_array((int)$d['id'], $discIds)) {
                                $discNames[] = '<code>' . e($d['code']) . '</code>';
                            }
                        }
                    ?>
                        <tr>
                            <td><?= e($c['name']) ?></td>
                            <td><?= implode(' + ', $discNames) ?></td>
                            <td><?= (int)$c['max_per_relay'] ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary py-0"
                                        onclick="openEditCombo(<?= htmlspecialchars(json_encode($c)) ?>, <?= htmlspecialchars(json_encode($discIds)) ?>)"
                                        title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post"
                                      action="<?= url('startlist/' . $generator['id'] . '/combos/' . $c['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć kombinację <?= e(addslashes($c['name'])) ?>?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="d-flex gap-2 mt-3">
            <a href="<?= url('startlist/' . $generator['id'] . '/disciplines') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Krok 2: Dyscypliny
            </a>
            <a href="<?= url('startlist/' . $generator['id'] . '/age-categories') ?>" class="btn btn-outline-primary btn-sm ms-auto">
                Krok 4: Kategorie wiekowe <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Add/Edit form -->
    <div class="col-lg-4">
        <div class="card" id="comboFormCard">
            <div class="card-header"><h6 class="mb-0" id="comboFormTitle">Dodaj kombinację</h6></div>
            <div class="card-body">
                <form method="post" id="comboForm"
                      action="<?= url('startlist/' . $generator['id'] . '/combos/add') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_edit_id" id="comboEditId" value="">

                    <div class="mb-2">
                        <label class="form-label form-label-sm">Nazwa kombinacji <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="cName" class="form-control form-control-sm"
                               placeholder="np. Pistolet (N+K razem)" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Maks. zawodników w zmianie <span class="text-danger">*</span></label>
                        <input type="number" name="max_per_relay" id="cMax"
                               class="form-control form-control-sm" min="1" max="200" value="10">
                        <div class="form-text">Łączny limit dla wszystkich dyscyplin w kombinacji.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Dyscypliny <span class="text-danger">*</span> <small class="text-muted">(min. 2)</small></label>
                        <?php foreach ($disciplines as $d): ?>
                        <div class="form-check">
                            <input class="form-check-input combo-disc-check" type="checkbox"
                                   name="discipline_ids[]" value="<?= (int)$d['id'] ?>"
                                   id="disc_<?= $d['id'] ?>">
                            <label class="form-check-label small" for="disc_<?= $d['id'] ?>">
                                <code><?= e($d['code']) ?></code> — <?= e($d['name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-grid gap-1">
                        <button type="submit" class="btn btn-danger btn-sm" id="comboSubmitBtn">
                            <i class="bi bi-plus-lg"></i> Dodaj kombinację
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="comboCancelBtn"
                                onclick="resetComboForm()">Anuluj edycję</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function openEditCombo(c, discIds) {
    document.getElementById('comboFormTitle').textContent = 'Edytuj kombinację';
    document.getElementById('comboSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Zapisz zmiany';
    document.getElementById('comboCancelBtn').classList.remove('d-none');
    document.getElementById('cName').value = c.name;
    document.getElementById('cMax').value  = c.max_per_relay;
    document.getElementById('comboEditId').value = c.id;

    // Reset all checkboxes, then check the ones belonging to this combo
    document.querySelectorAll('.combo-disc-check').forEach(function(cb) {
        cb.checked = discIds.includes(parseInt(cb.value));
    });

    document.getElementById('comboForm').action =
        '<?= url('startlist/' . $generator['id'] . '/combos/') ?>' + c.id + '/edit';
    document.getElementById('comboFormCard').scrollIntoView({behavior: 'smooth'});
}
function resetComboForm() {
    document.getElementById('comboFormTitle').textContent = 'Dodaj kombinację';
    document.getElementById('comboSubmitBtn').innerHTML = '<i class="bi bi-plus-lg"></i> Dodaj kombinację';
    document.getElementById('comboCancelBtn').classList.add('d-none');
    document.getElementById('comboForm').reset();
    document.getElementById('comboEditId').value = '';
    document.getElementById('comboForm').action = '<?= url('startlist/' . $generator['id'] . '/combos/add') ?>';
}
</script>
