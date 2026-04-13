<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Shootero') ?> &mdash; <?= e($appName ?? 'Shootero') ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Shootero design system -->
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
    <?php
    $__primaryColor = $clubBranding['primary_color'] ?? '#D4A373';
    $__navbarBg     = $clubBranding['navbar_bg']     ?? '#0F172A';
    $__customCss    = $clubBranding['custom_css']     ?? '';
    ?>
    <style>
        :root {
            --club-primary: <?= htmlspecialchars($__primaryColor, ENT_QUOTES) ?>;
            --club-navbar:  <?= htmlspecialchars($__navbarBg, ENT_QUOTES) ?>;
            /* Shootero palette — brand spec 2026 */
            --sht-900: #081220;        /* Navy 500 */
            --sht-800: #0F172A;        /* Navy 600 */
            --sht-700: #1E2838;        /* Navy 700 */
            --sht-gold: #D4A373;       /* Gold 500 */
            --sht-gold-bright: #E6C200;/* Gold 400 */
            --sht-gold-soft: #F3E9DC;  /* Gold Soft */
            --sht-muted: #94A3B8;      /* Gray 400 */
            --sht-dim: #475569;        /* Gray 600 */
        }

        /* ── Layout ── */
        html, body { margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--sht-900);
            color: #e2e8f0;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        #layout-wrap {
            display: flex;
            flex: 1;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 240px;
            min-width: 240px;
            background: var(--club-navbar, var(--sht-800));
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 100;
            transition: width .22s ease, min-width .22s ease;
            border-right: 1px solid rgba(255,255,255,.05);
            align-self: stretch;
        }
        #sidebar.collapsed { width: 62px; min-width: 62px; }

        /* Brand */
        .sb-brand {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: 1rem 1rem .9rem;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.06);
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
        }
        .sb-brand-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: opacity .2s;
            color: #fff;
        }
        #sidebar.collapsed .sb-brand-text { opacity: 0; width: 0; overflow: hidden; }

        /* Shootero crosshair icon */
        .sb-shootero-icon {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
        }

        /* Nav */
        .sb-nav { list-style: none; padding: .5rem .5rem; margin: 0; flex: 1; overflow-x: hidden; }

        .sb-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .54rem .75rem;
            color: var(--sht-muted);
            text-decoration: none;
            border-radius: .45rem;
            font-family: 'Inter', sans-serif;
            font-size: .875rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            transition: background .14s, color .14s;
            margin-bottom: 2px;
        }
        .sb-link i {
            font-size: 1.05rem;
            flex-shrink: 0;
            width: 1.25rem;
            text-align: center;
            transition: color .14s;
        }
        .sb-link span { transition: opacity .2s; }
        .sb-link:hover {
            background: rgba(212,163,115,.1);
            color: var(--sht-gold);
        }
        .sb-link:hover i { color: var(--sht-gold); }
        .sb-link.active {
            background: linear-gradient(135deg, rgba(212,163,115,.18) 0%, rgba(230,194,0,.12) 100%);
            color: var(--sht-gold);
            font-weight: 600;
            border-left: 3px solid var(--sht-gold);
            padding-left: calc(.75rem - 3px);
        }
        .sb-link.active i { color: var(--sht-gold); }

        #sidebar.collapsed .sb-link { justify-content: center; padding-left: 0; padding-right: 0; }
        #sidebar.collapsed .sb-link.active { padding-left: 0; border-left: none; border-bottom: 2px solid var(--sht-gold); }
        #sidebar.collapsed .sb-link span { opacity: 0; width: 0; overflow: hidden; }

        /* Nav section label */
        .sb-section {
            font-family: 'Poppins', sans-serif;
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--sht-dim);
            padding: .9rem .75rem .3rem;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity .2s;
        }
        #sidebar.collapsed .sb-section { opacity: 0; height: 0; padding: 0; overflow: hidden; }

        /* Footer */
        .sb-footer {
            border-top: 1px solid rgba(255,255,255,.06);
            padding: .7rem .9rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-shrink: 0;
            overflow: hidden;
            background: rgba(255,255,255,.01);
        }
        .sb-user { display: flex; align-items: center; gap: .5rem; flex: 1; min-width: 0; }
        .sb-user > i { font-size: 1.3rem; color: var(--sht-muted); flex-shrink: 0; }
        .sb-user-name {
            font-family: 'Poppins', sans-serif;
            font-size: .82rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sb-user-role { margin-top: 1px; }
        .sb-user-info { min-width: 0; overflow: hidden; transition: opacity .2s; }
        #sidebar.collapsed .sb-user-info { opacity: 0; width: 0; }
        .sb-action-btn {
            color: var(--sht-dim);
            font-size: 1.05rem;
            text-decoration: none;
            padding: .2rem .3rem;
            border-radius: .3rem;
            flex-shrink: 0;
            transition: color .14s, background .14s;
        }
        .sb-action-btn:hover { color: var(--sht-gold); background: rgba(212,163,115,.1); }
        .sb-logout { color: var(--sht-dim); font-size: 1.05rem; text-decoration: none; padding: .2rem .3rem; border-radius: .3rem; flex-shrink: 0; transition: color .14s, background .14s; }
        .sb-logout:hover { color: #fca5a5; background: rgba(220,38,38,.1); }
        #sidebar.collapsed .sb-logout { display: none; }
        #sidebar.collapsed .sb-action-btn { display: none; }

        /* Collapse button */
        .sb-collapse-btn {
            background: none;
            border: none;
            color: var(--sht-dim);
            font-size: .95rem;
            padding: .2rem .4rem;
            border-radius: .3rem;
            cursor: pointer;
            flex-shrink: 0;
            transition: color .14s, background .14s;
        }
        .sb-collapse-btn:hover { color: var(--sht-gold); background: rgba(212,163,115,.1); }
        #sidebar.collapsed .sb-footer { justify-content: center; }
        #sidebar.collapsed .sb-collapse-btn { display: flex; }

        /* ── Page area ── */
        #page-area {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--sht-900);
        }

        /* Top bar */
        #topbar {
            background: var(--sht-800);
            border-bottom: 1px solid rgba(255,255,255,.06);
            padding: 0 1.25rem;
            height: 52px;
            display: flex;
            align-items: center;
            gap: .75rem;
            position: sticky;
            top: 0;
            z-index: 99;
        }
        .topbar-hamburger {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--sht-muted);
            cursor: pointer;
            padding: .1rem .35rem;
            border-radius: .35rem;
            line-height: 1;
            display: none;
        }
        .topbar-hamburger:hover { background: rgba(255,255,255,.07); color: #fff; }
        .topbar-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: .93rem;
            color: #e2e8f0;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .85rem;
            color: var(--sht-muted);
        }
        .topbar-user a {
            color: var(--sht-dim);
            font-size: 1.1rem;
            text-decoration: none;
            padding: .2rem .3rem;
            border-radius: .3rem;
            transition: color .14s, background .14s;
        }
        .topbar-user a:hover { color: var(--sht-gold); background: rgba(212,163,115,.1); }
        .topbar-user a.text-danger:hover { color: #fca5a5 !important; background: rgba(220,38,38,.1); }

        /* Main content */
        #main-content {
            flex: 1;
            padding: 1.35rem 1.5rem 2.5rem;
        }
        footer.main-foot {
            text-align: center;
            font-size: .8rem;
            color: var(--sht-dim);
            padding: .75rem;
            border-top: 1px solid rgba(255,255,255,.06);
            background: var(--sht-800);
            font-family: 'Inter', sans-serif;
        }

        /* ── Mobile overlay ── */
        #sb-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            backdrop-filter: blur(2px);
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
                overflow-y: auto;
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
            #sidebar.collapsed .sb-link.active { border-left: 3px solid var(--sht-gold); padding-left: calc(.75rem - 3px) !important; border-bottom: none; }
            #sidebar.collapsed .sb-logout { display: flex !important; }
            #sidebar.collapsed .sb-action-btn { display: flex !important; }
            #sidebar.collapsed .sb-section { opacity: 1 !important; height: auto !important; padding: .9rem .75rem .3rem !important; }
            #page-area { margin-left: 0 !important; }
            .topbar-hamburger { display: block; }
        }

        /* ── Print ── */
        @media print {
            #sidebar, #topbar, #sb-overlay { display: none !important; }
            #layout-wrap { display: block; }
            #page-area { background: #fff; }
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

// Shootero brand icon SVG — brand spec 2026
$shooteroIcon = '<svg class="sb-shootero-icon" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
  <!-- Upper-left S-bolt blade (wide angular) -->
  <path d="M14 1 L1 7 L7 19 L22 12 Z" fill="#D4A373"/>
  <!-- Lower-right S-bolt blade (wide angular) -->
  <path d="M26 39 L39 33 L33 21 L18 28 Z" fill="#D4A373"/>
  <!-- Outer metallic ring (with depth fill) -->
  <circle cx="20" cy="20" r="12" stroke="rgba(200,218,232,.55)" stroke-width="2.5" fill="rgba(8,18,32,.35)"/>
  <!-- Metallic highlight arc upper-right -->
  <path d="M28.5 11.5 A12 12 0 0 1 32 20" stroke="rgba(255,255,255,.88)" stroke-width="2" fill="none" stroke-linecap="round"/>
  <!-- Inner ring -->
  <circle cx="20" cy="20" r="7.5" stroke="rgba(226,232,240,.9)" stroke-width="2" fill="none"/>
  <!-- Crosshair ticks between rings -->
  <line x1="20" y1="8.5"  x2="20" y2="12.5" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="20" y1="27.5" x2="20" y2="31.5" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="8.5"  y1="20" x2="12.5" y2="20" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="27.5" y1="20" x2="31.5" y2="20" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <!-- Inner crosshair lines (inside inner ring) -->
  <line x1="20" y1="14"  x2="20" y2="17"  stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <line x1="20" y1="23"  x2="20" y2="26"  stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <line x1="14"  y1="20" x2="17"  y2="20" stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <line x1="23"  y1="20" x2="26"  y2="20" stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <!-- Center dot (gold + bright highlight) -->
  <circle cx="20" cy="20" r="4" fill="#D4A373"/>
  <circle cx="20" cy="20" r="2" fill="#E6C200"/>
</svg>';
?>

<div id="layout-wrap">

<!-- ── Sidebar ────────────────────────────────────────────── -->
<?php
$__isSuperAdminNav = !empty($isSuperAdmin);
$__hasClubCtx      = \App\Helpers\ClubContext::current() !== null;
$__brandHref       = ($__isSuperAdminNav && !$__hasClubCtx) ? url('admin/dashboard') : url('dashboard');
$__brandText       = $__hasClubCtx
    ? e($clubBranding['club_name'] ?? ($appName ?? 'Shootero'))
    : e($appName ?? 'Shootero');
?>
<nav id="sidebar">
    <a href="<?= $__brandHref ?>" class="sb-brand">
        <?php if (!empty($clubBranding['logo_path']) && $__hasClubCtx): ?>
            <img src="<?= url('club/logo') ?>" alt="" style="height:26px;width:auto;flex-shrink:0">
        <?php else: ?>
            <?php if (!empty($systemBranding['logo'])): ?>
                <img src="<?= url('admin/system-logo') ?>?v=<?= $systemBranding['logoMts'] ?? '0' ?>" alt="" style="height:26px;max-width:80px;object-fit:contain;flex-shrink:0">
            <?php else: ?>
                <?= $shooteroIcon ?>
            <?php endif; ?>
        <?php endif; ?>
        <span class="sb-brand-text"><?= $__brandText ?></span>
    </a>

    <?php if ($__isSuperAdminNav && !$__hasClubCtx): ?>
    <!-- Admin nav (superadmin) -->
    <div class="sb-section">Administracja</div>
    <ul class="sb-nav">
        <?php
        $__adminNav = [
            ['icon' => 'speedometer2',        'label' => 'Dashboard',      'url' => 'admin/dashboard',    'match' => '/admin/dashboard'],
            ['icon' => 'building',             'label' => 'Kluby',          'url' => 'admin/clubs',        'match' => '/admin/clubs'],
            ['icon' => 'people',               'label' => 'Użytkownicy',    'url' => 'admin/users',        'match' => '/admin/users'],
            ['icon' => 'book',                 'label' => 'Słowniki',       'url' => 'config/disciplines', 'match' => '/config/'],
            ['icon' => 'joystick',             'label' => 'Demo',           'url' => 'admin/demos',        'match' => '/admin/demos'],
            ['icon' => 'credit-card-2-front',  'label' => 'Subskrypcje',   'url' => 'admin/subscriptions','match' => '/admin/subscriptions'],
            ['icon' => 'bar-chart-line',       'label' => 'Analityka',      'url' => 'admin/analytics',    'match' => '/admin/analytics'],
            ['icon' => 'megaphone',            'label' => 'Reklamy',        'url' => 'admin/ads',          'match' => '/admin/ads'],
            ['icon' => 'gear',                 'label' => 'Ustawienia',     'url' => 'admin/settings',     'match' => '/admin/settings'],
            ['icon' => 'cash-coin',            'label' => 'Płatności P24',  'url' => 'admin/online-payments', 'match' => '/admin/online-payments'],
            ['icon' => 'shield-check',         'label' => 'Bezpieczeństwo', 'url' => 'admin/security',     'match' => '/admin/security'],
            ['icon' => 'cloud-arrow-down',     'label' => 'Kopie zapasowe', 'url' => 'admin/backups',      'match' => '/admin/backups'],
        ];
        foreach ($__adminNav as $__item):
            $__aActive = str_contains($uri, $__item['match']);
        ?>
        <li>
            <a href="<?= url($__item['url']) ?>" class="sb-link <?= $__aActive ? 'active' : '' ?>">
                <i class="bi bi-<?= $__item['icon'] ?>"></i>
                <span><?= $__item['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php elseif ($__isSuperAdminNav && $__hasClubCtx): ?>
    <!-- Admin managing club -->
    <div class="sb-section">Klub</div>
    <ul class="sb-nav">
        <?php
        $__clubAdminNav = [
            ['icon' => 'speedometer2',  'label' => 'Dashboard',    'url' => 'dashboard',            'match' => '/dashboard'],
            ['icon' => 'people',        'label' => 'Zawodnicy',    'url' => 'members',              'match' => '/members'],
            ['icon' => 'gear',          'label' => 'Konfiguracja', 'url' => 'config',               'match' => '/config'],
            ['icon' => 'bell',          'label' => 'Powiadomienia','url' => 'config/notifications', 'match' => '/config/notifications'],
        ];
        foreach ($__clubAdminNav as $__item):
            $__aActive = str_contains($uri, $__item['match']);
        ?>
        <li>
            <a href="<?= url($__item['url']) ?>" class="sb-link <?= $__aActive ? 'active' : '' ?>">
                <i class="bi bi-<?= $__item['icon'] ?>"></i>
                <span><?= $__item['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        <li><hr style="border-color:rgba(255,255,255,.07);margin:.4rem .75rem"></li>
        <li>
            <a href="<?= url('admin/exit-club') ?>" class="sb-link" style="color:#fca5a5">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Panel admina</span>
            </a>
        </li>
    </ul>

    <?php else: ?>
    <!-- Club nav -->
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
    <?php endif; ?>

    <div class="sb-footer">
        <div class="sb-user">
            <i class="bi bi-person-circle"></i>
            <div class="sb-user-info">
                <div class="sb-user-name"><?= e($authUser['full_name'] ?? $authUser['username'] ?? '') ?></div>
                <div class="sb-user-role">
                    <span class="badge" style="font-size:.65rem;background:rgba(212,163,115,.15);color:#D4A373;border:1px solid rgba(212,163,115,.2)">
                        <?= e($role) ?>
                    </span>
                </div>
            </div>
        </div>
        <button class="sb-collapse-btn" id="desktopCollapse" title="Zwiń sidebar">
            <i class="bi bi-chevron-left"></i>
        </button>
        <a href="<?= url('2fa/setup') ?>" class="sb-action-btn" title="Ustawienia 2FA">
            <i class="bi bi-shield-lock"></i>
        </a>
        <a href="<?= url('auth/logout') ?>" class="sb-logout" title="Wyloguj">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>

<!-- ── Page area ─────────────────────────────────────────── -->
<div id="page-area">

    <!-- Top bar -->
    <div id="topbar">
        <button class="topbar-hamburger" id="mobileToggle" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-title"><?= e($title ?? '') ?></span>
        <div class="topbar-user">
            <?php
            if (in_array($authUser['role'] ?? '', ['admin', 'zarzad'])) {
                try {
                    $__notifCount = (new \App\Models\NotificationModel())->countUnreadForRoles([$authUser['role']]);
                } catch (\Throwable) { $__notifCount = 0; }
                if ($__notifCount > 0):
            ?>
            <a href="<?= url('dashboard') ?>" title="<?= $__notifCount ?> powiadomień" class="position-relative text-decoration-none">
                <i class="bi bi-bell" style="font-size:1.1rem;color:#D4A373"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.58rem">
                    <?= $__notifCount ?>
                </span>
            </a>
            <?php endif; } ?>
            <?php if (!empty($isSuperAdmin)): ?>
                <a href="<?= url($__hasClubCtx ? 'admin/exit-club' : 'admin/dashboard') ?>" title="Panel administratora">
                    <i class="bi bi-shield-lock text-danger" style="font-size:1.05rem"></i>
                </a>
            <?php endif; ?>
            <?php if (in_array($authUser['role'] ?? '', ['admin', 'zarzad'])): ?>
                <a href="<?= url('club/settings') ?>" title="Ustawienia klubu">
                    <i class="bi bi-building" style="font-size:1rem"></i>
                </a>
            <?php endif; ?>
            <span class="d-none d-md-inline" style="color:#94A3B8"><?= e($authUser['full_name'] ?? '') ?></span>
            <a href="<?= url('auth/logout') ?>" title="Wyloguj">
                <i class="bi bi-box-arrow-right"></i>
            </a>
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
         style="border-radius:0!important;border-left:4px solid #E6C200">
        <i class="bi bi-clock-history me-1"></i>
        <strong>Okres próbny:</strong> pozostało <?= $__daysLeft ?> dni. Skontaktuj się z administratorem.
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
    <?php
                elseif ($__daysLeft < 0 && $__subscription['status'] === 'active'):
    ?>
    <div class="alert alert-danger rounded-0 mb-0 py-2 px-3 small" style="border-radius:0!important">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Okres próbny wygasł.</strong> Skontaktuj się z administratorem systemu.
    </div>
    <?php       endif;
            }
        }
    } catch (\Throwable) {}
    ?>

    <!-- Impersonation banner -->
    <?php if (\App\Helpers\Auth::isImpersonating()): ?>
    <div class="alert alert-danger mb-0 rounded-0 py-2 text-center small fw-semibold"
         style="position:sticky;top:0;z-index:1050;background:rgba(220,38,38,.15);border-color:rgba(220,38,38,.4);color:#fca5a5">
        <i class="bi bi-person-fill-exclamation me-1"></i>
        TRYB IMPERSONACJI — przeglądasz system jako inny użytkownik.
        <a href="<?= url('admin/stop-impersonation') ?>" class="btn btn-sm btn-danger ms-3 py-0">
            <i class="bi bi-x-circle"></i> Zakończ
        </a>
    </div>
    <?php endif; ?>

    <!-- Flash messages -->
    <div style="padding: .75rem 1.5rem 0">
        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i> <?= $flashSuccess ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i> <?= e($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashWarning)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i> <?= $flashWarning ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ads banner -->
    <?php $adsTarget = 'club_ui'; include ROOT_PATH . '/app/Views/partials/ads_banner.php'; ?>

    <!-- Main content -->
    <main id="main-content">
        <?= $content ?>
    </main>

    <footer class="main-foot">
        <span style="font-family:'Poppins',sans-serif;font-weight:800;letter-spacing:2px;color:#D4A373;font-size:.78rem">SHOOTERO</span>
        <span style="color:#334155;margin:0 .4rem">&mdash;</span>
        <span style="font-family:'Poppins',sans-serif;font-weight:500;letter-spacing:1.5px;font-size:.7rem;color:#475569;text-transform:uppercase">ZARZĄDZAJ&nbsp;KLUBEM.&nbsp;WSPIERAJ&nbsp;LUDZI.</span>
        <span style="color:#1e293b;margin-left:.5rem;font-size:.7rem">&copy; <?= date('Y') ?></span>
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

    function openMobile()  { sidebar.classList.add('mobile-open');    overlay.classList.add('open'); }
    function closeMobile() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('open'); }

    mobToggle && mobToggle.addEventListener('click', function () {
        sidebar.classList.contains('mobile-open') ? closeMobile() : openMobile();
    });
    overlay.addEventListener('click', closeMobile);

    function updateCollapseBtn() {
        if (!dskToggle) return;
        var isCollapsed = sidebar.classList.contains('collapsed');
        dskToggle.querySelector('i').className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
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
