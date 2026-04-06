<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('config') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-tags"></i> Kategorie wydarzeń kalendarza</h2>
</div>

<div class="row g-4">

    <!-- Category list -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-semibold">Istniejące kategorie</div>
            <div class="card-body p-0">
                <?php if (empty($categories)): ?>
                <p class="text-muted p-3 mb-0">Brak kategorii. Dodaj pierwszą po prawej stronie.</p>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kolejność</th>
                            <th>Nazwa</th>
                            <th>Kolor / ikona</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="text-muted small"><?= (int)$cat['sort_order'] ?></td>
                            <td>
                                <span class="badge bg-<?= e($cat['color']) ?> me-1">
                                    <i class="bi bi-<?= e($cat['icon']) ?>"></i>
                                </span>
                                <?= e($cat['name']) ?>
                            </td>
                            <td class="small text-muted">
                                <?= e($cat['color']) ?> / <?= e($cat['icon']) ?>
                            </td>
                            <td>
                                <?php if ($cat['is_active']): ?>
                                <span class="badge bg-success">Aktywna</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Nieaktywna</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="editCategory(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="<?= url('config/calendar-categories/' . (int)$cat['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć kategorię? Powiązane zdarzenia stracą przypisanie.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add / Edit form -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold" id="formHeading">Dodaj kategorię</div>
            <div class="card-body">
                <form method="post" action="<?= url('config/calendar-categories/save') ?>" id="catForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="catId" value="">

                    <div class="mb-3">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control"
                               placeholder="np. Zawody zewnętrzne" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kolor (Bootstrap)</label>
                        <select name="color" id="catColor" class="form-select">
                            <?php foreach ($colorOpts as $val => $lbl): ?>
                            <option value="<?= $val ?>"><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-1">
                            <span class="badge" id="colorPreview">Podgląd</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ikona Bootstrap Icons
                            <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener"
                               class="small text-muted ms-1">(lista ikon)</a>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconPreview"><i class="bi bi-calendar-event"></i></span>
                            <input type="text" name="icon" id="catIcon" class="form-control"
                                   placeholder="np. trophy, people, geo-alt" value="calendar-event">
                        </div>
                        <div class="form-text">Wpisz nazwę ikony bez przedrostka <code>bi-</code></div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Kolejność sortowania</label>
                            <input type="number" name="sort_order" id="catSortOrder" class="form-control"
                                   value="0" min="0" max="255">
                        </div>
                        <div class="col-auto d-flex align-items-end pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="catIsActive"
                                       value="1" checked>
                                <label class="form-check-label" for="catIsActive">Aktywna</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger flex-fill">
                            <i class="bi bi-check-lg"></i> <span id="submitLabel">Dodaj</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var colorSel    = document.getElementById('catColor');
    var colorPrev   = document.getElementById('colorPreview');
    var iconInput   = document.getElementById('catIcon');
    var iconPrev    = document.getElementById('iconPreview');

    function updateColorPreview() {
        var c = colorSel.value;
        colorPrev.className = 'badge bg-' + c + (c === 'warning' ? ' text-dark' : '');
        colorPrev.textContent = colorSel.options[colorSel.selectedIndex].text;
    }
    colorSel.addEventListener('change', updateColorPreview);
    updateColorPreview();

    function updateIconPreview() {
        var icon = iconInput.value.trim() || 'calendar-event';
        iconPrev.innerHTML = '<i class="bi bi-' + icon + '"></i>';
    }
    iconInput.addEventListener('input', updateIconPreview);
    updateIconPreview();

    window.editCategory = function (cat) {
        document.getElementById('formHeading').textContent = 'Edytuj kategorię';
        document.getElementById('submitLabel').textContent = 'Zapisz';
        document.getElementById('catId').value       = cat.id;
        document.getElementById('catName').value     = cat.name;
        document.getElementById('catSortOrder').value= cat.sort_order;
        document.getElementById('catIsActive').checked = cat.is_active == 1;
        document.getElementById('catIcon').value = cat.icon;
        updateIconPreview();
        // Set color select
        for (var i = 0; i < colorSel.options.length; i++) {
            if (colorSel.options[i].value === cat.color) {
                colorSel.selectedIndex = i;
                break;
            }
        }
        updateColorPreview();
        document.getElementById('catForm').scrollIntoView({behavior: 'smooth'});
    };

    window.resetForm = function () {
        document.getElementById('formHeading').textContent = 'Dodaj kategorię';
        document.getElementById('submitLabel').textContent = 'Dodaj';
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catSortOrder').value = '0';
        document.getElementById('catIsActive').checked = true;
        document.getElementById('catIcon').value = 'calendar-event';
        colorSel.selectedIndex = 0;
        updateColorPreview();
        updateIconPreview();
    };
})();
</script>
