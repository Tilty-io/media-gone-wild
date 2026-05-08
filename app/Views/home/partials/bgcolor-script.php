<?php /** @var array<string, string> $bgcolorPickerBaseParams */ ?>
<script>
    (function () {
        var preview = document.getElementById('bgcolor-picker-preview');
        var token = document.getElementById('bgcolor-picker-token');
        var link = document.getElementById('bgcolor-picker-link');
        var status = document.getElementById('bgcolor-picker-status');
        var colorInput = document.getElementById('bgcolor-picker-color');
        var opacityInput = document.getElementById('bgcolor-picker-opacity');
        var opacityValue = document.getElementById('bgcolor-picker-opacity-value');
        var presetButtons = document.querySelectorAll('[data-bgcolor-preset]');

        if (!preview || !token || !link || !status || !colorInput || !opacityInput || !opacityValue) {
            return;
        }

        var baseUrl = <?= json_encode(site_url('photo'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var baseParams = <?= json_encode($bgcolorPickerBaseParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        /**
         * Convertit une opacité 0-100 en composant hexadécimal alpha sur 2 caractères.
         */
        function toAlphaHex(opacityPercent) {
            var alpha = Math.max(0, Math.min(255, Math.round((opacityPercent / 100) * 255)));
            return alpha.toString(16).padStart(2, '0');
        }

        /**
         * Retourne la valeur bgcolor attendue par l'API à partir des contrôles UI.
         */
        function getBgcolorToken() {
            var color = String(colorInput.value || '#ff00ff').replace('#', '').toLowerCase();
            var opacity = Number(opacityInput.value || '100');

            if (opacity <= 0) {
                return 'transparent';
            }

            if (opacity >= 100) {
                return color;
            }

            return color + toAlphaHex(opacity);
        }

        /**
         * Reconstruit l'URL de démo et met à jour l'aperçu, le token et le lien.
         */
        function updatePreview() {
            var params = new URLSearchParams(baseParams);
            var opacity = Number(opacityInput.value || '100');
            var bgcolor = getBgcolorToken();
            params.set('bgcolor', bgcolor);
            var nextUrl = baseUrl + '?' + params.toString();

            opacityValue.textContent = opacity + ' %';
            token.textContent = bgcolor;
            link.href = nextUrl;
            link.textContent = nextUrl;
            preview.src = nextUrl;
            status.innerHTML = 'Fond actuel : <span class="font-mono">' + bgcolor + '</span>';
        }

        colorInput.addEventListener('input', updatePreview);
        opacityInput.addEventListener('input', updatePreview);

        presetButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                colorInput.value = button.getAttribute('data-color') || '#ff00ff';
                opacityInput.value = button.getAttribute('data-opacity') || '100';
                updatePreview();
            });
        });

        updatePreview();
    })();
</script>


