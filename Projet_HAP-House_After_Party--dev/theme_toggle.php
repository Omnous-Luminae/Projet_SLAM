<!-- Theme Toggle Button -->
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
