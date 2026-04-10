<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-person"></i> Mój profil</h2>
    <a href="<?= url('portal/profile/edit') ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-pencil"></i> Edytuj dane kontaktowe
    </a>
</div>

<?php if (!empty($member['photo_path'])): ?>
<div class="text-center mb-3">
    <img src="<?= url('members/' . (int)$member['id'] . '/photo') ?>"
         alt="Moje zdjęcie" class="rounded-circle border"
         style="width:80px;height:80px;object-fit:cover">
</div>
<?php endif; ?>

<div class="row g-3">
<div class="col-lg-6">
    <div class="card">
        <div class="card-header"><strong>Dane osobowe</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-5">Imię i nazwisko</dt>
                <dd class="col-sm-7"><?= e($member['first_name'] . ' ' . $member['last_name']) ?></dd>

                <dt class="col-sm-5">Numer członkowski</dt>
                <dd class="col-sm-7"><code><?= e($member['member_number']) ?></code></dd>

                <dt class="col-sm-5">Data urodzenia</dt>
                <dd class="col-sm-7"><?= format_date($member['birth_date'] ?? '') ?></dd>

                <dt class="col-sm-5">PESEL</dt>
                <dd class="col-sm-7"><?php
                    $p = $member['pesel'] ?? '';
                    echo $p ? (substr($p, 0, 3) . '****' . substr($p, -4)) : '—';
                ?></dd>

                <dt class="col-sm-5">Płeć</dt>
                <dd class="col-sm-7"><?= e($member['gender'] ?? '—') ?></dd>

                <dt class="col-sm-5">Status</dt>
                <dd class="col-sm-7">
                    <span class="badge bg-<?= $member['status'] === 'aktywny' ? 'success' : 'warning' ?>">
                        <?= e($member['status']) ?>
                    </span>
                </dd>

                <dt class="col-sm-5">Kategoria wiekowa</dt>
                <dd class="col-sm-7"><?= e($member['age_category_name'] ?? '—') ?></dd>

                <dt class="col-sm-5">Typ zawodnika</dt>
                <dd class="col-sm-7"><?= e($member['member_type'] ?? '—') ?></dd>

                <dt class="col-sm-5">Klasa sportowa</dt>
                <dd class="col-sm-7"><?= e($member['sport_class'] ?? '—') ?></dd>

                <dt class="col-sm-5">Data wstąpienia</dt>
                <dd class="col-sm-7"><?= format_date($member['join_date'] ?? '') ?></dd>
            </dl>
        </div>
    </div>
</div>

<div class="col-lg-6">
    <div class="card">
        <div class="card-header"><strong>Kontakt</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-5">E-mail</dt>
                <dd class="col-sm-7"><?= e($member['email'] ?? '—') ?></dd>

                <dt class="col-sm-5">Telefon</dt>
                <dd class="col-sm-7"><?= e($member['phone'] ?? '—') ?></dd>

                <dt class="col-sm-5">Adres</dt>
                <dd class="col-sm-7"><?= e($member['address'] ?? '—') ?></dd>
            </dl>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><strong>Bezpieczeństwo konta</strong></div>
        <div class="card-body">
            <a href="<?= url('portal/change-password') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-key"></i> Zmień hasło
            </a>
        </div>
    </div>

    <?php if ($disciplines): ?>
    <div class="card mt-3">
        <div class="card-header"><strong>Dyscypliny</strong></div>
        <div class="card-body">
            <?php foreach ($disciplines as $d): ?>
                <span class="badge bg-secondary me-1"><?= e($d['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>
