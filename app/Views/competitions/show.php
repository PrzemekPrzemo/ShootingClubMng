<?php
$sc = match($competition['status']) {
    'planowane'=>'secondary','otwarte'=>'success','zamkniete'=>'warning','zakonczone'=>'dark',default=>'secondary'
};
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($competition['name']) ?></h2>
    <span class="badge bg-<?= $sc ?>"><?= e($competition['status']) ?></span>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-plus"></i> Zgłoszenia (<?= count($entries) ?>)
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/events') ?>" class="btn btn-sm btn-outline-info">
            <i class="bi bi-bullseye"></i> Konkurencje (<?= count($events) ?>)
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/results') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-list-ol"></i> Wyniki ogólne
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/rankings') ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-trophy"></i> Rankingi
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/protocol') ?>"
           target="_blank"
           class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer"></i> Protokół
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/protocol.pdf') ?>"
           class="btn btn-sm btn-outline-danger"
           title="Pobierz protokół PDF">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/scorecards') ?>" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-file-person"></i> Metryczki A5
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i> Edytuj
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Data</dt>
                    <dd class="col-sm-7"><?= format_date($competition['competition_date']) ?></dd>
                    <dt class="col-sm-5">Dyscyplina</dt>
                    <dd class="col-sm-7"><?= e($competition['discipline_name'] ?? '—') ?></dd>
                    <dt class="col-sm-5">Miejsce</dt>
                    <dd class="col-sm-7"><?= e($competition['location'] ?? '—') ?></dd>
                    <dt class="col-sm-5">Maks. zgłoszeń</dt>
                    <dd class="col-sm-7"><?= $competition['max_entries'] ?? 'bez limitu' ?></dd>
                </dl>
            </div>
        </div>
        <?php if ($competition['description']): ?>
        <div class="card">
            <div class="card-header"><strong>Opis</strong></div>
            <div class="card-body small"><?= nl2br(e($competition['description'])) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <!-- Konkurencje -->
        <?php if (!empty($events)): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-bullseye"></i> Konkurencje</strong>
                <a href="<?= url('competitions/' . $competition['id'] . '/events') ?>"
                   class="btn btn-xs btn-outline-info py-0 px-2">Zarządzaj</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Nazwa</th><th class="text-center">Strzały</th><th class="text-center">Wyniki</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><?= e($ev['name']) ?></td>
                            <td class="text-center"><?= $ev['shots_count'] ?? '—' ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $ev['result_count'] > 0 ? 'success':'secondary' ?>">
                                    <?= $ev['result_count'] ?>
                                </span>
                            </td>
                            <td class="text-end" style="white-space:nowrap">
                                <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $ev['id'] . '/results') ?>"
                                   class="btn btn-xs btn-outline-primary py-0 px-1">Wyniki</a>
                                <a href="<?= url('competitions/' . $competition['id'] . '/events/' . $ev['id'] . '/startcard') ?>"
                                   target="_blank"
                                   class="btn btn-xs btn-outline-secondary py-0 px-1"
                                   title="Lista startowa A4"><i class="bi bi-printer"></i></a>
                                <a href="<?= url('competitions/' . $competition['id'] . '/scorecards?e[]=' . $ev['id']) ?>"
                                   class="btn btn-xs btn-outline-dark py-0 px-1"
                                   title="Generuj metryczki A5 dla tej konkurencji"><i class="bi bi-file-person"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sędziowie -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-person-badge"></i> Sędziowie</strong>
            </div>
            <?php
            $roleLabels = [
                'glowny'        => 'Sędzia główny',
                'liniowy'       => 'Sędzia liniowy',
                'obliczeniowy'  => 'Obliczeniowy',
                'bezpieczenstwa'=> 'Bezpieczeństwo',
                'protokolant'   => 'Protokolant',
            ];
            ?>
            <?php if (!empty($judges)): ?>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($judges as $j): ?>
                        <tr>
                            <td class="small text-muted"><?= e($roleLabels[$j['role']] ?? $j['role']) ?></td>
                            <td><?= e($j['last_name']) ?> <?= e($j['first_name']) ?></td>
                            <td class="small">
                                <?php if ($j['judge_class']): ?>
                                    <span class="badge bg-dark">kl. <?= e($j['judge_class']) ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if (in_array($authUser['role'], ['admin','zarzad','instruktor'])): ?>
                            <td class="text-end">
                                <form method="post"
                                      action="<?= url('competitions/' . $competition['id'] . '/judges/' . $j['id'] . '/remove') ?>"
                                      class="d-inline" onsubmit="return confirm('Usunąć sędziego?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body">
                <p class="text-muted mb-2 small">Brak przypisanych sędziów.</p>
            </div>
            <?php endif; ?>
            <?php if (in_array($authUser['role'], ['admin','zarzad','instruktor']) && !empty($activeJudges)): ?>
            <div class="card-footer">
                <form method="post" action="<?= url('competitions/' . $competition['id'] . '/judges/add') ?>"
                      class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col">
                        <select name="member_id" class="form-select form-select-sm" required>
                            <option value="">Wybierz sędziego...</option>
                            <?php foreach ($activeJudges as $aj): ?>
                            <option value="<?= $aj['id'] ?>">
                                <?= e($aj['last_name']) ?> <?= e($aj['first_name']) ?>
                                [kl. <?= e($aj['judge_class']) ?>]
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="role" class="form-select form-select-sm">
                            <?php foreach ($roleLabels as $val => $lbl): ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top wyniki -->
        <div class="card">
            <div class="card-header"><strong>Wyniki ogólne</strong></div>
            <div class="card-body p-0">
                <?php if ($results): ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Miejsce</th><th>Zawodnik</th><th>Wynik</th><th>Grupa</th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td>
                                <?php if ($r['place'] == 1): ?><span class="badge bg-warning text-dark">🥇 1</span>
                                <?php elseif ($r['place'] == 2): ?><span class="badge bg-secondary">🥈 2</span>
                                <?php elseif ($r['place'] == 3): ?><span class="badge bg-danger">🥉 3</span>
                                <?php else: ?><?= $r['place'] ?>
                                <?php endif; ?>
                            </td>
                            <td><a href="<?= url('members/' . $r['member_id']) ?>"><?= e($r['last_name']) ?> <?= e($r['first_name']) ?></a></td>
                            <td><?= $r['score'] !== null ? $r['score'] : '—' ?></td>
                            <td class="small text-muted"><?= e($r['group_name'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted p-3 mb-0">Brak wyników.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
