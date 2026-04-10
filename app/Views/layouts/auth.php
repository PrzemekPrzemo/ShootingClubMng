<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Shootero') ?> &mdash; Logowanie</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
    <style>
        :root {
            --sht-900: #0B1220;
            --sht-800: #0F172A;
            --sht-700: #1E293B;
            --sht-gold: #D4A373;
            --sht-gold-bright: #E6C200;
        }
        html, body {
            height: 100%;
            margin: 0;
            background: var(--sht-900);
            font-family: 'Inter', -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        /* Subtle target grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle at 50% 50%, rgba(212,163,115,.04) 0%, transparent 60%),
                repeating-linear-gradient(0deg, transparent, transparent 60px, rgba(255,255,255,.012) 60px, rgba(255,255,255,.012) 61px),
                repeating-linear-gradient(90deg, transparent, transparent 60px, rgba(255,255,255,.012) 60px, rgba(255,255,255,.012) 61px);
            pointer-events: none;
            z-index: 0;
        }
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }
        .auth-container { width: 100%; max-width: 440px; }

        /* Card */
        .auth-card {
            background: var(--sht-800);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: .85rem;
            padding: 2rem 2rem 1.75rem;
            box-shadow:
                0 0 0 1px rgba(255,255,255,.04),
                0 20px 60px rgba(0,0,0,.5),
                0 4px 16px rgba(0,0,0,.3);
        }
        /* Top gold line accent */
        .auth-card::before {
            content: '';
            display: block;
            height: 2px;
            background: linear-gradient(90deg, transparent, #D4A373, #E6C200, #D4A373, transparent);
            border-radius: 100px;
            margin-bottom: 1.75rem;
        }

        /* Flash outside card */
        .auth-flash { margin-bottom: 1rem; }
        .auth-flash .alert { border-radius: .5rem; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-container">
        <?php if (!empty($flashError)): ?>
        <div class="auth-flash">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i> <?= e($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>
        <div class="auth-card">
            <?= $content ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
