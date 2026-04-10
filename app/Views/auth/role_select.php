<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <?php if (!empty($systemBranding['logo'])): ?>
                <img src="<?= url('admin/system-logo') ?>" alt="<?= e($systemBranding['name']) ?>"
                     style="height:48px; max-width:180px; object-fit:contain" class="mb-2">
            <?php else: ?>
                <i class="bi bi-bullseye text-danger" style="font-size:2.5rem"></i>
            <?php endif; ?>
            <h4 class="mt-2 mb-0 fw-bold"><?= e($systemBranding['name'] ?? 'Shootero') ?></h4>
            <p class="text-muted small">Wybierz kontekst logowania</p>
        </div>

        <p class="text-center text-muted small mb-4">
            Masz kilka uprawnień w tym klubie. Wybierz, jako kto chcesz się zalogować:
        </p>

        <?php
        $roleConfig = [
            'admin'     => ['icon' => 'shield-lock-fill',   'label' => 'Administrator',  'color' => 'danger',  'desc' => 'Pełny dostęp do systemu'],
            'zarzad'    => ['icon' => 'briefcase-fill',      'label' => 'Zarząd',         'color' => 'primary', 'desc' => 'Zarządzanie klubem i członkami'],
            'instruktor'=> ['icon' => 'person-workspace',   'label' => 'Instruktor',     'color' => 'success', 'desc' => 'Prowadzenie treningów i zawodów'],
            'sędzia'    => ['icon' => 'trophy-fill',         'label' => 'Sędzia',         'color' => 'warning', 'desc' => 'Obsługa i ocenianie zawodów'],
            'zawodnik'  => ['icon' => 'person-fill',         'label' => 'Zawodnik',       'color' => 'secondary','desc' => 'Przeglądanie wyników i harmonogramu'],
        ];
        ?>

        <div class="d-grid gap-2">
            <?php foreach ($roles as $role):
                $cfg = $roleConfig[$role] ?? ['icon' => 'person', 'label' => $role, 'color' => 'secondary', 'desc' => ''];
            ?>
            <form method="post" action="<?= url('auth/role-select') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="<?= e($role) ?>">
                <button type="submit" class="btn btn-outline-<?= $cfg['color'] ?> w-100 text-start py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-<?= $cfg['icon'] ?> fs-4 text-<?= $cfg['color'] ?>" style="width:1.5rem; text-align:center; flex-shrink:0"></i>
                        <div>
                            <div class="fw-semibold"><?= e($cfg['label']) ?></div>
                            <?php if ($cfg['desc']): ?>
                                <div class="small text-muted"><?= e($cfg['desc']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </button>
            </form>
            <?php endforeach; ?>
        </div>

        <div class="mt-3 text-center">
            <a href="<?= url('auth/logout') ?>" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Wyloguj i wróć do logowania
            </a>
        </div>
    </div>
</div>

<p class="text-center text-muted small mt-3">&copy; <?= date('Y') ?> <?= e($systemBranding['name'] ?? 'Shootero') ?></p>
