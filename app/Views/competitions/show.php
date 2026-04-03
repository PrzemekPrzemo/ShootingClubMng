<?php
$sc = match($competition['status']) {
    'planowane'=>'secondary','otwarte'=>'success','zamkniete'=>'warning','zakonczone'=>'dark',default=>'secondary'
};
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($competition['name']) ?></h2>
    <span class="badge bg-<?= $sc ?>"><?= e($competition['status']) ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('competitions/' . $competition['id'] . '/entries') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-plus"></i> Zgłoszenia (<?= count($entries) ?>)
        </a>
        <a href="<?= url('competitions/' . $competition['id'] . '/results') ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-list-ol"></i> Wyniki
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
        <!-- Top wyniki -->
        <div class="card">
            <div class="card-header"><strong>Wyniki</strong></div>
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
