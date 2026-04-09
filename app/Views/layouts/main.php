<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Klub Strzelecki') ?> &mdash; Klub Strzelecki</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
    <?php
    // Club branding — inject CSS custom properties
    $__primaryColor = $clubBranding['primary_color'] ?? '#dc3545';
    $__navbarBg     = $clubBranding['navbar_bg']     ?? '#1a1f2e';
    $__customCss    = $clubBranding['custom_css']     ?? '';
    ?>
    <style>
        :root {
            --club-primary: <?= htmlspecialchars($__primaryColor, ENT_QUOTES) ?>;
            --club-navbar:  <?= htmlspecialchars($__navbarBg, ENT_QUOTES) ?>;
        }
        /* ── Critical layout — inlined so it works even if app.css lags ── */
        html, body { height: 100%; margin: 0; padding: 0; }
        body { display: flex; flex-direction: column; background: #f0f2f5; font-size: .92rem; }

        /* Wrapper: sidebar + page */
        #layout-wrap {
            display: flex;
            flex: 1;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 240px;
            min-width: 240px;
            background: var(--club-navbar, #1a1f2e);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
            z-index: 100;
            transition: width .22s ease, min-width .22s ease;
        }
        #sidebar.collapsed { width: 62px; min-width: 62px; }

        /* Brand */
        .sb-brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .9rem 1rem;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.08);
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
        }
        .sb-brand i { font-size: 1.35rem; color: var(--club-primary, #dc3545); flex-shrink: 0; }
        .sb-brand-text { font-weight: 700; font-size: .97rem; transition: opacity .2s; }
        #sidebar.collapsed .sb-brand-text { opacity: 0; width: 0; overflow: hidden; }

        /* Nav */
        .sb-nav { list-style: none; padding: .4rem .5rem; margin: 0; flex: 1; overflow-y: auto; overflow-x: hidden; }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 2px; }

        .sb-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .52rem .75rem;
            color: #b8c0d4;
            text-decoration: none;
            border-radius: .4rem;
            font-size: .875rem;
            white-space: nowrap;
            overflow: hidden;
            transition: background .14s, color .14s;
            margin-bottom: 2px;
        }
        .sb-link i { font-size: 1.05rem; flex-shrink: 0; width: 1.25rem; text-align: center; }
        .sb-link span { transition: opacity .2s; }
        .sb-link:hover { background: #2a3147; color: #fff; }
        .sb-link.active { background: var(--club-primary, #dc3545); color: #fff; font-weight: 600; }
        #sidebar.collapsed .sb-link { justify-content: center; padding-left: 0; padding-right: 0; }
        #sidebar.collapsed .sb-link span { opacity: 0; width: 0; overflow: hidden; }

        /* Footer */
        .sb-footer {
            border-top: 1px solid rgba(255,255,255,.08);
            padding: .7rem .9rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-shrink: 0;
            overflow: hidden;
        }
        .sb-user { display: flex; align-items: center; gap: .5rem; flex: 1; min-width: 0; }
        .sb-user > i { font-size: 1.25rem; color: #8a94a8; flex-shrink: 0; }
        .sb-user-name { font-size: .82rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-user-role { margin-top: 1px; }
        .sb-user-info { min-width: 0; overflow: hidden; transition: opacity .2s; }
        #sidebar.collapsed .sb-user-info { opacity: 0; width: 0; }
        .sb-logout { color: #7a8499; font-size: 1.1rem; text-decoration: none; padding: .2rem .3rem; border-radius: .3rem; flex-shrink: 0; transition: color .14s, background .14s; }
        .sb-logout:hover { color: #fff; background: rgba(255,255,255,.1); }
        #sidebar.collapsed .sb-logout { display: none; }

        /* Collapse toggle button */
        .sb-collapse-btn {
            background: none; border: none; color: #7a8499; font-size: 1rem;
            padding: .2rem .4rem; border-radius: .3rem; cursor: pointer; flex-shrink: 0;
            transition: color .14s, background .14s;
        }
        .sb-collapse-btn:hover { color: #fff; background: rgba(255,255,255,.1); }
        /* collapsed: button stays visible, shifts to top-center as the only footer element */
        #sidebar.collapsed .sb-footer { justify-content: center; }
        #sidebar.collapsed .sb-collapse-btn { display: flex; }

        /* ── Page area (right of sidebar) ── */
        #page-area {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        /* Top bar */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0 1.25rem;
            height: 52px;
            display: flex;
            align-items: center;
            gap: .75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .topbar-hamburger {
            background: none; border: none; font-size: 1.45rem;
            color: #555; cursor: pointer; padding: .1rem .35rem;
            border-radius: .3rem; line-height: 1;
            display: none; /* shown only on mobile via media query */
        }
        .topbar-hamburger:hover { background: #f0f0f0; }
        .topbar-title { font-weight: 600; font-size: .93rem; color: #333; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topbar-user { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: #555; }
        .topbar-user a { color: #888; font-size: 1.1rem; text-decoration: none; }
        .topbar-user a:hover { color: #dc3545; }

        /* Main content */
        #main-content {
            flex: 1;
            padding: 1.25rem 1.5rem 2.5rem;
        }
        footer.main-foot {
            text-align: center; font-size: .8rem; color: #aaa;
            padding: .75rem; border-top: 1px solid #e9ecef;
        }

        /* ── Mobile overlay ── */
        #sb-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 99;
        }
        #sb-overlay.open { display: block; }

        /* ── Mobile breakpoint ── */
        @media (max-width: 991.98px) {
            #layout-wrap { display: block; position: relative; }
            #sidebar {
                position: fixed;
                top: 0; left: 0;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform .25s ease;
                width: 240px !important;
                min-width: 240px !important;
                z-index: 200;
            }
            #sidebar.mobile-open { transform: translateX(0); }
            #sidebar.collapsed .sb-brand-text,
            #sidebar.collapsed .sb-link span,
            #sidebar.collapsed .sb-user-info { opacity: 1 !important; width: auto !important; overflow: visible !important; }
            #sidebar.collapsed .sb-link { justify-content: flex-start !important; padding-left: .75rem !important; padding-right: .75rem !important; }
            #sidebar.collapsed .sb-logout { display: flex !important; }
            #page-area { margin-left: 0 !important; }
            .topbar-hamburger { display: block; }
        }

        /* Print */
        @media print {
            #sidebar, #topbar, #sb-overlay { display: none !important; }
            #layout-wrap { display: block; }
            #page-area { margin: 0; }
            #main-content { padding: 0; }
        }
        <?php if ($__customCss): ?>
        /* Custom CSS per klub */
        <?= $__customCss ?>
        <?php endif; ?>
    </style>
</head>
<body>

<?php
use App\Models\RolePermissionModel;
$role       = $authUser['role'] ?? '';
$uri        = $_SERVER['REQUEST_URI'] ?? '';
$navModules = $navModules ?? RolePermissionModel::modulesForRole($role);

$allModules = RolePermissionModel::MODULES;

function isActive(string $mod, string $uri): bool {
    return match($mod) {
        'dashboard'     => str_contains($uri, '/dashboard'),
        'members'       => str_contains($uri, '/members'),
        'licenses'      => str_contains($uri, '/licenses'),
        'finances'      => str_contains($uri, '/finances'),
        'competitions'  => str_contains($uri, '/competitions'),
        'judges'        => str_contains($uri, '/judges'),
        'club_fees'     => str_contains($uri, '/club-fees'),
        'equipment'     => str_contains($uri, '/equipment'),
        'trainings'     => str_contains($uri, '/trainings'),
        'announcements' => str_contains($uri, '/announcements'),
        'calendar'      => str_contains($uri, '/calendar'),
        'reports'       => str_contains($uri, '/reports'),
        'config'        => str_contains($uri, '/config'),
        'security'      => str_contains($uri, '/security'),
        default         => false,
    };
}
?>

<div id="layout-wrap">

<!-- ── Sidebar ──────────────────────────────────────────────────────── -->
<nav id="sidebar">
    <a href="<?= url('dashboard') ?>" class="sb-brand">
        <?php if (!empty($clubBranding['logo_path'])): ?>
            <img src="<?= url('club/logo') ?>" alt="" style="height:26px;width:auto;flex-shrink:0">
        <?php else: ?>
            <i class="bi bi-bullseye"></i>
        <?php endif; ?>
        <span class="sb-brand-text"><?= e($clubBranding['club_name'] ?? 'Klub Strzelecki') ?></span>
    </a>

    <ul class="sb-nav">
        <?php foreach ($allModules as $mod => $cfg):
            if (!in_array($mod, $navModules, true)) continue;
            $active = isActive($mod, $uri);
        ?>
        <li>
            <a href="<?= url($cfg['url']) ?>"
               class="sb-link <?= $active ? 'active' : '' ?>">
                <i class="bi bi-<?= $cfg['icon'] ?>"></i>
                <span><?= $cfg['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sb-footer">
        <div class="sb-user">
            <i class="bi bi-person-circle"></i>
            <div class="sb-user-info">
                <div class="sb-user-name"><?= e($authUser['full_name'] ?? $authUser['username'] ?? '') ?></div>
                <div class="sb-user-role">
                    <span class="badge bg-<?= match($role) { 'admin'=>'danger','zarzad'=>'warning text-dark',default=>'secondary' } ?>" style="font-size:.68rem">
                        <?= e($role) ?>
                    </span>
                </div>
            </div>
        </div>
        <button class="sb-collapse-btn" id="desktopCollapse" title="Zwiń sidebar">
            <i class="bi bi-chevron-left"></i>
        </button>
        <a href="<?= url('2fa/setup') ?>" class="sb-logout" title="Ustawienia 2FA">
            <i class="bi bi-shield-lock"></i>
        </a>
        <a href="<?= url('auth/logout') ?>" class="sb-logout" title="Wyloguj">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>

<!-- ── Page area ────────────────────────────────────────────────────── -->
<div id="page-area">

    <!-- Top bar -->
    <div id="topbar">
        <button class="topbar-hamburger" id="mobileToggle" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-title"><?= e($title ?? '') ?></span>
        <div class="topbar-user">
            <?php
            // Notification badge (admin/zarząd only, try/catch for pre-migration safety)
            if (in_array($authUser['role'] ?? '', ['admin', 'zarzad'])) {
                try {
                    $__notifCount = (new \App\Models\NotificationModel())->countUnreadForRoles([$authUser['role']]);
                } catch (\Throwable) { $__notifCount = 0; }
                if ($__notifCount > 0):
            ?>
            <a href="<?= url('dashboard') ?>" title="<?= $__notifCount ?> nieprzeczytanych powiadomień" class="position-relative text-decoration-none me-1">
                <i class="bi bi-bell text-warning" style="font-size:1.15rem"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">
                    <?= $__notifCount ?>
                </span>
            </a>
            <?php endif; } ?>
            <?php if (!empty($isSuperAdmin)): ?>
                <a href="<?= url('admin/dashboard') ?>" class="text-decoration-none me-1" title="Panel administratora">
                    <i class="bi bi-shield-lock text-danger" style="font-size:1.1rem"></i>
                </a>
            <?php endif; ?>
            <?php if (in_array($authUser['role'] ?? '', ['admin', 'zarzad'])): ?>
                <a href="<?= url('club/settings') ?>" class="text-decoration-none me-1" title="Ustawienia klubu">
                    <i class="bi bi-building" style="font-size:1rem"></i>
                </a>
            <?php endif; ?>
            <span class="d-none d-md-inline"><?= e($authUser['full_name'] ?? '') ?></span>
            <a href="<?= url('auth/logout') ?>" title="Wyloguj"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>

    <!-- Trial period banner -->
    <?php
    try {
        $__clubId = \App\Helpers\ClubContext::current();
        if ($__clubId) {
            $__sub = \App\Helpers\Database::pdo()
                ->prepare("SELECT plan, valid_until, status FROM club_subscriptions WHERE club_id = ? LIMIT 1");
            $__sub->execute([$__clubId]);
            $__subscription = $__sub->fetch();
            if ($__subscription && $__subscription['plan'] === 'trial' && $__subscription['valid_until']) {
                $__daysLeft = (int)ceil((strtotime($__subscription['valid_until']) - time()) / 86400);
                if ($__daysLeft >= 0):
    ?>
    <div class="alert alert-warning alert-dismissible rounded-0 mb-0 py-2 px-3 small"
         style="border-left:4px solid #ffc107;border-radius:0!important">
        <i class="bi bi-clock"></i>
        <strong>Okres próbny:</strong> pozostało <?= $__daysLeft ?> dni.
        Skontaktuj się z administratorem systemu, aby wybrać plan.
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
    <?php
                elseif ($__daysLeft < 0 && $__subscription['status'] === 'active'):
    ?>
    <div class="alert alert-danger rounded-0 mb-0 py-2 px-3 small"
         style="border-left:4px solid #dc3545;border-radius:0!important">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Okres próbny wygasł.</strong> Skontaktuj się z administratorem systemu, aby przedłużyć dostęp.
    </div>
    <?php       endif;
            }
        }
    } catch (\Throwable) { /* table may not exist before migration */ }
    ?>

    <!-- Impersonation banner -->
    <?php if (\App\Helpers\Auth::isImpersonating()): ?>
    <div class="alert alert-danger mb-0 rounded-0 py-2 text-center" style="position:sticky;top:0;z-index:1050">
        <i class="bi bi-person-fill-exclamation"></i>
        <strong>TRYB IMPERSONACJI</strong> — przeglądasz system jako inny użytkownik.
        <a href="<?= url('admin/stop-impersonation') ?>" class="btn btn-sm btn-danger ms-3">
            <i class="bi bi-x-circle"></i> Zakończ
        </a>
    </div>
    <?php endif; ?>

    <!-- Flash messages -->
    <div style="padding: .75rem 1.5rem 0">
        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $flashSuccess ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= e($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashWarning)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= $flashWarning ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ads banner (club_ui) -->
    <?php $adsTarget = 'club_ui'; include ROOT_PATH . '/app/Views/partials/ads_banner.php'; ?>

    <!-- Main content -->
    <main id="main-content">
        <?= $content ?>
    </main>

    <footer class="main-foot">
        &copy; <?= date('Y') ?> <?= e($clubBranding['club_name'] ?? 'Klub Strzelecki') ?> &mdash; System zarządzania
    </footer>

</div><!-- /page-area -->
</div><!-- /layout-wrap -->

<!-- Mobile overlay -->
<div id="sb-overlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('js/app.js') ?>"></script>
<script>
(function () {
    var sidebar   = document.getElementById('sidebar');
    var overlay   = document.getElementById('sb-overlay');
    var mobToggle = document.getElementById('mobileToggle');
    var dskToggle = document.getElementById('desktopCollapse');

    // Mobile open/close
    function openMobile()  { sidebar.classList.add('mobile-open');    overlay.classList.add('open'); }
    function closeMobile() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('open'); }

    mobToggle && mobToggle.addEventListener('click', function () {
        sidebar.classList.contains('mobile-open') ? closeMobile() : openMobile();
    });
    overlay.addEventListener('click', closeMobile);

    // Desktop collapse (icon-only mode)
    function updateCollapseBtn() {
        if (!dskToggle) return;
        var isCollapsed = sidebar.classList.contains('collapsed');
        dskToggle.querySelector('i').className = isCollapsed
            ? 'bi bi-chevron-right'
            : 'bi bi-chevron-left';
        dskToggle.title = isCollapsed ? 'Rozwiń menu' : 'Zwiń menu';
    }

    var collapsed = localStorage.getItem('sbCollapsed') === '1';
    if (collapsed) sidebar.classList.add('collapsed');
    updateCollapseBtn();

    dskToggle && dskToggle.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sbCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
        updateCollapseBtn();
    });
})();
</script>
</body>
</html>
