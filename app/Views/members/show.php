<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= url('members') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="h4 mb-0"><?= e($member['last_name']) ?> <?= e($member['first_name']) ?></h2>
    <span class="ms-2 badge bg-<?= $member['member_type']==='wyczynowy' ? 'danger':'secondary' ?>">
        <?= e($member['member_type']) ?>
    </span>
    <?php
    $sc = match($member['status']) { 'aktywny'=>'success', 'zawieszony'=>'warning', 'wykreslony'=>'danger', default=>'secondary' };
    ?>
    <span class="badge bg-<?= $sc ?>"><?= e($member['status']) ?></span>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= url('members/' . $member['id'] . '/card') ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Drukuj kartę zawodnika">
            <i class="bi bi-person-vcard"></i> Karta
        </a>
        <a href="<?= url('members/' . $member['id'] . '/card.pdf') ?>" class="btn btn-sm btn-outline-danger" title="Pobierz legitymację PDF">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
        <a href="<?= url('members/' . $member['id'] . '/weapons') ?>" class="btn btn-sm btn-outline-secondary" title="Rejestr broni osobistej">
            <i class="bi bi-shield-lock"></i> Broń
        </a>
        <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
        <a href="<?= url('members/' . $member['id'] . '/history') ?>" class="btn btn-sm btn-outline-secondary" title="Historia zmian profilu">
            <i class="bi bi-clock-history"></i> Historia
        </a>
        <?php endif; ?>
        <a href="<?= url('members/' . $member['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil"></i> Edytuj
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Dane -->
        <div class="card mb-3">
            <div class="card-header"><strong>Dane osobowe</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nr członkowski</dt>
                    <dd class="col-sm-8"><code><?= e($member['member_number']) ?></code></dd>
                    <dt class="col-sm-4">Nr karty dostępu</dt>
                    <dd class="col-sm-8"><?= e($member['card_number'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Data urodzenia</dt>
                    <dd class="col-sm-8"><?= format_date($member['birth_date']) ?></dd>
                    <dt class="col-sm-4">PESEL</dt>
                    <dd class="col-sm-8"><?= e($member['pesel'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Płeć</dt>
                    <dd class="col-sm-8"><?= $member['gender'] === 'M' ? 'Mężczyzna' : ($member['gender'] === 'K' ? 'Kobieta' : '—') ?></dd>
                    <dt class="col-sm-4">Kategoria wiekowa</dt>
                    <dd class="col-sm-8"><?= e($member['age_category_name'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Klasa zawodnika</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($member['member_class_name'])): ?>
                            <span class="badge bg-info text-dark"><?= e($member['member_class_name']) ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">E-mail</dt>
                    <dd class="col-sm-8"><?= $member['email'] ? '<a href="mailto:' . e($member['email']) . '">' . e($member['email']) . '</a>' : '—' ?></dd>
                    <dt class="col-sm-4">Telefon</dt>
                    <dd class="col-sm-8"><?= e($member['phone'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Adres</dt>
                    <dd class="col-sm-8">
                        <?= e($member['address_street'] ?? '') ?>
                        <?php if ($member['address_city']): ?>
                            , <?= e($member['address_postal'] ?? '') ?> <?= e($member['address_city']) ?>
                        <?php endif; ?>
                        <?php if (!$member['address_street'] && !$member['address_city']): ?>—<?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Data wstąpienia</dt>
                    <dd class="col-sm-8"><?= format_date($member['join_date']) ?></dd>
                </dl>
            </div>
        </div>

        <!-- Dyscypliny -->
        <div class="card mb-3">
            <div class="card-header"><strong>Dyscypliny</strong></div>
            <div class="card-body p-0">
                <?php if ($disciplines): ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Dyscyplina</th><th>Klasa</th><th>Instruktor</th><th>Od</th></tr></thead>
                    <tbody>
                    <?php foreach ($disciplines as $d): ?>
                        <tr>
                            <td><?= e($d['discipline_name']) ?> <small class="text-muted">[<?= e($d['short_code']) ?>]</small></td>
                            <td><?= e($d['class'] ?? '—') ?></td>
                            <td><?= e($d['instructor_name'] ?? '—') ?></td>
                            <td><?= format_date($d['joined_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted p-3 mb-0">Brak przypisanych dyscyplin.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($member['notes']): ?>
        <div class="card mb-3">
            <div class="card-header"><strong>Uwagi</strong></div>
            <div class="card-body"><p class="mb-0"><?= nl2br(e($member['notes'])) ?></p></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Photo avatar -->
        <div class="text-center mb-3">
            <?php if (!empty($member['photo_path'])): ?>
            <img src="<?= url('members/' . (int)$member['id'] . '/photo') ?>"
                 alt="<?= e($member['first_name'] . ' ' . $member['last_name']) ?>"
                 class="rounded-circle border"
                 style="width:96px;height:96px;object-fit:cover;border-color:#dee2e6!important">
            <?php else: ?>
            <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white fw-bold"
                 style="width:96px;height:96px;font-size:2rem">
                <?= e(mb_strtoupper(mb_substr($member['first_name'], 0, 1) . mb_substr($member['last_name'], 0, 1))) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Składki -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Składki <?= date('Y') ?></strong>
                <a href="<?= url('finances/create?member_id=' . $member['id']) ?>" class="btn btn-sm btn-outline-success py-0">
                    <i class="bi bi-plus"></i> Wpłata
                </a>
            </div>
            <div class="card-body">
                <p class="mb-0">
                    Łącznie zapłacono: <strong><?= format_money($payment['total'] ?? 0) ?></strong>
                </p>
                <a href="<?= url('finances?member_id=' . $member['id']) ?>" class="small">Historia wpłat</a>
            </div>
        </div>

        <!-- Licencja -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Licencja PZSS</strong>
                <a href="<?= url('licenses/create?member_id=' . $member['id']) ?>" class="btn btn-sm btn-outline-primary py-0">
                    <i class="bi bi-plus"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if ($license): ?>
                    <?php $days = days_until($license['valid_until']); ?>
                    <p class="mb-1">Nr: <code><?= e($license['license_number']) ?></code></p>
                    <p class="mb-0">
                        Ważna do: <?= format_date($license['valid_until']) ?>
                        <span class="badge bg-<?= alert_class($days, 60) ?>">
                            <?= $days === null ? 'bezterminowa' : ($days >= 0 ? "za {$days} dni" : 'WYGASŁA') ?>
                        </span>
                    </p>
                <?php else: ?>
                    <p class="text-muted mb-0">Brak licencji.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badania lekarskie -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Badania lekarskie</strong>
                <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                <a href="<?= url('members/' . $member['id'] . '/exams/create') ?>" class="btn btn-sm btn-outline-info py-0">
                    <i class="bi bi-plus"></i>
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($examMatrix)): ?>
                    <?php foreach ($examMatrix as $row): ?>
                    <?php
                    $statusCls = ['ok'=>'success','warn'=>'warning','expired'=>'danger','missing'=>'secondary'][$row['status']] ?? 'secondary';
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted"><?= e($row['type_name']) ?></small>
                        <span class="badge bg-<?= $statusCls ?>">
                            <?php if ($row['status'] === 'missing'): ?>Brak
                            <?php elseif ($row['status'] === 'expired'): ?>Wygasłe
                            <?php elseif ($row['status'] === 'warn'): ?>za <?= $row['days_left'] ?> dni
                            <?php else: ?>OK<?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?= url('members/' . $member['id'] . '/exams') ?>" class="small">Historia badań</a>
                <?php elseif ($medical): ?>
                    <?php $days = days_until($medical['valid_until']); ?>
                    <p class="mb-1">Badanie: <?= format_date($medical['exam_date']) ?></p>
                    <p class="mb-0">
                        Ważne do: <?= format_date($medical['valid_until']) ?>
                        <span class="badge bg-<?= alert_class($days, 30) ?>">
                            <?= $days === null ? 'bezterminowa' : ($days >= 0 ? "za {$days} dni" : 'WYGASŁE') ?>
                        </span>
                    </p>
                    <a href="<?= url('members/' . $member['id'] . '/exams') ?>" class="small">Historia badań</a>
                <?php else: ?>
                    <p class="text-muted mb-0 small">Brak badań lekarskich.</p>
                    <a href="<?= url('members/' . $member['id'] . '/exams') ?>" class="small">Zarządzaj badaniami</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger zone -->
        <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
        <div class="card border-danger">
            <div class="card-header text-danger"><strong>Strefa administracyjna</strong></div>
            <div class="card-body">
                <form method="post" action="<?= url('members/' . $member['id'] . '/delete') ?>"
                      onsubmit="return confirm('Czy na pewno wykreślić zawodnika? Operacja jest odwracalna przez edycję statusu.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-person-x"></i> Wykreśl zawodnika
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
