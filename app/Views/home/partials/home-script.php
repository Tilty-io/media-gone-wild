<?php
/** @var string $defaultDemoPhotoId */
/** @var array<string, string> $bgcolorPickerBaseParams */
?>
<script>
    (function () {
        var photoIdInput     = document.getElementById('home-photo-id-select');
        var photoIdPreview   = document.getElementById('home-photo-id-preview');
        var photoIdDisplay   = document.getElementById('home-photo-id-display');
        var photoIdOptions   = document.querySelectorAll('[data-photo-id-option]');
        var copyToast        = document.getElementById('global-copy-toast');
        var fitDemoCard      = document.querySelector('[data-fit-demo-card]');
        var fitDemoSelect    = document.querySelector('[data-fit-demo-select]');
        var fitDemoSizeSelect = document.querySelector('[data-fit-demo-size-select]');
        var fitDemoBgSelect  = document.querySelector('[data-fit-demo-bg-select]');
        var fitDemoDescription = document.querySelector('[data-fit-demo-description]');
        var basePhotoPath    = <?= json_encode(site_url('photo'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var defaultDemoPhotoId = <?= json_encode($defaultDemoPhotoId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var bgcolorPickerBaseParams = <?= json_encode($bgcolorPickerBaseParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        var bgcolorRoot         = document.getElementById('bgcolor-picker-root');
        var bgcolorPreview      = document.getElementById('bgcolor-picker-preview');
        var bgcolorToken        = document.getElementById('bgcolor-picker-token');
        var bgcolorStatus       = document.getElementById('bgcolor-picker-status');
        var bgcolorPhotoId      = document.getElementById('bgcolor-picker-photo-id');
        var bgcolorColorInput   = document.getElementById('bgcolor-picker-color');
        var bgcolorOpacityInput = document.getElementById('bgcolor-picker-opacity');
        var bgcolorOpacityValue = document.getElementById('bgcolor-picker-opacity-value');
        var bgcolorPresetButtons = document.querySelectorAll('[data-bgcolor-preset]');
        var bgcolorUrlLink      = document.querySelector('#bgcolor-picker-url-host [data-api-url-link]');

        if (!photoIdInput) {
            return;
        }

        var currentPhotoId = String(photoIdInput.value || defaultDemoPhotoId);
        var toastTimer = null;
        var fitModeDescriptions = {
            contain: '<code class="rounded bg-base-300 px-1 py-0.5">contain</code> conserve les proportions et affiche toute l\'image ; des marges peuvent apparaître dans le canvas.',
            cover:   '<code class="rounded bg-base-300 px-1 py-0.5">cover</code> remplit tout le canvas, en recadrant l\'image si nécessaire.',
            fill:    '<code class="rounded bg-base-300 px-1 py-0.5">fill</code> remplit exactement le canvas en étirant l\'image si le ratio diffère.',
            scale:   '<code class="rounded bg-base-300 px-1 py-0.5">scale</code> redimensionne proportionnellement sans forcer le remplissage complet du canvas ; l\'image générée peut donc ne pas mesurer exactement la taille demandée.'
        };
        var fitDemoSizes = {
            '4:3':  { width: '400', height: '300' },
            '3:4':  { width: '300', height: '400' },
            '16:9': { width: '400', height: '225' },
            '1:1':  { width: '320', height: '320' }
        };

        function updateFitDemoDescription() {
            if (!fitDemoDescription || !fitDemoSelect) { return; }
            var selectedFit = String(fitDemoSelect.value || 'contain');
            fitDemoDescription.innerHTML = fitModeDescriptions[selectedFit] || fitModeDescriptions.contain;
        }

        function updateFitDemoCard() {
            if (!fitDemoCard || !fitDemoSelect) { return; }

            var fitParams    = readTemplate(fitDemoCard);
            var selectedFit  = String(fitDemoSelect.value || 'contain');
            var selectedRatio = fitDemoSizeSelect ? String(fitDemoSizeSelect.value || '3:4') : '3:4';
            var selectedBg   = fitDemoBgSelect ? String(fitDemoBgSelect.value || 'ffffff') : 'ffffff';
            var ratioSize    = fitDemoSizes[selectedRatio] || fitDemoSizes['3:4'];

            fitParams.fit       = selectedFit;
            fitParams.width     = ratioSize.width;
            fitParams.height    = ratioSize.height;
            fitParams.bgcolor   = selectedBg;
            fitParams.extension = 'png';

            fitDemoCard.setAttribute('data-photo-template', JSON.stringify(fitParams));
            applyPhotoUrlToCard(fitDemoCard, fitParams);
            updateFitDemoDescription();
        }

        function renderPhotoPicker() {
            if (photoIdDisplay) { photoIdDisplay.textContent = currentPhotoId; }
            if (photoIdPreview) { photoIdPreview.src = buildPhotoUrl({}); }
        }

        function setCurrentPhotoId(nextPhotoId) {
            if (!nextPhotoId) { return; }
            currentPhotoId = String(nextPhotoId);
            photoIdInput.value = currentPhotoId;
            renderPhotoPicker();
            updateAllPhotoCards();
            updateBgcolorDemo();
        }

        function showToast() {
            if (!copyToast) { return; }
            copyToast.classList.remove('hidden');
            if (toastTimer !== null) { clearTimeout(toastTimer); }
            toastTimer = window.setTimeout(function () { copyToast.classList.add('hidden'); }, 1600);
        }

        function copyText(value) {
            if (!value) { return Promise.reject(new Error('Aucun texte à copier.')); }

            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(value);
            }

            return new Promise(function (resolve, reject) {
                var textarea = document.createElement('textarea');
                textarea.value = value;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();

                try {
                    var success = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    if (success) { resolve(); return; }
                    reject(new Error('Copie impossible.'));
                } catch (err) {
                    document.body.removeChild(textarea);
                    reject(err);
                }
            });
        }

        function readTemplate(card) {
            var raw = card.getAttribute('data-photo-template') || '{}';
            try {
                var parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (err) { return {}; }
        }

        function buildPhotoUrl(params) {
            var search = new URLSearchParams();
            search.set('id', currentPhotoId);

            Object.keys(params).forEach(function (key) {
                var value = params[key];
                if (value === null || value === '') { return; }
                search.set(key, String(value));
            });

            return basePhotoPath + '?' + search.toString();
        }

        function applyPhotoUrlToCard(card, params) {
            var url     = buildPhotoUrl(params);
            var preview = card.querySelector('[data-photo-preview]');
            var link    = card.querySelector('[data-api-url-link]');

            if (preview && preview.tagName === 'IMG') { preview.src = url; }
            if (link) { link.href = url; link.textContent = url; }
        }

        function updateAllPhotoCards() {
            document.querySelectorAll('[data-photo-template]').forEach(function (card) {
                applyPhotoUrlToCard(card, readTemplate(card));
            });
        }

        function toAlphaHex(opacityPercent) {
            var alpha = Math.max(0, Math.min(255, Math.round((opacityPercent / 100) * 255)));
            return alpha.toString(16).padStart(2, '0');
        }

        function getBgcolorToken() {
            var color   = String((bgcolorColorInput && bgcolorColorInput.value) || '#ff00ff').replace('#', '').toLowerCase();
            var opacity = Number((bgcolorOpacityInput && bgcolorOpacityInput.value) || '100');
            if (opacity <= 0)  { return 'transparent'; }
            if (opacity >= 100) { return color; }
            return color + toAlphaHex(opacity);
        }

        function updateBgcolorDemo() {
            if (!bgcolorRoot || !bgcolorPreview || !bgcolorToken || !bgcolorStatus || !bgcolorOpacityValue || !bgcolorUrlLink || !bgcolorPhotoId) {
                return;
            }

            var opacity = Number((bgcolorOpacityInput && bgcolorOpacityInput.value) || '100');
            var bgcolor = getBgcolorToken();
            var params  = Object.assign({}, bgcolorPickerBaseParams, { bgcolor: bgcolor });
            var url     = buildPhotoUrl(params);

            bgcolorOpacityValue.textContent = opacity + ' %';
            bgcolorToken.textContent        = bgcolor;
            bgcolorStatus.innerHTML         = 'Fond actuel : <span class="font-mono">' + bgcolor + '</span>';
            bgcolorPhotoId.innerHTML        = 'Photo testée : <span class="font-mono">' + currentPhotoId + '</span>';
            bgcolorPreview.src              = url;
            bgcolorUrlLink.href             = url;
            bgcolorUrlLink.textContent      = url;
        }

        // --- Écouteurs d'événements ---

        document.addEventListener('click', function (event) {
            var copyButton = event.target.closest('[data-api-copy]');
            if (copyButton) {
                var copyCard = copyButton.closest('[data-api-url-card]');
                var copyLink = copyCard ? copyCard.querySelector('[data-api-url-link]') : null;
                var text     = copyLink ? String(copyLink.href || copyLink.textContent || '').trim() : '';
                copyText(text).then(function () { showToast(); }).catch(function () {});
                return;
            }

            var transformButton = event.target.closest('[data-api-transform]');
            if (transformButton) {
                var transformCard = transformButton.closest('[data-api-url-card]');
                var transformLink = transformCard ? transformCard.querySelector('[data-api-url-link]') : null;
                var url = transformLink ? String(transformLink.href || transformLink.textContent || '').trim() : '';
                // La logique de la modale est gérée par photo-transform-modal-script.php
                if (window.HomeApp && typeof window.HomeApp.openTransformModal === 'function') {
                    window.HomeApp.openTransformModal(url);
                }
            }
        });

        photoIdOptions.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setCurrentPhotoId(btn.getAttribute('data-photo-id') || '');
            });
        });

        if (fitDemoCard && fitDemoSelect)     { fitDemoSelect.addEventListener('change', updateFitDemoCard); }
        if (fitDemoCard && fitDemoSizeSelect) { fitDemoSizeSelect.addEventListener('change', updateFitDemoCard); }
        if (fitDemoCard && fitDemoBgSelect)   { fitDemoBgSelect.addEventListener('change', updateFitDemoCard); }

        if (bgcolorColorInput)   { bgcolorColorInput.addEventListener('input', updateBgcolorDemo); }
        if (bgcolorOpacityInput) { bgcolorOpacityInput.addEventListener('input', updateBgcolorDemo); }

        bgcolorPresetButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!bgcolorColorInput || !bgcolorOpacityInput) { return; }
                bgcolorColorInput.value   = btn.getAttribute('data-color')   || '#ff00ff';
                bgcolorOpacityInput.value = btn.getAttribute('data-opacity') || '100';
                updateBgcolorDemo();
            });
        });

        // --- Initialisation ---
        renderPhotoPicker();
        updateFitDemoCard();
        updateAllPhotoCards();
        updateBgcolorDemo();

        // Expose l'état partagé pour les scripts de composants (ex. photo-transform-modal-script.php).
        window.HomeApp = {
            getPhotoId:    function () { return currentPhotoId; },
            basePhotoPath: basePhotoPath,
            buildPhotoUrl: buildPhotoUrl,
            showToast:     showToast,
        };
    })();
</script>

