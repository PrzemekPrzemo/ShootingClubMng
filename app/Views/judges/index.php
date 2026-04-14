<div class="d-flex align-items-center mb-3 gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-person-badge"></i> Rejestr sędziów</h2>
    <span class="badge bg-secondary"><?= count($judges ?? []) ?> licencji</span>
    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
    <a href="<?= url('judges/create') ?>" class="btn btn-sm btn-danger ms-auto">
        <i class="bi bi-plus-lg"></i> Dodaj licencję
    </a>
    <?php endif; ?>
</div>

<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle"></i>
    Rejestr pokazuje wszystkie licencje sędziowskie przypisane do zawodników.
    Ikona <i class="bi bi-person-check text-success"></i> oznacza, że sędzia posiada także konto użytkownika
    z możliwością logowania.
</div>

<!-- Filtry -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="judge_class" class="form-select form-select-sm">
            <option value="">Wszystkie klasy</option>
            <?php foreach (['III','II','I','P'] as $cls): ?>
            <option value="<?= $cls ?>" <?= ($filters['judge_class'] ?? '') === $cls ? 'selected' : '' ?>>
                Klasa <?= $cls ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">Wszystkie statusy</option>
            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktywna</option>
            <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Wygasła</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="fee_paid" class="form-select form-select-sm">
            <option value="">Opłata — wszystkie</option>
            <option value="yes" <?= ($filters['fee_paid'] ?? '') === 'yes' ? 'selected' : '' ?>>Opłata zapłacona</option>
            <option value="no" <?= ($filters['fee_paid'] ?? '') === 'no' ? 'selected' : '' ?>>Opłata niezapłacona</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filtruj</button>
        <a href="<?= url('judges') ?>" class="btn btn-sm btn-outline-secondary ms-1">Wyczyść</a>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Zawodnik</th>
                    <th class="text-center">Klasa</th>
                    <th>Dyscyplina</th>
                    <th>Nr licencji</th>
                    <th>Ważna do</th>
                    <th class="text-center">Opłata PomZSS</th>
                    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                    <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($judges as $j): ?>
                <?php
                $days   = (int)$j['days_left'];
                $licCls = $days < 0 ? 'danger' : ($days <= 30 ? 'warning' : 'success');
                $curYear = (int)date('Y');
                $feePaid = ($j['fee_paid_year'] == $curYear);
                ?>
                <tr>
                    <td>
                        <a href="<?= url('members/' . $j['member_id']) ?>">
                            <?= e($j['last_name']) ?> <?= e($j['first_name']) ?>
                        </a>
                        <small class="text-muted"><?= e($j['member_number']) ?></small>
                        <?php if (!empty($j['user_id'])): ?>
                            <i class="bi bi-person-check text-success ms-1" title="Posiada konto użytkownika: <?= e($j['user_username'] ?? '') ?>"></i>
                        <?php endif; ?>
                        <?php if (($j['member_status'] ?? 'aktywny') !== 'aktywny'): ?>
                            <span class="badge bg-secondary ms-1"><?= e($j['member_status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-dark"><?= e($j['judge_class']) ?></span>
                    </td>
                    <td class="small"><?= e($j['discipline_name'] ?? '—') ?></td>
                    <td class="small"><?= e($j['license_number'] ?? '—') ?></td>
                    <td>
                        <?= format_date($j['valid_until']) ?>
                        <span class="badge bg-<?= $licCls ?>">
                            <?= $days >= 0 ? "za {$days} dni" : 'WYGASŁA' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($feePaid): ?>
                            <span class="badge bg-success"><i class="bi bi-check"></i> <?= $j['fee_paid_year'] ?></span>
                        <?php else: ?>
                            <span class="badge bg-danger">Niezapłacona</span>
                            <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                            <form method="post" action="<?= url('judges/' . $j['id'] . '/fee-paid') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-outline-success py-0 px-1 ms-1" title="Oznacz jako zapłaconą">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php if (in_array($authUser['role'], ['admin','zarzad'])): ?>
                    <td class="text-end" style="white-space:nowrap">
                        <a href="<?= url('judges/' . $j['id'] . '/edit') ?>"
                           class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('judges/' . $j['id'] . '/delete') ?>"
                              class="d-inline" onsubmit="return confirm('Usunąć tę licencję sędziowską?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($judges)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak licencji sędziowskich.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 small text-muted">
    <i class="bi bi-info-circle"></i>
    Klasy sędziowskie PZSS: III (podstawowa), II, I, P (państwowy).
    Odnowienie licencji: 50 PLN/rok do PomZSS.
</div>
