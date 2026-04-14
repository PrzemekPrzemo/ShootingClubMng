/**
 * Klub Strzelecki — Application JavaScript
 */

'use strict';

// Theme toggle (light / dark)
(function () {
    function applyTheme(theme) {
        const root = document.getElementById('htmlRoot') || document.documentElement;
        root.setAttribute('data-bs-theme', theme);
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
        }
        try { localStorage.setItem('bs-theme', theme); } catch (e) {}
    }

    // Apply saved theme on load (before DOMContentLoaded to avoid flash)
    try {
        const saved = localStorage.getItem('bs-theme');
        if (saved === 'light' || saved === 'dark') applyTheme(saved);
    } catch (e) {}

    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('themeToggleBtn');
        if (btn) {
            btn.addEventListener('click', function () {
                const root = document.getElementById('htmlRoot') || document.documentElement;
                const current = root.getAttribute('data-bs-theme') || 'dark';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
            // Ensure icon reflects current state on load
            const current = (document.getElementById('htmlRoot') || document.documentElement).getAttribute('data-bs-theme') || 'dark';
            applyTheme(current);
        }
    });
})();

// Auto-dismiss alerts after 5s
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 5000);
    });

    // Confirm-delete forms (fallback, most use inline onsubmit)
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Highlight active nav link (already done server-side, JS fallback)
    const path = window.location.pathname;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && path.startsWith(href) && href !== '/') {
            link.classList.add('active');
        }
    });
});
