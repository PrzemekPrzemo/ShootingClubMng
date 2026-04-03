<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-gear"></i> Konfiguracja systemu</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-auto">
        <a href="<?= url('config') ?>" class="btn btn-outline-primary btn-sm <?= !str_contains($_SERVER['REQUEST_URI'],'/categories') && !str_contains($_SERVER['REQUEST_URI'],'/users') ? 'active':'' ?>">
            <i class="bi bi-sliders"></i> Ustawienia
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/categories') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($_SERVER['REQUEST_URI'],'/categories') ? 'active':'' ?>">
            <i class="bi bi-tags"></i> Kategorie wiekowe
        </a>
    </div>
    <div class="col-auto">
        <a href="<?= url('config/users') ?>" class="btn btn-outline-secondary btn-sm <?= str_contains($_SERVER['REQUEST_URI'],'/users') ? 'active':'' ?>">
            <i class="bi bi-people"></i> Użytkownicy
        </a>
    </div>
</div>

<div class="row">
<div class="col-lg-8">
<div class="card">
    <div class="card-header"><strong>Dane klubu i alerty</strong></div>
    <div class="card-body">
        <form method="post" action="<?= url('config') ?>">
            <?= csrf_field() ?>

            <h6 class="text-muted mb-3">Dane klubu</h6>
            <?php foreach ([
                'club_name'    => 'Nazwa klubu',
                'club_address' => 'Adres',
                'club_email'   => 'E-mail',
                'club_phone'   => 'Telefon',
                'pzss_portal_url' => 'URL portalu PZSS',
            ] as $key => $label): ?>
            <div class="mb-3">
                <label class="form-label"><?= $label ?></label>
                <input type="text" name="<?= $key ?>" class="form-control"
                       value="<?= e($settings[$key]['value'] ?? '') ?>">
            </div>
            <?php endforeach; ?>

            <hr>
            <h6 class="text-muted mb-3">Alerty i terminy</h6>

            <?php foreach ([
                'alert_payment_days'       => ['Próg alertu zaległości (dni)', 'Ile dni przed terminem składki pokazywać alert?'],
                'alert_license_days'       => ['Próg alertu licencji (dni)', 'Ile dni przed wygaśnięciem licencji?'],
                'alert_medical_days'       => ['Próg alertu badań (dni)', 'Ile dni przed wygaśnięciem badań?'],
                'membership_fee_due_month' => ['Miesiąc terminu składki (1-12)', 'W którym miesiącu składka powinna być opłacona?'],
            ] as $key => [$label, $help]): ?>
            <div class="mb-3">
                <label class="form-label"><?= $label ?></label>
                <input type="number" name="<?= $key ?>" class="form-control" min="1"
                       value="<?= e($settings[$key]['value'] ?? '') ?>">
                <div class="form-text"><?= $help ?></div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-danger">
                <i class="bi bi-check-lg"></i> Zapisz konfigurację
            </button>
        </form>
    </div>
</div>
</div>
</div>
