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

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= url('dashboard') ?>">
            <i class="bi bi-bullseye"></i> Klub Strzelecki
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/dashboard') ? 'active' : '' ?>"
                       href="<?= url('dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/members') ? 'active' : '' ?>"
                       href="<?= url('members') ?>"><i class="bi bi-people"></i> Zawodnicy</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/licenses') ? 'active' : '' ?>"
                       href="<?= url('licenses') ?>"><i class="bi bi-card-checklist"></i> Licencje</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/finances') ? 'active' : '' ?>"
                       href="<?= url('finances') ?>"><i class="bi bi-cash-stack"></i> Finanse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/competitions') ? 'active' : '' ?>"
                       href="<?= url('competitions') ?>"><i class="bi bi-trophy"></i> Zawody</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/reports') ? 'active' : '' ?>"
                       href="<?= url('reports') ?>"><i class="bi bi-file-earmark-bar-graph"></i> Raporty</a>
                </li>
                <?php if (in_array($authUser['role'] ?? '', ['admin','zarzad'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/config') ? 'active' : '' ?>"
                       href="<?= url('config') ?>"><i class="bi bi-gear"></i> Konfiguracja</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?= e($authUser['full_name'] ?? $authUser['username'] ?? '') ?>
                        <span class="badge bg-light text-dark ms-1 small"><?= e($authUser['role'] ?? '') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="<?= url('auth/logout') ?>">
                            <i class="bi bi-box-arrow-right"></i> Wyloguj
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash messages -->
<div class="container-fluid pt-3">
    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= e($flashSuccess) ?>
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
            <i class="bi bi-exclamation-circle"></i> <?= e($flashWarning) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>

<!-- Main content -->
<main class="container-fluid py-3">
    <?= $content ?>
</main>

<footer class="text-center text-muted small py-3 border-top mt-4">
    &copy; <?= date('Y') ?> Klub Strzelecki &mdash; System zarządzania v1.0
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
