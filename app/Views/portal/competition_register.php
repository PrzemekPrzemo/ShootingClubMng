<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('portal/competitions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Zapisz się na zawody</h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-6">
    <div class="card mb-3">
        <div class="card-body">
            <h5><?= e($competition['name']) ?></h5>
            <dl class="row mb-0 small">
                <dt class="col-sm-4">Data</dt>
                <dd class="col-sm-8"><?= format_date($competition['competition_date']) ?></dd>
                <dt class="col-sm-4">Miejsce</dt>
                <dd class="col-sm-8"><?= e($competition['location'] ?? '—') ?></dd>
                <dt class="col-sm-4">Dyscyplina</dt>
                <dd class="col-sm-8"><?= e($competition['discipline_name'] ?? '—') ?></dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= url('portal/competitions/' . $competition['id'] . '/register') ?>">
                <?= csrf_field() ?>

                <?php if ($events): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Wybierz konkurencje</label>
                    <div class="list-group">
                    <?php foreach ($events as $ev): ?>
                        <label class="list-group-item">
                            <input class="form-check-input me-2" type="checkbox"
                                   name="event_ids[]" value="<?= $ev['id'] ?>"
                                   <?= in_array($ev['id'], $selectedEventIds ?? []) ? 'checked' : '' ?>>
                            <?= e($ev['name']) ?>
                            <?php if ($ev['shots_count']): ?>
                                <small class="text-muted">(<?= $ev['shots_count'] ?> strzałów)</small>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-check-lg"></i> Potwierdź zgłoszenie
                </button>
                <a href="<?= url('portal/competitions') ?>" class="btn btn-outline-secondary w-100 mt-2">Anuluj</a>
            </form>
        </div>
    </div>
</div>
</div>
