<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('calendar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($title) ?></h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? url('calendar/events/create') : url('calendar/events/' . $event['id'] . '/edit') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Tytuł <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($event['title'] ?? '') ?>" required
                       placeholder="np. Zawody Pucharu Polski w Łodzi">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Kategoria</label>
                    <select name="category_id" class="form-select" id="categorySelect">
                        <option value="">— brak kategorii —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"
                                data-color="<?= e($cat['color']) ?>"
                                data-icon="<?= e($cat['icon']) ?>"
                                <?= (int)($event['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kolor na kalendarzu</label>
                    <select name="color" class="form-select" id="colorSelect">
                        <?php foreach ($colorOpts as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($event['color'] ?? 'secondary') === $val ? 'selected' : '' ?>>
                            <?= e($lbl) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-1">
                        <span class="badge" id="colorPreview">Podgląd</span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data rozpoczęcia <span class="text-danger">*</span></label>
                    <input type="date" name="event_date" id="eventDate" class="form-control"
                           value="<?= e($event['event_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data zakończenia
                        <span class="text-muted small">(opcjonalna — dla wydarzeń wielodniowych)</span>
                    </label>
                    <input type="date" name="event_date_end" id="eventDateEnd" class="form-control"
                           value="<?= e($event['event_date_end'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Miejsce / lokalizacja</label>
                <input type="text" name="location" class="form-control"
                       value="<?= e($event['location'] ?? '') ?>"
                       placeholder="np. Strzelnica OSRiW Łódź, ul. Rogowska 71">
            </div>

            <div class="mb-3">
                <label class="form-label">Opis / notatki</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Dodatkowe informacje, uwagi organizacyjne…"><?= e($event['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Link (regulamin, strona zawodów, itp.)</label>
                <input type="url" name="url" class="form-control"
                       value="<?= e($event['url'] ?? '') ?>"
                       placeholder="https://…">
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_public" id="isPublic"
                           value="1" <?= ($event['is_public'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isPublic">
                        Widoczne w portalu zawodnika
                    </label>
                </div>
                <div class="form-text">Gdy włączone, zawodnicy zobaczą to wydarzenie w swoim kalendarzu.</div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-check-lg"></i>
                    <?= $mode === 'create' ? 'Dodaj wydarzenie' : 'Zapisz zmiany' ?>
                </button>
                <a href="<?= url('calendar') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>

<?php if ($mode === 'edit'): ?>
<div class="mt-3">
    <form method="post" action="<?= url('calendar/events/' . $event['id'] . '/delete') ?>"
          onsubmit="return confirm('Czy na pewno usunąć to wydarzenie?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-trash"></i> Usuń wydarzenie
        </button>
    </form>
</div>
<?php endif; ?>

</div>
</div>

<script>
(function () {
    var colorSelect  = document.getElementById('colorSelect');
    var colorPreview = document.getElementById('colorPreview');
    var eventDate    = document.getElementById('eventDate');
    var eventDateEnd = document.getElementById('eventDateEnd');

    // Color preview
    function updatePreview() {
        var c = colorSelect.value;
        colorPreview.className = 'badge bg-' + c + (c === 'warning' ? ' text-dark' : '');
        colorPreview.textContent = colorSelect.options[colorSelect.selectedIndex].text;
    }
    colorSelect.addEventListener('change', updatePreview);
    updatePreview();

    // Ensure end date >= start date
    eventDate.addEventListener('change', function () {
        if (eventDateEnd.value && eventDateEnd.value < eventDate.value) {
            eventDateEnd.value = eventDate.value;
        }
        eventDateEnd.min = eventDate.value;
    });
    if (eventDate.value) eventDateEnd.min = eventDate.value;
})();
</script>
