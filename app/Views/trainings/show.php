<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('trainings') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($training['title']) ?></h2>
    <?php
    $sc = match($training['status']) {
        'planowany'  => 'info',
        'odbyl_sie'  => 'success',
        'odwolany'   => 'secondary',
        default      => 'secondary',
    };
    ?>
    <span class="badge bg-<?= $sc ?>"><?= e($training['status']) ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('trainings/' . $training['id'] . '/attendance') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-check"></i> Zarządzaj obecnością
        </a>
        <a href="<?= url('trainings/' . $training['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i> Edytuj
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <!-- Szczegóły treningu -->
        <div class="card mb-3">
            <div class="card-header"><strong>Szczegóły treningu</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Data</dt>
                    <dd class="col-sm-8"><?= format_date($training['training_date']) ?></dd>

                    <dt class="col-sm-4">Godziny</dt>
                    <dd class="col-sm-8">
                        <?php if ($training['time_start']): ?>
                            <?= e(substr($training['time_start'], 0, 5)) ?>
                            <?php if ($training['time_end']): ?>
                                &ndash; <?= e(substr($training['time_end'], 0, 5)) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Stanowisko</dt>
                    <dd class="col-sm-8"><?= e($training['lane'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Instruktor</dt>
                    <dd class="col-sm-8"><?= e($training['instructor_name'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Maks. uczestników</dt>
                    <dd class="col-sm-8"><?= $training['max_participants'] ? (int)$training['max_participants'] : '—' ?></dd>

                    <dt class="col-sm-4">Utworzył</dt>
                    <dd class="col-sm-8"><?= e($training['created_by_name'] ?? '—') ?></dd>
                </dl>
                <?php if ($training['notes']): ?>
                <hr>
                <p class="mb-0"><strong>Uwagi:</strong><br><?= nl2br(e($training['notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lista obecności -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-person-check"></i> Lista obecności</strong>
                <span class="badge bg-secondary"><?= count($attendees) ?> zapisanych</span>
            </div>
            <div class="card-body p-0">
                <?php if ($attendees): ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nr</th>
                            <th>Imię i nazwisko</th>
                            <th class="text-center">Obecny</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attendees as $a): ?>
                        <tr>
                            <td class="small text-muted"><code><?= e($a['member_number']) ?></code></td>
                            <td><?= e($a['last_name']) ?> <?= e($a['first_name']) ?></td>
                            <td class="text-center">
                                <?php if ($a['attended']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Tak</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Nie</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted p-3 mb-0">Brak zapisanych uczestników.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= url('trainings/' . $training['id'] . '/attendance') ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square"></i> Edytuj listę obecności
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-5">
        <!-- Statystyki -->
        <div class="card mb-3">
            <div class="card-header"><strong>Statystyki</strong></div>
            <div class="card-body">
                <?php
                $total    = count($attendees);
                $attended = count(array_filter($attendees, fn($a) => $a['attended']));
                $pct      = $total > 0 ? round($attended / $total * 100) : 0;
                ?>
                <div class="d-flex justify-content-between mb-1">
                    <span>Zapisani:</span><strong><?= $total ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Obecni:</span><strong class="text-success"><?= $attended ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Nieobecni:</span><strong class="text-warning"><?= $total - $attended ?></strong>
                </div>
                <div class="progress" style="height:10px">
                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                </div>
                <div class="text-center text-muted small mt-1">Frekwencja: <?= $pct ?>%</div>
            </div>
        </div>

        <!-- Danger zone -->
        <?php if (in_array($authUser['role'] ?? '', ['admin', 'zarzad'])): ?>
        <div class="card border-danger">
            <div class="card-header text-danger"><strong>Strefa administracyjna</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('trainings/' . $training['id'] . '/delete') ?>"
                      onsubmit="return confirm('Czy na pewno usunąć ten trening? Operacja jest nieodwracalna.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash"></i> Usuń trening
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
