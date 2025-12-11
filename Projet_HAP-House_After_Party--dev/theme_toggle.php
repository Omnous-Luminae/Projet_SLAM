<!-- Theme Toggle Button -->
<style>
    /* Floating Theme Toggle Button - Inline styles to ensure it works on all pages */
    .floating-theme-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        background: var(--header-bg, #fff);
        border: 2px solid var(--form-border, #ddd);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        color: var(--header-text, #3d0066);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .floating-theme-toggle:hover {
        background: var(--nav-hover-bg, #f3e6fa);
        color: var(--nav-hover-text, #a100b8);
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }

    [data-theme="dark"] .floating-theme-toggle {
        background: var(--header-bg, #1e1e1e);
        border-color: var(--form-border, #444);
        color: var(--header-text, #bb86fc);
    }

    [data-theme="dark"] .floating-theme-toggle:hover {
        background: var(--nav-hover-bg, #2a2a2a);
        color: var(--nav-hover-text, #bb86fc);
    }
</style>
<button id="theme-toggle" class="floating-theme-toggle" title="Basculer vers le mode sombre">🌙</button>

<script>
    // Theme toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;

        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        body.setAttribute('data-theme', currentTheme);
        updateToggleIcon(currentTheme);

        themeToggle.addEventListener('click', function() {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateToggleIcon(newTheme);
        });

        function updateToggleIcon(theme) {
            themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
            themeToggle.title = theme === 'light' ? 'Basculer vers le mode sombre' : 'Basculer vers le mode clair';
        }
    });
</script>
