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
                            <th class="text-end">Broń własna</th>
                            <th class="text-end">Broń klubowa</th>
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
                            <td class="text-end">
                                <?php if (isset($ev['fee_own_weapon']) && $ev['fee_own_weapon'] !== null): ?>
                                    <span class="badge bg-info text-dark"><?= format_money($ev['fee_own_weapon']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (isset($ev['fee_club_weapon']) && $ev['fee_club_weapon'] !== null): ?>
                                    <span class="badge bg-secondary"><?= format_money($ev['fee_club_weapon']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
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
                                   class="btn btn-xs btn-outline-secondary py-0 px-2"
                                   title="Lista startowa (A4)">
                                    <i class="bi bi-printer"></i> Lista
                                </a>
                                <a href="<?= url('competitions/' . $competition['id'] . '/scorecards') ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-2"
                                   title="Generuj metryczki A5 (wybór zawodników i konkurencji)">
                                    <i class="bi bi-file-person"></i> Metryczki A5
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

    <!-- Formularz dodawania + szablony -->
    <div class="col-md-4">

        <!-- Szablony (jeśli są zdefiniowane) -->
        <?php if (!empty($templateGroups)): ?>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <strong><i class="bi bi-lightning"></i> Wstaw z szablonu</strong>
                <span class="badge bg-info text-dark ms-1">szybkie dodawanie</span>
            </div>
            <div class="card-body p-2">
                <?php foreach ($templateGroups as $group): ?>
                <div class="mb-2">
                    <div class="small fw-bold text-muted mb-1">
                        <i class="bi bi-bullseye"></i> <?= e($group['discipline']['name']) ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($group['templates'] as $tpl): ?>
                        <button type="button"
                                class="btn btn-xs btn-outline-secondary py-0 px-2 tpl-btn"
                                data-name="<?= e($tpl['name']) ?>"
                                data-shots="<?= e($tpl['shots_count'] ?? '') ?>"
                                data-scoring="<?= e($tpl['scoring_type']) ?>"
                                title="<?= e($tpl['description'] ?? $tpl['name']) ?>">
                            <?= e($tpl['name']) ?>
                            <?php if ($tpl['shots_count']): ?>
                                <span class="text-muted">(<?= $tpl['shots_count'] ?>)</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formularz ręczny -->
        <div class="card">
            <div class="card-header"><strong>Dodaj konkurencję</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/events/add') ?>" id="addEventForm">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Nazwa <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="evtName" class="form-control form-control-sm" required
                               placeholder="np. 10m Pistolet Pneumatyczny">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Liczba strzałów</label>
                        <input type="number" name="shots_count" id="evtShots" class="form-control form-control-sm"
                               min="1" max="255" placeholder="np. 60">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Typ punktacji</label>
                        <select name="scoring_type" id="evtScoring" class="form-select form-select-sm">
                            <option value="decimal">Dziesiętna (np. 10.9)</option>
                            <option value="integer">Całkowita (np. 98)</option>
                            <option value="hit_miss">Trafiony / Chybiony</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label">Cena — broń własna (zł)</label>
                            <input type="number" name="fee_own_weapon" id="evtFeeOwn"
                                   class="form-control form-control-sm" min="0" step="0.01" placeholder="np. 30.00">
                        </div>
                        <div class="col">
                            <label class="form-label">Cena — broń klubowa (zł)</label>
                            <input type="number" name="fee_club_weapon" id="evtFeeClub"
                                   class="form-control form-control-sm" min="0" step="0.01" placeholder="np. 40.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kolejność</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm"
                               min="0" value="<?= count($events) ?>">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus-lg"></i> Dodaj konkurencję
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
document.querySelectorAll('.tpl-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('evtName').value    = btn.dataset.name;
        document.getElementById('evtShots').value   = btn.dataset.shots;
        const sel = document.getElementById('evtScoring');
        for (let o of sel.options) o.selected = (o.value === btn.dataset.scoring);
        document.getElementById('evtFeeOwn').value  = '';
        document.getElementById('evtFeeClub').value = '';
        document.getElementById('evtName').focus();
        document.getElementById('addEventForm').scrollIntoView({behavior:'smooth', block:'nearest'});
        // Highlight the form briefly
        const card = document.getElementById('addEventForm').closest('.card');
        card.style.outline = '2px solid #0d6efd';
        setTimeout(() => card.style.outline = '', 1200);
    });
});
</script>
