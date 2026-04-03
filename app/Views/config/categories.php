<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-gear"></i> Kategorie wiekowe</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-auto"><a href="<?= url('config') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-sliders"></i> Ustawienia</a></div>
    <div class="col-auto"><a href="<?= url('config/categories') ?>" class="btn btn-outline-primary btn-sm active"><i class="bi bi-tags"></i> Kategorie wiekowe</a></div>
    <div class="col-auto"><a href="<?= url('config/users') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people"></i> Użytkownicy</a></div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Istniejące kategorie</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-dark"><tr><th>Nazwa</th><th>Wiek od</th><th>do</th><th>Kolejność</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= e($cat['name']) ?></td>
                            <td><?= $cat['age_from'] ?></td>
                            <td><?= $cat['age_to'] ?></td>
                            <td><?= $cat['sort_order'] ?></td>
                            <td class="text-end">
                                <form method="post" action="<?= url('config/categories/' . $cat['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Usunąć kategorię?')">
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
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong>Dodaj kategorię</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('config/categories') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="0">
                    <div class="mb-2">
                        <label class="form-label">Nazwa</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Wiek od</label>
                            <input type="number" name="age_from" min="0" max="120" class="form-control form-control-sm" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Wiek do</label>
                            <input type="number" name="age_to" min="0" max="255" class="form-control form-control-sm" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Kolejność</label>
                            <input type="number" name="sort_order" min="0" value="0" class="form-control form-control-sm">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm w-100">Dodaj</button>
                </form>
            </div>
        </div>
    </div>
</div>
