<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Klub Strzelecki') ?> &mdash; Klub Strzelecki</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body>

<?php
use App\Models\RolePermissionModel;
$role       = $authUser['role'] ?? '';
$uri        = $_SERVER['REQUEST_URI'] ?? '';
$navModules = $navModules ?? RolePermissionModel::modulesForRole($role);

$allModules = RolePermissionModel::MODULES;

// Build nav items — only modules this role can access
$navItems = [];
foreach ($allModules as $mod => $cfg) {
    if (in_array($mod, $navModules, true)) {
        $navItems[$mod] = $cfg;
    }
}
?>

<!-- ── Sidebar ──────────────────────────────────────────────────────── -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <a href="<?= url('dashboard') ?>" class="sidebar-brand-link">
            <i class="bi bi-bullseye"></i>
            <span class="sidebar-brand-text">Klub Strzelecki</span>
        </a>
    </div>

    <ul class="sidebar-nav">
        <?php foreach ($navItems as $mod => $cfg): ?>
        <?php
            $active = match($mod) {
                'dashboard'    => str_contains($uri, '/dashboard'),
                'members'      => str_contains($uri, '/members') && !str_contains($uri, '/medical'),
                'licenses'     => str_contains($uri, '/licenses'),
                'finances'     => str_contains($uri, '/finances'),
                'competitions' => str_contains($uri, '/competitions'),
                'judges'       => str_contains($uri, '/judges'),
                'club_fees'    => str_contains($uri, '/club-fees'),
                'reports'      => str_contains($uri, '/reports'),
                'config'       => str_contains($uri, '/config'),
                default        => false,
            };
        ?>
        <li class="sidebar-nav-item">
            <a href="<?= url($cfg['url']) ?>"
               class="sidebar-nav-link <?= $active ? 'active' : '' ?>">
                <i class="bi bi-<?= $cfg['icon'] ?>"></i>
                <span><?= $cfg['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <i class="bi bi-person-circle"></i>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($authUser['full_name'] ?? $authUser['username'] ?? '') ?></div>
                <div class="sidebar-user-role">
                    <span class="badge bg-<?= match($role) { 'admin'=>'danger','zarzad'=>'warning text-dark', default=>'secondary' } ?>">
                        <?= e($role) ?>
                    </span>
                </div>
            </div>
        </div>
        <a href="<?= url('auth/logout') ?>" class="sidebar-logout" title="Wyloguj">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>

<!-- ── Top bar (mobile toggle + page title) ─────────────────────────── -->
<div id="topbar">
    <button id="sidebarToggle" class="topbar-toggle" aria-label="Menu">
        <i class="bi bi-list"></i>
    </button>
    <span class="topbar-title"><?= e($title ?? '') ?></span>
    <a href="<?= url('auth/logout') ?>" class="topbar-logout d-lg-none" title="Wyloguj">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</div>

<!-- Overlay for mobile -->
<div id="sidebarOverlay"></div>

<!-- ── Main content ─────────────────────────────────────────────────── -->
<div id="mainContent">

    <!-- Flash messages -->
    <div class="flash-wrap">
        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle"></i> <?= e($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= e($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashWarning)): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= e($flashWarning) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <main class="main-inner">
        <?= $content ?>
    </main>

    <footer class="main-footer">
        &copy; <?= date('Y') ?> Klub Strzelecki &mdash; System zarządzania v1.0
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('js/app.js') ?>"></script>
<script>
(function () {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggle   = document.getElementById('sidebarToggle');
    const COLLAPSED = 'sidebar-collapsed';

    function open()  { sidebar.classList.add('open');  overlay.classList.add('open'); }
    function close() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }

    toggle.addEventListener('click', () => sidebar.classList.contains('open') ? close() : open());
    overlay.addEventListener('click', close);

    // Restore collapsed state on desktop
    if (localStorage.getItem('sidebarCollapsed') === '1') {
        document.body.classList.add(COLLAPSED);
    }
    // Double-click brand to collapse on desktop
    document.querySelector('.sidebar-brand-link')?.addEventListener('dblclick', () => {
        document.body.classList.toggle(COLLAPSED);
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains(COLLAPSED) ? '1' : '0');
    });
})();
</script>
</body>
</html>
