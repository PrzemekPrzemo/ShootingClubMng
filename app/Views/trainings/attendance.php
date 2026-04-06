<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('trainings/' . $training['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0"><i class="bi bi-person-check"></i> Lista obecności</h2>
    <span class="text-muted">— <?= e($training['title']) ?> (<?= format_date($training['training_date']) ?>)</span>
</div>

<form method="post" action="<?= url('trainings/' . $training['id'] . '/attendance') ?>">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Uczestnicy</strong>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-success btn-sm" id="selectAll">
                    <i class="bi bi-check-all"></i> Zaznacz wszystkich
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAll">
                    <i class="bi bi-x-circle"></i> Odznacz wszystkich
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" class="form-check-input" id="masterEnroll" title="Zaznacz wszystkich">
                        </th>
                        <th>Nr</th>
                        <th>Imię i nazwisko</th>
                        <th>Typ</th>
                        <th class="text-center" style="width:100px">Obecny</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $m): ?>
                    <?php
                    $isEnrolled  = in_array((int)$m['id'], $enrolledIds);
                    $didAttend   = in_array((int)$m['id'], $attendedIds);
                    ?>
                    <tr class="member-row <?= $isEnrolled ? 'table-primary' : '' ?>">
                        <td>
                            <input type="checkbox" class="form-check-input enroll-check"
                                   name="member_ids[]" value="<?= $m['id'] ?>"
                                   <?= $isEnrolled ? 'checked' : '' ?>>
                        </td>
                        <td><code class="small"><?= e($m['member_number']) ?></code></td>
                        <td><?= e($m['last_name']) ?> <?= e($m['first_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $m['member_type'] === 'wyczynowy' ? 'danger' : 'secondary' ?> small">
                                <?= e($m['member_type']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input attended-check"
                                   name="attended[]" value="<?= $m['id'] ?>"
                                   <?= $didAttend ? 'checked' : '' ?>
                                   <?= !$isEnrolled ? 'disabled' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($members)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Brak aktywnych zawodników.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Zaznaczonych: <strong id="enrolledCount"><?= count($enrolledIds) ?></strong>
                | Obecnych: <strong id="attendedCount"><?= count($attendedIds) ?></strong>
            </span>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-save"></i> Zapisz listę obecności
            </button>
        </div>
    </div>
</form>

<script>
(function () {
    function updateCounts() {
        var enrolled  = document.querySelectorAll('.enroll-check:checked').length;
        var attended  = document.querySelectorAll('.attended-check:checked').length;
        document.getElementById('enrolledCount').textContent = enrolled;
        document.getElementById('attendedCount').textContent = attended;
    }

    // When enroll checkbox changes, enable/disable attended checkbox
    document.querySelectorAll('.enroll-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var row        = this.closest('tr');
            var attendedCb = row.querySelector('.attended-check');
            if (attendedCb) {
                attendedCb.disabled = !this.checked;
                if (!this.checked) attendedCb.checked = false;
            }
            row.classList.toggle('table-primary', this.checked);
            updateCounts();
        });
    });

    document.querySelectorAll('.attended-check').forEach(function (cb) {
        cb.addEventListener('change', updateCounts);
    });

    // Select all / deselect all enrolled
    document.getElementById('selectAll').addEventListener('click', function () {
        document.querySelectorAll('.enroll-check').forEach(function (cb) {
            cb.checked = true;
            var row        = cb.closest('tr');
            var attendedCb = row.querySelector('.attended-check');
            if (attendedCb) attendedCb.disabled = false;
            row.classList.add('table-primary');
        });
        updateCounts();
    });

    document.getElementById('deselectAll').addEventListener('click', function () {
        document.querySelectorAll('.enroll-check').forEach(function (cb) {
            cb.checked = false;
            var row        = cb.closest('tr');
            var attendedCb = row.querySelector('.attended-check');
            if (attendedCb) { attendedCb.checked = false; attendedCb.disabled = true; }
            row.classList.remove('table-primary');
        });
        updateCounts();
    });

    document.getElementById('masterEnroll').addEventListener('change', function () {
        var checked = this.checked;
        document.querySelectorAll('.enroll-check').forEach(function (cb) {
            cb.checked = checked;
            var row        = cb.closest('tr');
            var attendedCb = row.querySelector('.attended-check');
            if (attendedCb) { attendedCb.disabled = !checked; if (!checked) attendedCb.checked = false; }
            row.classList.toggle('table-primary', checked);
        });
        updateCounts();
    });
})();
</script>
