<?php
$roleLabels = [
    'admin'      => 'Administrator',
    'zarzad'     => 'Zarząd',
    'instruktor' => 'Instruktor',
    'sędzia'     => 'Sędzia',
    'zawodnik'   => 'Zawodnik',
];
$roleColors = [
    'admin'      => 'danger',
    'zarzad'     => 'primary',
    'instruktor' => 'success',
    'sędzia'     => 'warning',
    'zawodnik'   => 'secondary',
];
?>
<div class="card shadow-sm" style="max-width:460px;margin:0 auto">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <?php if (!empty($systemBranding['logo'])): ?>
                <img src="<?= url('system-logo') ?>?v=<?= $systemBranding['logoMts'] ?? '0' ?>"
                     alt="<?= e($systemBranding['name'] ?? 'Shootero') ?>"
                     style="height:48px;max-width:180px;object-fit:contain" class="mb-2 d-block mx-auto">
            <?php else: ?>
                <i class="bi bi-building-check" style="font-size:2.5rem;color:#D4A373"></i>
            <?php endif; ?>
            <h5 class="mt-2 mb-0 fw-bold" style="font-family:'Poppins',sans-serif;color:#fff">
                Wybierz klub
            </h5>
            <p class="small mt-1 mb-0" style="color:#94A3B8">
                Twoje konto jest przypisane do kilku klubów
            </p>
        </div>

        <div class="d-grid gap-2">
            <?php foreach ($clubs as $club):
                $roles     = $club['roles'] ?? [$club['role']];
                $topRole   = $club['highest_role'] ?? $roles[0];
                $color     = $roleColors[$topRole] ?? 'secondary';
                $roleNames = implode(', ', array_map(fn($r) => $roleLabels[$r] ?? $r, $roles));
            ?>
            <form method="post"
                  action="<?= url('club-select/' . (int)$club['club_id']) ?>"
                  id="cf-<?= (int)$club['club_id'] ?>">
                <?= csrf_field() ?>
                <button type="submit"
                        class="btn btn-outline-<?= $color ?> w-100 text-start py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-building fs-4 text-<?= $color ?>"
                           style="width:1.5rem;text-align:center;flex-shrink:0"></i>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate">
                                <?= e($club['club_name']) ?>
                                <?php if (!empty($club['short_name'])): ?>
                                    <span class="fw-normal opacity-75 small">(<?= e($club['short_name']) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="small opacity-75"><?= e($roleNames) ?></div>
                        </div>
                        <i class="bi bi-chevron-right opacity-50 flex-shrink-0"></i>
                    </div>
                </button>
            </form>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4 pt-2" style="border-top:1px solid rgba(255,255,255,.06)">
            <a href="<?= url('auth/logout') ?>" class="small" style="color:#475569">
                <i class="bi bi-arrow-left me-1"></i>Wyloguj się
            </a>
        </div>
    </div>
</div>

<p class="text-center mt-3 mb-0" style="color:#334155;font-size:.72rem">
    &copy; <?= date('Y') ?> <?= e($systemBranding['name'] ?? 'Shootero') ?>
</p>
