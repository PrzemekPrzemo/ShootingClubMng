<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-award"></i> Dyplomy: <?= e($competition['name']) ?></h2>
    <a href="<?= url('competitions/' . $competition['id'] . '/certificates.pdf') ?>" class="btn btn-sm btn-danger ms-auto">
        <i class="bi bi-file-earmark-pdf"></i> Pobierz PDF (top 3)
    </a>
    <a href="<?= url('competitions/' . $competition['id'] . '/certificates.pdf?top=5') ?>" class="btn btn-sm btn-outline-danger">
        Top 5
    </a>
</div>

<?php if (empty($certificates)): ?>
<div class="alert alert-warning">Brak wyników z przypisanymi miejscami. Uzupełnij <strong>final_place</strong> w wynikach zawodów.</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Miejsce</th>
                    <th>Zawodnik</th>
                    <th>Kategoria</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($certificates as $c): ?>
                <tr>
                    <td>
                        <?php $trophy = ['1'=>'🥇','2'=>'🥈','3'=>'🥉']; ?>
                        <?= $trophy[$c['place']] ?? '' ?> <strong><?= (int)$c['place'] ?>.</strong>
                    </td>
                    <td><?= e($c['first_name']) ?> <?= e($c['last_name']) ?></td>
                    <td><?= e($c['category'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
