<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Portal Zawodnika') ?> &mdash; Klub Strzelecki</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; font-size: .93rem; }
        .portal-navbar { background: #1a1f2e; }
        .portal-navbar .navbar-brand { color: #fff; font-weight: 700; }
        .portal-navbar .navbar-brand i { color: #dc3545; }
        .portal-navbar .nav-link { color: #b8c0d4; }
        .portal-navbar .nav-link:hover,
        .portal-navbar .nav-link.active { color: #fff; }
        .portal-main { min-height: calc(100vh - 56px - 48px); padding: 1.5rem; }
        footer.portal-foot { text-align: center; font-size: .8rem; color: #aaa; padding: .75rem; border-top: 1px solid #e9ecef; background: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-md portal-navbar">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="<?= url('portal') ?>">
            <i class="bi bi-bullseye"></i> Klub Strzelecki
        </a>
        <button class="navbar-toggler text-white border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="portalNav">
            <?php if (isset($memberUser)): ?>
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= str_ends_with($_SERVER['REQUEST_URI'], '/portal') ? 'active' : '' ?>" href="<?= url('portal') ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/portal/profile') ? 'active' : '' ?>" href="<?= url('portal/profile') ?>">
                        <i class="bi bi-person"></i> Mój profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/portal/exams') ? 'active' : '' ?>" href="<?= url('portal/exams') ?>">
                        <i class="bi bi-heart-pulse"></i> Badania lekarskie
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/portal/competitions') ? 'active' : '' ?>" href="<?= url('portal/competitions') ?>">
                        <i class="bi bi-trophy"></i> Zawody
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/portal/results') ? 'active' : '' ?>" href="<?= url('portal/results') ?>">
                        <i class="bi bi-bar-chart"></i> Wyniki
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/portal/fees') ? 'active' : '' ?>" href="<?= url('portal/fees') ?>">
                        <i class="bi bi-cash"></i> Opłaty
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/portal/weapons') ? 'active' : '' ?>" href="<?= url('portal/weapons') ?>">
                        <i class="bi bi-shield-lock"></i> Moja broń
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small"><?= e($memberUser['full_name'] ?? '') ?></span>
                <a href="<?= url('portal/logout') ?>" class="btn btn-sm btn-outline-secondary text-white border-secondary">
                    <i class="bi bi-box-arrow-right"></i> Wyloguj
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="portal-main container-fluid">
    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-check-circle"></i> <?= e($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= e($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashWarning)): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?= e($flashWarning) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Ads banner (member_portal) -->
    <?php $adsTarget = 'member_portal'; include ROOT_PATH . '/app/Views/partials/ads_banner.php'; ?>

    <?= $content ?>
</div>

<footer class="portal-foot">
    &copy; <?= date('Y') ?> Klub Strzelecki &mdash; Portal Zawodnika v1.0
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
