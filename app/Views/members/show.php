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
        <a href="<?= url('members/' . $member['id'] . '/gdpr/consents') ?>" class="btn btn-sm btn-outline-secondary" title="Zarządzanie zgodami RODO">
            <i class="bi bi-shield-check"></i> RODO
        </a>
        <a href="<?= url('members/' . $member['id'] . '/weapons') ?>" class="btn btn-sm btn-outline-secondary" title="Rejestr broni osobistej">
            <i class="bi bi-shield-lock"></i> Broń
        </a>
        <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
        <a href="<?= url('members/' . $member['id'] . '/history') ?>" class="btn btn-sm btn-outline-secondary" title="Historia zmian profilu">
            <i class="bi bi-clock-history"></i> Historia
        </a>
        <?php endif; ?>
        <?php if (!empty($isSuperAdmin)): ?>
        <a href="<?= url('admin/impersonate/member/' . $member['id']) ?>"
           class="btn btn-sm btn-outline-warning"
           title="Zaloguj się jako ten zawodnik (portal)"
           onclick="return confirm('Zalogować się jako <?= e(addslashes($member['first_name'] . ' ' . $member['last_name'])) ?> (portal zawodnika)?')">
            <i class="bi bi-person-fill-gear"></i> Portal
        </a>
        <form method="post" action="<?= url('members/' . $member['id'] . '/purge') ?>"
              class="d-inline"
              onsubmit="return confirm('TRWAŁE USUNIĘCIE zawodnika <?= e(addslashes($member['first_name'] . ' ' . $member['last_name'])) ?>.\n\nUsuniętych danych nie można przywrócić. Kontynuować?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger" title="Trwale usuń zawodnika z systemu">
                <i class="bi bi-trash3-fill"></i> Usuń trwale
            </button>
        </form>
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

        <!-- Osiągnięcia -->
        <?php
        $canManageAchievements = in_array($authUser['role'] ?? '', ['admin', 'zarzad', 'instruktor']);
        $canDeleteAchievement  = in_array($authUser['role'] ?? '', ['admin', 'zarzad']);
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-trophy me-1 text-warning"></i>Osiągnięcia sportowe</strong>
                <?php if ($canManageAchievements): ?>
                <a href="<?= url('members/' . $member['id'] . '/achievements/create') ?>"
                   class="btn btn-sm btn-outline-warning py-0">
                    <i class="bi bi-plus"></i> Dodaj
                </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($achievements)): ?>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rodzaj</th>
                            <th class="text-center">Miejsce</th>
                            <th class="text-center">Rok</th>
                            <th>Zawody</th>
                            <?php if ($canDeleteAchievement): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $achievementTypes = \App\Models\MemberAchievementModel::TYPES;
                    $placeClass = \App\Models\MemberAchievementModel::PLACE_CLASS;
                    foreach ($achievements as $ach):
                        $placeNum = $ach['place'] !== null ? (int)$ach['place'] : null;
                    ?>
                        <tr>
                            <td class="small"><?= e($achievementTypes[$ach['achievement_type']] ?? $ach['achievement_type']) ?></td>
                            <td class="text-center">
                                <?php if ($placeNum !== null): ?>
                                    <span class="badge bg-<?= $placeClass[$placeNum] ?? 'secondary' ?>">
                                        <?= $placeNum ?>. m.
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= (int)$ach['year'] ?></td>
                            <td class="small text-muted"><?= e($ach['competition_name'] ?? '') ?></td>
                            <?php if ($canDeleteAchievement): ?>
                            <td class="text-end pe-2">
                                <form method="post"
                                      action="<?= url('members/' . $member['id'] . '/achievements/' . $ach['id'] . '/delete') ?>"
                                      class="d-inline"
                                      onsubmit="return confirm('Usunąć to osiągnięcie?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-xs btn-outline-danger py-0 px-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body">
                <p class="text-muted small mb-0">Brak zarejestrowanych osiągnięć sportowych.</p>
            </div>
            <?php endif; ?>
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
                <?php if (!empty($feeAssignment) && (float)$feeAssignment['final_annual_fee'] > 0): ?>
                <hr class="my-2">
                <p class="mb-0 small">
                    <span class="text-muted">Obliczona składka:</span><br>
                    <strong><?= number_format((float)$feeAssignment['final_annual_fee'], 2, ',', ' ') ?> PLN</strong>/rok
                    &nbsp;|&nbsp;
                    <strong><?= number_format((float)$feeAssignment['monthly_fee'], 2, ',', ' ') ?> PLN</strong>/mies.
                    <?php if ((float)$feeAssignment['early_payment_fee'] > 0): ?>
                    <br><span class="text-muted">Wczesna wpłata (do końca lutego):</span>
                    <strong><?= number_format((float)$feeAssignment['early_payment_fee'], 2, ',', ' ') ?> PLN</strong>
                    <?php endif; ?>
                </p>
                <?php if ((float)($feeAssignment['discount_class'] ?? 0) > 0 || (float)($feeAssignment['discount_achieve'] ?? 0) > 0): ?>
                <p class="mb-0 mt-1 small text-muted">
                    Baza: <?= number_format((float)$feeAssignment['base_annual_fee'], 2, ',', ' ') ?> PLN
                    <?php if ((float)$feeAssignment['discount_class'] > 0): ?>
                    − <?= number_format((float)$feeAssignment['discount_class'], 2, ',', ' ') ?> (klasa)
                    <?php endif; ?>
                    <?php if ((float)$feeAssignment['discount_achieve'] > 0): ?>
                    − <?= number_format((float)$feeAssignment['discount_achieve'], 2, ',', ' ') ?> (osiągnięcia)
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <?php endif; ?>
                <a href="<?= url('finances?member_id=' . $member['id']) ?>" class="small">Historia wpłat</a>
            </div>
        </div>

        <!-- Licencje -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Licencje PZSS</strong>
                <a href="<?= url('licenses/create?member_id=' . $member['id']) ?>" class="btn btn-sm btn-outline-primary py-0">
                    <i class="bi bi-plus"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php
                $licTypeLabels = [
                    'zawodnicza' => 'Zawodnicza',
                    'patent'     => 'Patent strzelecki',
                    'myśliwska'  => 'Myśliwska',
                    'sportowa'   => 'Sportowa',
                ];
                $allLicenses = $licensesByType ?? [];
                if (empty($allLicenses) && !empty($license)) {
                    $allLicenses['zawodnicza'] = $license;
                }
                ?>
                <?php if (!empty($allLicenses)): ?>
                <table class="table table-sm mb-0">
                    <?php foreach ($allLicenses as $lType => $lic):
                        $days = days_until($lic['valid_until'] ?? null); ?>
                    <tr>
                        <td class="ps-3 small text-muted" style="width:38%">
                            <?= e($licTypeLabels[$lType] ?? ucfirst($lType)) ?>
                        </td>
                        <td>
                            <code class="small"><?= e($lic['license_number']) ?></code>
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge bg-<?= alert_class($days, 60) ?>">
                                <?php if ($days === null): ?>bezterminowa
                                <?php elseif ($days >= 0): ?>za <?= $days ?> dni
                                <?php else: ?>WYGASŁA<?php endif; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                    <p class="text-muted mb-0 p-3">Brak licencji.</p>
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
