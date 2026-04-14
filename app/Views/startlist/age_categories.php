<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('startlist/' . $generator['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>
<p class="text-muted">Kategorie wiekowe są opcjonalne. Jeśli nie zdefiniujesz żadnej, wszyscy zawodnicy startują w jednej grupie (open). Algorytm automatycznie przypisuje zawodników do kategorii na podstawie daty urodzenia.</p>

<div class="row g-4">
    <!-- Current categories -->
    <div class="col-lg-8">
        <?php if (empty($categories)): ?>
        <div class="alert alert-info">Brak kategorii wiekowych — start open / bez podziału wiekowego.</div>
        <?php else: ?>
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Zdefiniowane kategorie wiekowe</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nazwa</th>
                            <th>Wiek od</th>
                            <th>Wiek do</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="text-muted small"><?= (int)$cat['sort_order'] ?></td>
                            <td><?= e($cat['name']) ?></td>
                            <td><?= (int)$cat['age_from'] ?></td>
                            <td><?= $cat['age_to'] >= 99 ? '∞' : (int)$cat['age_to'] ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary py-0"
                                        onclick="openEditCat(<?= htmlspecialchars(json_encode($cat)) ?>)"
                                        title="Edytuj">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post"
                                      action="<?= url('startlist/' . $generator['id'] . '/age-categories/' . $cat['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć kategorię <?= e(addslashes($cat['name'])) ?>?')">
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
            <a href="<?= url('startlist/' . $generator['id'] . '/combos') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Krok 3: Kombinacje
            </a>
            <a href="<?= url('startlist/' . $generator['id'] . '/import') ?>" class="btn btn-outline-primary btn-sm ms-auto">
                Krok 5: Import zawodników <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Add/Edit form -->
    <div class="col-lg-4">
        <div class="card" id="catFormCard">
            <div class="card-header"><h6 class="mb-0" id="catFormTitle">Dodaj kategorię wiekową</h6></div>
            <div class="card-body">
                <form method="post" id="catForm"
                      action="<?= url('startlist/' . $generator['id'] . '/age-categories/add') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_edit_id" id="catEditId" value="">

                    <div class="mb-2">
                        <label class="form-label form-label-sm">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control form-control-sm"
                               placeholder="np. Junior, Senior, Masters" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm">Wiek od (lat)</label>
                            <input type="number" name="age_from" id="catFrom"
                                   class="form-control form-control-sm" min="0" max="99" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm">Wiek do (lat)</label>
                            <input type="number" name="age_to" id="catTo"
                                   class="form-control form-control-sm" min="0" max="150" value="99">
                            <div class="form-text">99 = bez górnego limitu</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Kolejność (sort)</label>
                        <input type="number" name="sort_order" id="catSort"
                               class="form-control form-control-sm" min="0" value="<?= count($categories) ?>">
                    </div>
                    <div class="d-grid gap-1">
                        <button type="submit" class="btn btn-danger btn-sm" id="catSubmitBtn">
                            <i class="bi bi-plus-lg"></i> Dodaj
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="catCancelBtn"
                                onclick="resetCatForm()">Anuluj edycję</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-light border mt-3 small">
            <i class="bi bi-info-circle text-info"></i>
            <strong>Jak działa przypisanie?</strong><br>
            System oblicza wiek zawodnika w roku bieżących zawodów i przypisuje go do pierwszej pasującej kategorii (wg kolejności sort). Jeśli żadna nie pasuje — zawodnik startuje bez kategorii (open).
        </div>
    </div>
</div>

<script>
function openEditCat(c) {
    document.getElementById('catFormTitle').textContent = 'Edytuj kategorię';
    document.getElementById('catSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Zapisz zmiany';
    document.getElementById('catCancelBtn').classList.remove('d-none');
    document.getElementById('catName').value = c.name;
    document.getElementById('catFrom').value = c.age_from;
    document.getElementById('catTo').value   = c.age_to;
    document.getElementById('catSort').value = c.sort_order;
    document.getElementById('catEditId').value = c.id;
    document.getElementById('catForm').action =
        '<?= url('startlist/' . $generator['id'] . '/age-categories/') ?>' + c.id + '/edit';
    document.getElementById('catFormCard').scrollIntoView({behavior: 'smooth'});
}
function resetCatForm() {
    document.getElementById('catFormTitle').textContent = 'Dodaj kategorię wiekową';
    document.getElementById('catSubmitBtn').innerHTML = '<i class="bi bi-plus-lg"></i> Dodaj';
    document.getElementById('catCancelBtn').classList.add('d-none');
    document.getElementById('catForm').reset();
    document.getElementById('catEditId').value = '';
    document.getElementById('catForm').action = '<?= url('startlist/' . $generator['id'] . '/age-categories/add') ?>';
}
</script>
