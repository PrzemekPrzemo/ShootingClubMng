<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Wyniki zawodów') ?></title>
    <meta name="description" content="<?= e($title ?? 'Publiczne wyniki zawodów strzeleckich') ?>">
    <!-- Open Graph -->
    <meta property="og:title"       content="<?= e($title ?? 'Wyniki zawodów') ?>">
    <meta property="og:type"        content="website">
    <meta property="og:description" content="Oficjalne wyniki zawodów strzeleckich">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php
    $__primaryColor = $club['primary_color'] ?? '#dc3545';
    ?>
    <style>
        :root { --pub-primary: <?= htmlspecialchars($__primaryColor, ENT_QUOTES) ?>; }
        .pub-navbar { background: var(--pub-primary); }
        .pub-navbar a, .pub-navbar .navbar-brand { color: #fff !important; }
        .pub-navbar .navbar-text { color: rgba(255,255,255,.8); }
        footer { background: #f8f9fa; border-top: 1px solid #dee2e6; padding: 1rem; text-align: center; color: #6c757d; font-size: .85rem; margin-top: 2rem; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand pub-navbar px-3 py-2 mb-4">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= url('pub') ?>">
        <?php if (!empty($club['logo_path'])): ?>
            <img src="<?= url('club/logo?club=' . ($club['id'] ?? '')) ?>" alt="" style="height:32px">
        <?php else: ?>
            <i class="bi bi-bullseye"></i>
        <?php endif; ?>
        <?= e($club['name'] ?? 'Wyniki zawodów') ?>
    </a>
    <?php if (!empty($slug)): ?>
    <div class="ms-auto">
        <a href="<?= url('pub/' . $slug . '/competitions') ?>" class="text-white text-decoration-none small">
            <i class="bi bi-list-ol"></i> Zawody
        </a>
    </div>
    <?php endif; ?>
</nav>

<div class="container pb-4">
    <?= $content ?>
</div>

<footer>
    <?= e($club['name'] ?? 'Klub strzelecki') ?> &mdash; Wyniki zawodów &mdash; <?= date('Y') ?>
    &mdash; <a href="<?= url('auth/login') ?>" class="text-muted text-decoration-none">Panel zarządzania</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
