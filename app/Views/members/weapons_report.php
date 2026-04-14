<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><i class="bi bi-file-earmark-pdf"></i> Raport broni zawodników</h2>
</div>

<div class="card" style="max-width:900px">
    <div class="card-header"><strong>Wybierz zawodników do raportu</strong></div>
    <div class="card-body">
        <p class="text-muted mb-3">
            Raport zawiera: <strong>L.p. / Nazwisko i Imię / PESEL / Nr pozwolenia / Rodzaj broni / Kaliber / Producent i nazwa / Numer broni</strong>.
            Uwzględniana jest tylko aktywna broń osobista zawodnika.
        </p>

        <form method="post" action="<?= url('members/weapons-report/pdf') ?>" id="reportForm">
            <?= csrf_field() ?>

            <div class="d-flex gap-2 mb-3">
                <button type="submit" name="all_members" value="1" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Generuj raport dla wszystkich
                </button>
                <button type="submit" class="btn btn-outline-danger" id="selectedBtn" disabled>
                    <i class="bi bi-file-earmark-pdf"></i> Generuj raport dla zaznaczonych
                    (<span id="selectedCount">0</span>)
                </button>
            </div>

            <?php if (empty($members)): ?>
            <div class="alert alert-info">Brak zawodników z zarejestrowaną bronią osobistą.</div>
            <?php else: ?>
            <div class="mb-2 d-flex gap-2 align-items-center">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="checkAll">
                    <label class="form-check-label fw-semibold" for="checkAll">Zaznacz wszystkich</label>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="checkAllTh" class="form-check-input"></th>
                            <th>Nr</th>
                            <th>Nazwisko i imię</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <input class="form-check-input member-check" type="checkbox"
                                       name="member_ids[]" value="<?= (int)$m['id'] ?>">
                            </td>
                            <td><code><?= e($m['member_number']) ?></code></td>
                            <td><?= e($m['last_name']) ?> <?= e($m['first_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
(function () {
    const checks  = document.querySelectorAll('.member-check');
    const allBox  = document.getElementById('checkAll');
    const allTh   = document.getElementById('checkAllTh');
    const selBtn  = document.getElementById('selectedBtn');
    const selCnt  = document.getElementById('selectedCount');

    function updateCount() {
        const n = document.querySelectorAll('.member-check:checked').length;
        selCnt.textContent = n;
        selBtn.disabled = n === 0;
    }

    [allBox, allTh].forEach(el => {
        if (!el) return;
        el.addEventListener('change', function () {
            checks.forEach(c => { c.checked = this.checked; });
            if (allBox) allBox.checked = this.checked;
            if (allTh)  allTh.checked  = this.checked;
            updateCount();
        });
    });

    checks.forEach(c => c.addEventListener('change', updateCount));
}());
</script>
