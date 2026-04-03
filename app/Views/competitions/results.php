<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0">Wyniki — <?= e($competition['name']) ?></h2>
    <span class="badge bg-secondary"><?= format_date($competition['competition_date']) ?></span>
</div>

<?php if (empty($entries)): ?>
<div class="alert alert-warning">Brak zgłoszeń — nie można wprowadzić wyników.</div>
<?php else: ?>
<form method="post" action="<?= url('competitions/' . $competition['id'] . '/results/save') ?>">
    <?= csrf_field() ?>

    <!-- Build a lookup of existing results by member_id -->
    <?php
    $resultsMap = [];
    foreach ($results as $r) { $resultsMap[$r['member_id']] = $r; }
    ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Zawodnik</th>
                            <th>Wynik</th>
                            <th>Miejsce</th>
                            <th>Grupa</th>
                            <th>Uwagi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php $r = $resultsMap[$entry['member_id']] ?? null; ?>
                        <input type="hidden" name="member_id[]" value="<?= $entry['member_id'] ?>">
                        <tr>
                            <td>
                                <?= e($entry['last_name']) ?> <?= e($entry['first_name']) ?>
                                <small class="text-muted">(<?= e($entry['class'] ?? '') ?>)</small>
                            </td>
                            <td style="width:120px">
                                <input type="number" name="score[]" step="0.01" class="form-control form-control-sm"
                                       value="<?= e($r['score'] ?? '') ?>">
                            </td>
                            <td style="width:80px">
                                <input type="number" name="place[]" min="1" class="form-control form-control-sm"
                                       value="<?= e($r['place'] ?? '') ?>">
                            </td>
                            <td style="width:140px">
                                <select name="group_id[]" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    <?php foreach ($groups as $g): ?>
                                        <option value="<?= $g['id'] ?>" <?= ($r['group_id'] ?? '') == $g['id'] ? 'selected':'' ?>>
                                            <?= e($g['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="notes[]" class="form-control form-control-sm"
                                       value="<?= e($r['notes'] ?? '') ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-check-lg"></i> Zapisz wyniki
        </button>
        <a href="<?= url('competitions/' . $competition['id']) ?>" class="btn btn-outline-secondary ms-2">Anuluj</a>
    </div>
</form>
<?php endif; ?>
