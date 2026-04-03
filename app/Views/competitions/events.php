<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0">Konkurencje — <?= e($competition['name']) ?></h2>
    <span class="badge bg-secondary ms-2"><?= format_date($competition['competition_date']) ?></span>
</div>

<div class="row g-3">
    <!-- Lista konkurencji -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Kol.</th>
                            <th>Nazwa konkurencji</th>
                            <th>Strzały</th>
                            <th>Punktacja</th>
                            <th>Wyniki</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td class="text-muted text-center"><?= $ev['sort_order'] ?></td>
                            <td><strong><?= e($ev['name']) ?></strong></td>
                            <td class="text-center"><?= $ev['shots_count'] ?? '—' ?></td>
                            <td>
                                <?php $stMap = ['decimal'=>'Dziesiętna','integer'=>'Całkowita','hit_miss'=>'Trafiony/Chybiony']; ?>
                                <span class="badge bg-light text-dark border"><?= $stMap[$ev['scoring_type']] ?? $ev['scoring_type'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $ev['result_count'] > 0 ? 'success' : 'secondary' ?>">
                                    <?= $ev['result_count'] ?>
                                </span>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $ev['id'] . '/results') ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-2">
                                    <i class="bi bi-trophy"></i> Wyniki
                                </a>
                                <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $ev['id'] . '/startcard') ?>"
                                   target="_blank"
                                   class="btn btn-xs btn-outline-secondary py-0 px-2">
                                    <i class="bi bi-printer"></i> Metryczka
                                </a>
                                <form method="post"
                                      action="<?= url('competitions/' . $competition['id'] . '/events/' . $ev['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć konkurencję i wszystkie jej wyniki?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            Brak konkurencji. Dodaj pierwszą po prawej.
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formularz dodawania -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Dodaj konkurencję</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/events/add') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required
                               placeholder="np. 10m Pistolet Pneumatyczny">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Liczba strzałów</label>
                        <input type="number" name="shots_count" class="form-control form-control-sm"
                               min="1" max="255" placeholder="np. 60">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Typ punktacji</label>
                        <select name="scoring_type" class="form-select form-select-sm">
                            <option value="decimal">Dziesiętna (np. 10.9)</option>
                            <option value="integer">Całkowita (np. 98)</option>
                            <option value="hit_miss">Trafiony / Chybiony</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kolejność</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm"
                               min="0" value="0">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus-lg"></i> Dodaj konkurencję
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <strong>Popularne konkurencje PZSS/ISSF:</strong><br>
            • 10m Pistolet Pneumatyczny (60 strzałów)<br>
            • 10m Karabin Pneumatyczny (60 strzałów)<br>
            • 25m Pistolet Sportowy (60 strzałów)<br>
            • 25m Pistolet Szybkostrzelny (60 strzałów)<br>
            • 50m Karabin Leżąc (60 strzałów)<br>
            • 50m Karabin 3×40 (120 strzałów)<br>
            • Trap (75 rzutków)<br>
            • Skeet (100 rzutków)
        </div>
    </div>
</div>
