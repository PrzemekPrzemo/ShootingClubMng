<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>
<p class="text-muted">Zdefiniuj dyscypliny dla tego generatora. Kody muszą zgadzać się z kolumną <code>disciplines</code> w pliku CSV zawodników (np. <code>ppn</code>, <code>pst</code>, <code>psp</code>).</p>

<div class="row g-4">
    <!-- Current disciplines -->
    <div class="col-lg-8">
        <?php if (empty($disciplines)): ?>
        <div class="alert alert-warning">Brak dyscyplin. Dodaj przynajmniej jedną.</div>
        <?php else: ?>
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Zdefiniowane dyscypliny</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nazwa</th>
                            <th>Kod</th>
                            <th>Czas (min)</th>
                            <th>Stanowisk</th>
                            <th>Tryb płci</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($disciplines as $d): ?>
                        <tr>
                            <td class="text-muted small"><?= (int)$d['sort_order'] ?></td>
                            <td><?= e($d['name']) ?></td>
                            <td><code><?= e($d['code']) ?></code></td>
                            <td><?= (int)$d['duration_minutes'] ?></td>
                            <td><?= (int)$d['lanes_count'] ?></td>
                            <td>
                                <?php if ($d['gender_mode'] === 'separate'): ?>
                                    <span class="badge bg-info text-dark">M / K osobno</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Open</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary py-0"
                                        onclick="openEditDisc(<?= htmlspecialchars(json_encode($d)) ?>)"
                                        title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post"
                                      action="<?= url('startlist/' . $generator['id'] . '/disciplines/' . $d['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć dyscyplinę <?= e(addslashes($d['name'])) ?>?')">
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
            <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Wróć do wizarda
            </a>
            <a href="<?= url('startlist/' . $generator['id'] . '/combos') ?>" class="btn btn-outline-primary btn-sm ms-auto">
                Krok 3: Kombinacje <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Add form -->
    <div class="col-lg-4">
        <div class="card" id="disciplineFormCard">
            <div class="card-header"><h6 class="mb-0" id="formCardTitle">Dodaj dyscyplinę</h6></div>
            <div class="card-body">
                <form method="post" id="disciplineForm"
                      action="<?= url('startlist/' . $generator['id'] . '/disciplines/add') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method_override" id="formMethodOverride" value="add">
                    <input type="hidden" name="_edit_id" id="formEditId" value="">

                    <div class="mb-2">
                        <label class="form-label form-label-sm">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="fName" class="form-control form-control-sm"
                               placeholder="np. Pistolet nieograniczony" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Kod <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="fCode" class="form-control form-control-sm"
                               placeholder="ppn" pattern="[a-zA-Z0-9_\-]+" required
                               title="Tylko litery, cyfry, _ i -">
                        <div class="form-text">Musi zgadzać się z kodem w CSV.</div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm">Czas zmiany (min)</label>
                            <input type="number" name="duration_minutes" id="fDuration"
                                   class="form-control form-control-sm" min="1" max="300" value="45">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm">Stanowisk</label>
                            <input type="number" name="lanes_count" id="fLanes"
                                   class="form-control form-control-sm" min="1" max="100" value="10">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Tryb płci</label>
                        <select name="gender_mode" id="fGender" class="form-select form-select-sm">
                            <option value="open">Open (mieszany)</option>
                            <option value="separate">M i K osobno</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Kolejność (sort)</label>
                        <input type="number" name="sort_order" id="fSort"
                               class="form-control form-control-sm" min="0" value="<?= count($disciplines) ?>">
                    </div>
                    <div class="d-grid gap-1">
                        <button type="submit" class="btn btn-danger btn-sm" id="formSubmitBtn">
                            <i class="bi bi-plus-lg"></i> Dodaj
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="formCancelBtn"
                                onclick="resetForm()">Anuluj edycję</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit modal via JS (reuses the same form) -->
<script>
function openEditDisc(d) {
    document.getElementById('formCardTitle').textContent = 'Edytuj dyscyplinę';
    document.getElementById('formSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Zapisz zmiany';
    document.getElementById('formCancelBtn').classList.remove('d-none');
    document.getElementById('fName').value     = d.name;
    document.getElementById('fCode').value     = d.code;
    document.getElementById('fDuration').value = d.duration_minutes;
    document.getElementById('fLanes').value    = d.lanes_count;
    document.getElementById('fGender').value   = d.gender_mode;
    document.getElementById('fSort').value     = d.sort_order;
    document.getElementById('formEditId').value = d.id;
    document.getElementById('disciplineForm').action =
        '<?= url('startlist/' . $generator['id'] . '/disciplines/') ?>' + d.id + '/edit';
    document.getElementById('disciplineFormCard').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
    document.getElementById('formCardTitle').textContent = 'Dodaj dyscyplinę';
    document.getElementById('formSubmitBtn').innerHTML = '<i class="bi bi-plus-lg"></i> Dodaj';
    document.getElementById('formCancelBtn').classList.add('d-none');
    document.getElementById('disciplineForm').reset();
    document.getElementById('formEditId').value = '';
    document.getElementById('disciplineForm').action = '<?= url('startlist/' . $generator['id'] . '/disciplines/add') ?>';
}
</script>
