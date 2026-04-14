<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Portal Zawodnika') ?> &mdash; Shootero</title>
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
    <script>(function(){var t=localStorage.getItem('theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-bs-theme',t);})();</script>
    <style>
        :root {
            --sht-900: #081220;
            --sht-800: #0F172A;
            --sht-700: #1E2838;
            --sht-gold: #D4A373;
            --sht-gold-bright: #E6C200;
            --sht-muted: #94A3B8;
            --sht-dim: #475569;
        }

        html, body {
            height: 100%;
            margin: 0;
            background: var(--sht-900);
            color: #e2e8f0;
            font-family: 'Inter', -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Navbar ── */
        .portal-navbar {
            background: var(--sht-800);
            border-bottom: 1px solid rgba(255,255,255,.06);
            padding: .5rem 0;
        }
        .portal-navbar .navbar-brand {
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: .98rem;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
        }
        .portal-navbar .navbar-brand svg { flex-shrink: 0; }

        .portal-navbar .nav-link {
            color: var(--sht-muted);
            font-family: 'Inter', sans-serif;
            font-size: .875rem;
            font-weight: 500;
            padding: .4rem .75rem;
            border-radius: .35rem;
            transition: color .14s, background .14s;
        }
        .portal-navbar .nav-link:hover {
            color: var(--sht-gold);
            background: rgba(212,163,115,.08);
        }
        .portal-navbar .nav-link.active {
            color: var(--sht-gold);
            background: rgba(212,163,115,.13);
            font-weight: 600;
        }
        .portal-navbar .nav-link i { margin-right: .3rem; }

        .portal-navbar .navbar-toggler {
            border-color: rgba(255,255,255,.15);
            color: var(--sht-muted);
        }
        .portal-navbar .navbar-toggler-icon {
            filter: invert(1);
            opacity: .7;
        }

        /* Member badge */
        .portal-member-badge {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: var(--sht-muted);
            font-size: .82rem;
        }
        .portal-member-badge i {
            color: var(--sht-gold);
            font-size: 1.1rem;
        }

        /* ── Main ── */
        .portal-main {
            min-height: calc(100vh - 56px - 52px);
            padding: 1.75rem 1.5rem;
        }

        /* ── Footer ── */
        .portal-foot {
            background: var(--sht-800);
            border-top: 1px solid rgba(255,255,255,.06);
            padding: .85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .portal-foot-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: .82rem;
            color: var(--sht-gold);
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .portal-foot-copy {
            font-size: .75rem;
            color: var(--sht-dim);
        }
        .portal-foot-tagline {
            font-size: .72rem;
            color: var(--sht-dim);
            font-style: italic;
        }

        /* Theme toggle in navbar */
        .portal-theme-btn {
            background: none;
            border: 1px solid rgba(255,255,255,.15);
            color: var(--sht-muted);
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .9rem;
            transition: background .15s, color .15s, border-color .15s;
            flex-shrink: 0;
        }
        .portal-theme-btn:hover { background: rgba(212,163,115,.12); color: var(--sht-gold); border-color: rgba(212,163,115,.3); }

        /* Light mode overrides */
        [data-bs-theme="light"] body { background: #F1F5F9; color: #1E293B; }
        [data-bs-theme="light"] .portal-main { background: #F1F5F9; }
        [data-bs-theme="light"] .portal-theme-btn {
            border-color: rgba(0,0,0,.15);
            color: #64748B;
        }
        [data-bs-theme="light"] .portal-theme-btn:hover { background: rgba(139,69,19,.08); color: #8B4513; border-color: rgba(139,69,19,.25); }
    </style>
</head>
<body>

<?php
// Shootero brand icon — brand spec 2026 (portal navbar, 26px)
$__portalIcon = '<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px;flex-shrink:0">
  <path d="M14 1 L1 7 L7 19 L22 12 Z" fill="#D4A373"/>
  <path d="M26 39 L39 33 L33 21 L18 28 Z" fill="#D4A373"/>
  <circle cx="20" cy="20" r="12" stroke="rgba(200,218,232,.55)" stroke-width="2.5" fill="rgba(8,18,32,.35)"/>
  <path d="M28.5 11.5 A12 12 0 0 1 32 20" stroke="rgba(255,255,255,.88)" stroke-width="2" fill="none" stroke-linecap="round"/>
  <circle cx="20" cy="20" r="7.5" stroke="rgba(226,232,240,.9)" stroke-width="2" fill="none"/>
  <line x1="20" y1="8.5"  x2="20" y2="12.5" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="20" y1="27.5" x2="20" y2="31.5" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="8.5"  y1="20" x2="12.5" y2="20" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="27.5" y1="20" x2="31.5" y2="20" stroke="rgba(226,232,240,.92)" stroke-width="1.8" stroke-linecap="round"/>
  <line x1="20" y1="14"  x2="20" y2="17"  stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <line x1="20" y1="23"  x2="20" y2="26"  stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <line x1="14"  y1="20" x2="17"  y2="20" stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <line x1="23"  y1="20" x2="26"  y2="20" stroke="rgba(255,255,255,.65)" stroke-width="1.2" stroke-linecap="round"/>
  <circle cx="20" cy="20" r="4" fill="#D4A373"/>
  <circle cx="20" cy="20" r="2" fill="#E6C200"/>
</svg>';
$__uri = $_SERVER['REQUEST_URI'] ?? '';
?>

<nav class="navbar navbar-expand-md portal-navbar">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="<?= url('portal') ?>">
            <?= $__portalIcon ?>
            <span style="font-weight:800;letter-spacing:2px;font-size:.92rem">SHOOTERO</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav" aria-controls="portalNav" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="portalNav">
            <?php if (isset($memberUser)): ?>
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= (str_ends_with(rtrim($__uri, '/'), '/portal') || str_ends_with($__uri, '/portal/dashboard')) ? 'active' : '' ?>"
                       href="<?= url('portal') ?>">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/profile') ? 'active' : '' ?>"
                       href="<?= url('portal/profile') ?>">
                        <i class="bi bi-person-circle"></i>Mój profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/exams') ? 'active' : '' ?>"
                       href="<?= url('portal/exams') ?>">
                        <i class="bi bi-heart-pulse"></i>Badania
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/competitions') ? 'active' : '' ?>"
                       href="<?= url('portal/competitions') ?>">
                        <i class="bi bi-trophy"></i>Zawody
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/results') ? 'active' : '' ?>"
                       href="<?= url('portal/results') ?>">
                        <i class="bi bi-bar-chart-line"></i>Wyniki
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/fees') ? 'active' : '' ?>"
                       href="<?= url('portal/fees') ?>">
                        <i class="bi bi-cash-stack"></i>Opłaty
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/weapons') ? 'active' : '' ?>"
                       href="<?= url('portal/weapons') ?>">
                        <i class="bi bi-shield-lock"></i>Broń
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($__uri, '/portal/trainings') ? 'active' : '' ?>"
                       href="<?= url('portal/trainings') ?>">
                        <i class="bi bi-calendar-check"></i>Treningi
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3 ms-3">
                <div class="portal-member-badge d-none d-lg-flex">
                    <i class="bi bi-person-fill"></i>
                    <span><?= e($memberUser['full_name'] ?? '') ?></span>
                </div>
                <button class="portal-theme-btn" id="portalThemeToggle" title="Zmień motyw">
                    <i class="bi bi-sun-fill" id="portalThemeIcon"></i>
                </button>
                <a href="<?= url('portal/logout') ?>"
                   class="btn btn-sm btn-outline-secondary"
                   style="border-color:rgba(255,255,255,.15);color:var(--sht-muted)">
                    <i class="bi bi-box-arrow-right me-1"></i>Wyloguj
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="portal-main container-fluid">

    <?php if (\App\Helpers\Auth::isImpersonating()): ?>
    <div class="alert mb-0 rounded-0 py-2 text-center small fw-semibold"
         style="position:sticky;top:0;z-index:1050;background:rgba(220,38,38,.15);border:none;border-bottom:1px solid rgba(220,38,38,.4);color:#fca5a5">
        <i class="bi bi-person-fill-exclamation me-1"></i>
        TRYB IMPERSONACJI — przeglądasz portal jako
        <strong><?= e(\App\Helpers\Session::get('member_full_name', 'zawodnik')) ?></strong>.
        <a href="<?= url('admin/stop-impersonation') ?>" class="btn btn-sm btn-danger ms-3 py-0">
            <i class="bi bi-x-circle"></i> Zakończ — wróć do admina
        </a>
    </div>
    <?php endif; ?>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show mt-0 mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $flashSuccess ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-0 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashWarning)): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-0 mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= $flashWarning ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashInfo)): ?>
        <div class="alert alert-info alert-dismissible fade show mt-0 mb-3" role="alert">
            <i class="bi bi-info-circle me-2"></i><?= $flashInfo ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Ads banner (member_portal) -->
    <?php $adsTarget = 'member_portal'; include ROOT_PATH . '/app/Views/partials/ads_banner.php'; ?>

    <?= $content ?>
</div>

<footer class="portal-foot">
    <div class="portal-foot-brand">
        <!-- mini S-bolt icon -->
        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px">
            <path d="M14 1 L1 7 L7 19 L22 12 Z" fill="#D4A373"/>
            <path d="M26 39 L39 33 L33 21 L18 28 Z" fill="#D4A373"/>
            <circle cx="20" cy="20" r="12" stroke="rgba(200,218,232,.5)" stroke-width="2" fill="rgba(8,18,32,.4)"/>
            <circle cx="20" cy="20" r="7.5" stroke="rgba(226,232,240,.8)" stroke-width="1.8" fill="none"/>
            <circle cx="20" cy="20" r="4" fill="#D4A373"/>
            <circle cx="20" cy="20" r="2" fill="#E6C200"/>
        </svg>
        SHOOTERO
    </div>
    <span class="portal-foot-tagline" style="font-family:'Poppins',sans-serif;font-weight:500;letter-spacing:1.5px;text-transform:uppercase">ZARZĄDZAJ&nbsp;KLUBEM.&nbsp;WSPIERAJ&nbsp;LUDZI.</span>
    <span class="portal-foot-copy">&copy; <?= date('Y') ?> Shootero &mdash; Portal Zawodnika</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var btn  = document.getElementById('portalThemeToggle');
    var icon = document.getElementById('portalThemeIcon');
    var html = document.documentElement;
    function applyTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        if (icon) icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        if (btn)  btn.title = theme === 'dark' ? 'Przełącz na jasny motyw' : 'Przełącz na ciemny motyw';
        localStorage.setItem('theme', theme);
    }
    applyTheme(html.getAttribute('data-bs-theme') || 'dark');
    btn && btn.addEventListener('click', function () {
        applyTheme(html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
    });
})();
</script>
</body>
</html>
