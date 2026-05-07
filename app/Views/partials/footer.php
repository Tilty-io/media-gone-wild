    <footer class="mt-16 border-t border-base-300 bg-base-100">
        <div class="mx-auto w-full max-w-7xl px-6 py-6 text-sm opacity-70">
            Media Gone Wild - API et catalogue de médias
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('theme-toggle');
            var label = document.getElementById('theme-toggle-label');

            if (!toggle || !label) {
                return;
            }

            function currentTheme() {
                return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            }

            function renderLabel() {
                var isDark = currentTheme() === 'dark';
                label.textContent = isDark ? 'Dark' : 'Light';
                toggle.checked = isDark;
            }

            toggle.addEventListener('change', function () {
                var nextTheme = toggle.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', nextTheme);
                localStorage.setItem('mgw-theme', nextTheme);
                renderLabel();
            });

            renderLabel();
        })();
    </script>
</body>
</html>


