<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Zgłoszenia — <?= e($competition['name']) ?></h2>
    <span class="badge bg-secondary ms-2"><?= format_date($competition['competition_date']) ?></span>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Zawodnik</th>
                            <th>Klasa</th>
                            <th>Grupa</th>
                            <th>Status</th>
                            <th>Opłata</th>
                            <th>Zgłoszono</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entries as $i => $e): ?>
                        <?php $sc = match($e['status']) { 'potwierdzony'=>'success','wycofany'=>'secondary','zdyskwalifikowany'=>'danger',default=>'warning' }; ?>
                        <tr>
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td><a href="<?= url('members/' . $e['member_id']) ?>"><?= e($e['last_name']) ?> <?= e($e['first_name']) ?></a><br>
                                <small class="text-muted"><?= e($e['member_number']) ?></small></td>
                            <td><?= e($e['class'] ?? '—') ?></td>
                            <td class="small"><?= e($e['group_name'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $sc ?>"><?= e($e['status']) ?></span></td>
                            <td>
                                <?php if (isset($e['start_fee_paid'])): ?>
                                    <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/fee') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm py-0 btn-<?= $e['start_fee_paid'] ? 'success' : 'outline-secondary' ?>" title="Przełącz opłatę">
                                            <i class="bi bi-cash<?= $e['start_fee_paid'] ? '' : '-coin' ?>"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= format_date(substr($e['registered_at'] ?? '', 0, 10)) ?></td>
                            <td class="text-end" style="white-space:nowrap">
                                <?php if ($e['status'] === 'zgloszony'): ?>
                                    <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/approve') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-success py-0" title="Zatwierdź"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form method="post" action="<?= url('competitions/entries/' . $e['id'] . '/reject') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-warning py-0" title="Odrzuć"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/entries/' . $e['id'] . '/remove') ?>"
                                      class="d-inline" onsubmit="return confirm('Usunąć zgłoszenie?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Brak zgłoszeń.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php if ($competition['status'] === 'otwarte'): ?>
        <div class="card">
            <div class="card-header"><strong>Dodaj zgłoszenie</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/entries/add') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select form-select-sm" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Klasa</label>
                        <select name="class" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php foreach (['Master','A','B','C','D'] as $cls): ?>
                                <option value="<?= $cls ?>"><?= $cls ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($groups): ?>
                    <div class="mb-2">
                        <label class="form-label">Grupa startowa</label>
                        <select name="group_id" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success btn-sm w-100">Zgłoś zawodnika</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Zapisy są zamknięte (status: <?= e($competition['status']) ?>).</div>
        <?php endif; ?>
    </div>
</div>
