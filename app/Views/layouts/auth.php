<!DOCTYPE html>
<html lang="pl" id="htmlRoot">
<head>
    <script>
    (function(){ try { var t = localStorage.getItem('bs-theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', t); } catch(e){} })();
    </script>
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
    <style>
        :root {
            --sht-gold: #D4A373;
            --sht-gold-bright: #E6C200;
        }
        [data-bs-theme="dark"] {
            --sht-900: #081220;
            --sht-800: #0F172A;
            --sht-700: #1E2838;
            --sht-text: #e2e8f0;
            --sht-card-border: rgba(255,255,255,.07);
            --sht-muted: #94A3B8;
            --sht-dim:   #64748B;
            --sht-border: rgba(255,255,255,.06);
            --sht-brand:  #ffffff;
        }
        [data-bs-theme="light"] {
            --sht-900: #f8fafc;
            --sht-800: #ffffff;
            --sht-700: #f1f5f9;
            --sht-text: #1e293b;
            --sht-card-border: rgba(0,0,0,.08);
            --sht-muted: #475569;
            --sht-dim:   #64748B;
            --sht-border: rgba(0,0,0,.1);
            --sht-brand:  #1e293b;
        }
        html, body {
            height: 100%;
            margin: 0;
            background: var(--sht-900);
            color: var(--sht-text);
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
            border: 1px solid var(--sht-card-border);
            border-radius: .85rem;
            padding: 2rem 2rem 1.75rem;
            box-shadow:
                0 20px 60px rgba(0,0,0,.15),
                0 4px 16px rgba(0,0,0,.08);
        }
        [data-bs-theme="dark"] .auth-card {
            box-shadow:
                0 0 0 1px rgba(255,255,255,.04),
                0 20px 60px rgba(0,0,0,.5),
                0 4px 16px rgba(0,0,0,.3);
        }
        /* Theme toggle in corner of login screen */
        .theme-toggle-corner {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: rgba(127,127,127,.1);
            border: 1px solid var(--sht-card-border);
            color: var(--sht-text);
            padding: .4rem .7rem;
            border-radius: 6px;
            cursor: pointer;
            z-index: 10;
        }
        .theme-toggle-corner:hover { background: rgba(127,127,127,.2); }
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
<button type="button" class="theme-toggle-corner" id="themeToggleBtn" title="Przełącz tryb jasny/ciemny">
    <i class="bi bi-moon-stars" id="themeIcon"></i>
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
    var btn = document.getElementById('themeToggleBtn');
    var icon = document.getElementById('themeIcon');
    function apply(t) {
        document.documentElement.setAttribute('data-bs-theme', t);
        if (icon) icon.className = t === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
        try { localStorage.setItem('bs-theme', t); } catch(e){}
    }
    if (btn) {
        var cur = document.documentElement.getAttribute('data-bs-theme') || 'dark';
        apply(cur);
        btn.addEventListener('click', function () {
            var c = document.documentElement.getAttribute('data-bs-theme') || 'dark';
            apply(c === 'dark' ? 'light' : 'dark');
        });
    }
})();
</script>
</body>
</html>
