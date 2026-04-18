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
                    if ($p) {
                        $len = strlen($p);
                        $mask = $len > 3 ? str_repeat('*', $len - 3) : str_repeat('*', max(0, $len - 3));
                        echo e(substr($p, 0, 2) . $mask . substr($p, -1));
                    } else {
                        echo '—';
                    }
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
                <dd class="col-sm-7"><?php
                    $email = $member['email'] ?? '';
                    if (empty($email)) {
                        // Fallback to session (set at login) in case query returns empty
                        $email = \App\Helpers\Session::get('member_email', '');
                    }
                    echo $email ? e($email) : '<span class="text-muted">brak — skontaktuj się z zarządem klubu</span>';
                ?></dd>

                <dt class="col-sm-5">Telefon</dt>
                <dd class="col-sm-7"><?= e($member['phone'] ?? '—') ?></dd>

                <dt class="col-sm-5">Adres</dt>
                <dd class="col-sm-7">
                    <?php
                        $addrParts = [];
                        if (!empty($member['address_street'])) $addrParts[] = $member['address_street'];
                        $postCity = trim(($member['address_postal'] ?? '') . ' ' . ($member['address_city'] ?? ''));
                        if ($postCity !== '') $addrParts[] = $postCity;
                        echo $addrParts ? e(implode(', ', $addrParts)) : '—';
                    ?>
                </dd>
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

    <div class="card mt-3">
        <div class="card-header"><strong>Dyscypliny</strong></div>
        <div class="card-body p-0">
            <?php if ($disciplines): ?>
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dyscyplina</th>
                        <th>Klasa</th>
                        <th>Od</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($disciplines as $d): ?>
                    <tr>
                        <td><?= e($d['name'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($d['class'])): ?>
                                <span class="badge bg-info text-dark"><?= e($d['class']) ?></span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= !empty($d['joined_at']) ? format_date($d['joined_at']) : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted m-3 mb-0 small">Brak przypisanych dyscyplin.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
