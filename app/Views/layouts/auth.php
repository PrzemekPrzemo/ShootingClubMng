<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Shootero') ?> &mdash; Logowanie</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        /* Theme toggle button */
        .auth-theme-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            color: #94A3B8;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            transition: background .15s, color .15s;
            z-index: 10;
        }
        .auth-theme-btn:hover { background: rgba(212,163,115,.18); color: #D4A373; }

        /* Light mode */
        [data-bs-theme="light"] body { background: #F1F5F9; }
        [data-bs-theme="light"] body::before {
            background-image:
                radial-gradient(circle at 50% 50%, rgba(212,163,115,.06) 0%, transparent 60%),
                repeating-linear-gradient(0deg,  transparent, transparent 60px, rgba(0,0,0,.025) 60px, rgba(0,0,0,.025) 61px),
                repeating-linear-gradient(90deg, transparent, transparent 60px, rgba(0,0,0,.025) 60px, rgba(0,0,0,.025) 61px);
        }
        [data-bs-theme="light"] .auth-card {
            background: #FFFFFF;
            border-color: rgba(0,0,0,.1);
            box-shadow: 0 4px 24px rgba(0,0,0,.1), 0 1px 4px rgba(0,0,0,.06);
        }
        [data-bs-theme="light"] .auth-theme-btn {
            background: rgba(0,0,0,.05);
            border-color: rgba(0,0,0,.12);
            color: #475569;
        }
        [data-bs-theme="light"] .auth-theme-btn:hover { background: rgba(139,69,19,.1); color: #8B4513; }
    </style>
</head>
<body>
<button class="auth-theme-btn" id="authThemeToggle" title="Zmień motyw">
    <i class="bi bi-sun-fill" id="authThemeIcon"></i>
</button>
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
<script>
(function () {
    var btn  = document.getElementById('authThemeToggle');
    var icon = document.getElementById('authThemeIcon');
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
