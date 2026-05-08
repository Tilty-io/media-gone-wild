<?php
/** @var string $defaultDemoPhotoId */
/** @var array<string, string> $bgcolorPickerBaseParams */
?>
<script>
    (function () {
        var photoIdInput = document.getElementById('home-photo-id-select');
        var photoIdPreview = document.getElementById('home-photo-id-preview');
        var photoIdDisplay = document.getElementById('home-photo-id-display');
        var photoIdOptions = document.querySelectorAll('[data-photo-id-option]');
        var copyToast = document.getElementById('global-copy-toast');
        var fitDemoCard = document.querySelector('[data-fit-demo-card]');
        var fitDemoSelect = document.querySelector('[data-fit-demo-select]');
        var fitDemoSizeSelect = document.querySelector('[data-fit-demo-size-select]');
        var fitDemoBgSelect = document.querySelector('[data-fit-demo-bg-select]');
        var fitDemoDescription = document.querySelector('[data-fit-demo-description]');
        var basePhotoPath = <?= json_encode(site_url('photo'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var defaultDemoPhotoId = <?= json_encode($defaultDemoPhotoId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        var bgcolorPickerBaseParams = <?= json_encode($bgcolorPickerBaseParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        var modal = document.getElementById('home-photo-transform-modal');
        var modalForm = document.getElementById('home-photo-transform-builder');
        var modalIdInput = document.getElementById('home-photo-transform-id');
        var modalPreview = document.getElementById('home-photo-transform-preview');
        var modalStatus = document.getElementById('home-photo-transform-status');
        var modalReset = document.getElementById('home-photo-transform-reset');
        var modalUrlLink = modal ? modal.querySelector('[data-api-url-link]') : null;
        var modalBgcolorInput = document.getElementById('home-photo-transform-bgcolor');
        var modalBgcolorColorInput = document.getElementById('home-photo-transform-bgcolor-color');
        var modalBgcolorOpacityInput = document.getElementById('home-photo-transform-bgcolor-opacity');
        var modalBgcolorOpacityValue = document.getElementById('home-photo-transform-bgcolor-opacity-value');
        var modalBgcolorToken = document.getElementById('home-photo-transform-bgcolor-token');
        var modalBgcolorPresetButtons = document.querySelectorAll('[data-home-photo-transform-bgcolor-preset]');

        var bgcolorRoot = document.getElementById('bgcolor-picker-root');
        var bgcolorPreview = document.getElementById('bgcolor-picker-preview');
        var bgcolorToken = document.getElementById('bgcolor-picker-token');
        var bgcolorStatus = document.getElementById('bgcolor-picker-status');
        var bgcolorPhotoId = document.getElementById('bgcolor-picker-photo-id');
        var bgcolorColorInput = document.getElementById('bgcolor-picker-color');
        var bgcolorOpacityInput = document.getElementById('bgcolor-picker-opacity');
        var bgcolorOpacityValue = document.getElementById('bgcolor-picker-opacity-value');
        var bgcolorPresetButtons = document.querySelectorAll('[data-bgcolor-preset]');
        var bgcolorUrlLink = document.querySelector('#bgcolor-picker-url-host [data-api-url-link]');

        if (!photoIdInput) {
            return;
        }

        var currentPhotoId = String(photoIdInput.value || defaultDemoPhotoId);
        var toastTimer = null;
        var fitModeDescriptions = {
            contain: '<code class="rounded bg-base-300 px-1 py-0.5">contain</code> conserve les proportions et affiche toute l\'image ; des marges peuvent apparaître dans le canvas.',
            cover: '<code class="rounded bg-base-300 px-1 py-0.5">cover</code> remplit tout le canvas, en recadrant l\'image si nécessaire.',
            fill: '<code class="rounded bg-base-300 px-1 py-0.5">fill</code> remplit exactement le canvas en étirant l\'image si le ratio diffère.',
            scale: '<code class="rounded bg-base-300 px-1 py-0.5">scale</code> redimensionne proportionnellement sans forcer le remplissage complet du canvas ; l\'image générée peut donc ne pas mesurer exactement la taille demandée.'
        };
        var fitDemoSizes = {
            '4:3': { width: '400', height: '300' },
            '3:4': { width: '300', height: '400' },
            '16:9': { width: '400', height: '225' },
            '1:1': { width: '320', height: '320' }
        };

        function updateFitDemoDescription() {
            if (!fitDemoDescription || !fitDemoSelect) {
                return;
            }

            var selectedFit = String(fitDemoSelect.value || 'contain');
            fitDemoDescription.innerHTML = fitModeDescriptions[selectedFit] || fitModeDescriptions.contain;
        }

        function updateFitDemoCard() {
            if (!fitDemoCard || !fitDemoSelect) {
                return;
            }

            var fitParams = readTemplate(fitDemoCard);
            var selectedFit = String(fitDemoSelect.value || 'contain');
            var selectedRatio = fitDemoSizeSelect ? String(fitDemoSizeSelect.value || '3:4') : '3:4';
            var selectedBg = fitDemoBgSelect ? String(fitDemoBgSelect.value || 'ffffff') : 'ffffff';
            var ratioSize = fitDemoSizes[selectedRatio] || fitDemoSizes['3:4'];

            fitParams.fit = selectedFit;
            fitParams.width = ratioSize.width;
            fitParams.height = ratioSize.height;
            fitParams.bgcolor = selectedBg;
            fitParams.extension = 'png';

            fitDemoCard.setAttribute('data-photo-template', JSON.stringify(fitParams));
            applyPhotoUrlToCard(fitDemoCard, fitParams);
            updateFitDemoDescription();
        }

        function renderPhotoPicker() {
            if (photoIdDisplay) {
                photoIdDisplay.textContent = currentPhotoId;
            }

            if (photoIdPreview) {
                photoIdPreview.src = buildPhotoUrl({});
            }
        }

        function setCurrentPhotoId(nextPhotoId) {
            if (!nextPhotoId) {
                return;
            }

            currentPhotoId = String(nextPhotoId);
            photoIdInput.value = currentPhotoId;
            renderPhotoPicker();
            updateAllPhotoCards();
            updateBgcolorDemo();
        }

        function showToast() {
            if (!copyToast) {
                return;
            }

            copyToast.classList.remove('hidden');

            if (toastTimer !== null) {
                clearTimeout(toastTimer);
            }

            toastTimer = window.setTimeout(function () {
                copyToast.classList.add('hidden');
            }, 1600);
        }

        function copyText(value) {
            if (!value) {
                return Promise.reject(new Error('Aucun texte à copier.'));
            }

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

                    if (success) {
                        resolve();
                        return;
                    }

                    reject(new Error('Copie impossible.'));
                } catch (error) {
                    document.body.removeChild(textarea);
                    reject(error);
                }
            });
        }

        function readTemplate(card) {
            var raw = card.getAttribute('data-photo-template') || '{}';

            try {
                var parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function buildPhotoUrl(params) {
            var search = new URLSearchParams();
            search.set('id', currentPhotoId);

            Object.keys(params).forEach(function (key) {
                var value = params[key];

                if (value === null || value === '') {
                    return;
                }

                search.set(key, String(value));
            });

            return basePhotoPath + '?' + search.toString();
        }

        function applyPhotoUrlToCard(card, params) {
            var url = buildPhotoUrl(params);
            var preview = card.querySelector('[data-photo-preview]');
            var link = card.querySelector('[data-api-url-link]');

            if (preview && preview.tagName === 'IMG') {
                preview.src = url;
            }

            if (link) {
                link.href = url;
                link.textContent = url;
            }
        }

        function updateAllPhotoCards() {
            document.querySelectorAll('[data-photo-template]').forEach(function (card) {
                var params = readTemplate(card);
                applyPhotoUrlToCard(card, params);
            });
        }

        function toAlphaHex(opacityPercent) {
            var alpha = Math.max(0, Math.min(255, Math.round((opacityPercent / 100) * 255)));
            return alpha.toString(16).padStart(2, '0');
        }

        function getBgcolorToken() {
            var color = String((bgcolorColorInput && bgcolorColorInput.value) || '#ff00ff').replace('#', '').toLowerCase();
            var opacity = Number((bgcolorOpacityInput && bgcolorOpacityInput.value) || '100');

            if (opacity <= 0) {
                return 'transparent';
            }

            if (opacity >= 100) {
                return color;
            }

            return color + toAlphaHex(opacity);
        }

        function getModalBgcolorTokenFromControls() {
            if (!modalBgcolorColorInput || !modalBgcolorOpacityInput) {
                return '';
            }

            var color = String(modalBgcolorColorInput.value || '#ff00ff').replace('#', '').toLowerCase();
            var opacity = Number(modalBgcolorOpacityInput.value || '100');

            if (opacity <= 0) {
                return 'transparent';
            }

            if (opacity >= 100) {
                return color;
            }

            return color + toAlphaHex(opacity);
        }

        function updateModalBgcolorUi() {
            if (!modalBgcolorInput || !modalBgcolorOpacityInput || !modalBgcolorOpacityValue || !modalBgcolorToken) {
                return;
            }

            var token = getModalBgcolorTokenFromControls();
            var opacity = Number(modalBgcolorOpacityInput.value || '100');

            modalBgcolorInput.value = token;
            modalBgcolorOpacityValue.textContent = opacity + ' %';
            modalBgcolorToken.textContent = token;
        }

        function syncModalBgcolorControlsFromToken(rawToken) {
            if (!modalBgcolorInput || !modalBgcolorColorInput || !modalBgcolorOpacityInput || !modalBgcolorOpacityValue || !modalBgcolorToken) {
                return;
            }

            var normalized = String(rawToken || '').trim().toLowerCase();

            if (normalized === '') {
                modalBgcolorInput.value = '';
                modalBgcolorColorInput.value = '#ff00ff';
                modalBgcolorOpacityInput.value = '100';
                modalBgcolorOpacityValue.textContent = '100 %';
                modalBgcolorToken.textContent = 'non défini';
                return;
            }

            if (normalized === 'transparent') {
                modalBgcolorColorInput.value = '#000000';
                modalBgcolorOpacityInput.value = '0';
                updateModalBgcolorUi();
                return;
            }

            if (/^[0-9a-f]{6}$/i.test(normalized)) {
                modalBgcolorColorInput.value = '#' + normalized;
                modalBgcolorOpacityInput.value = '100';
                updateModalBgcolorUi();
                return;
            }

            if (/^[0-9a-f]{8}$/i.test(normalized)) {
                var rgb = normalized.slice(0, 6);
                var alphaHex = normalized.slice(6, 8);
                var alphaValue = parseInt(alphaHex, 16);
                var opacityPercent = Math.max(0, Math.min(100, Math.round((alphaValue / 255) * 100)));
                modalBgcolorColorInput.value = '#' + rgb;
                modalBgcolorOpacityInput.value = String(opacityPercent);
                updateModalBgcolorUi();
                return;
            }

            modalBgcolorInput.value = '';
            modalBgcolorToken.textContent = 'non défini';
        }

        function updateBgcolorDemo() {
            if (!bgcolorRoot || !bgcolorPreview || !bgcolorToken || !bgcolorStatus || !bgcolorOpacityValue || !bgcolorUrlLink || !bgcolorPhotoId) {
                return;
            }

            var opacity = Number((bgcolorOpacityInput && bgcolorOpacityInput.value) || '100');
            var bgcolor = getBgcolorToken();
            var params = Object.assign({}, bgcolorPickerBaseParams, { bgcolor: bgcolor });
            var url = buildPhotoUrl(params);

            bgcolorOpacityValue.textContent = opacity + ' %';
            bgcolorToken.textContent = bgcolor;
            bgcolorStatus.innerHTML = 'Fond actuel : <span class="font-mono">' + bgcolor + '</span>';
            bgcolorPhotoId.innerHTML = 'Photo testée : <span class="font-mono">' + currentPhotoId + '</span>';
            bgcolorPreview.src = url;
            bgcolorUrlLink.href = url;
            bgcolorUrlLink.textContent = url;
        }

        function parseApiMediaUrl(url) {
            try {
                var parsed = new URL(url, window.location.href);
                var endpointMatch = parsed.pathname.match(/\/(photo|video|logo)\/?$/);

                if (!endpointMatch) {
                    return null;
                }

                var extractedParams = {};
                ['width', 'height', 'fit', 'extension', 'quality', 'bgcolor'].forEach(function (name) {
                    var value = parsed.searchParams.get(name);

                    if (value !== null && value !== '') {
                        extractedParams[name] = value;
                    }
                });

                return {
                    id: parsed.searchParams.get('id') || currentPhotoId,
                    params: extractedParams
                };
            } catch (error) {
                return null;
            }
        }

        function readModalField(name) {
            if (!modalForm) {
                return '';
            }

            var field = modalForm.querySelector('[name="' + name + '"]');
            return field ? String(field.value || '').trim() : '';
        }

        function updateModalUrl() {
            if (!modalForm || !modalUrlLink || !modalPreview || !modalIdInput) {
                return;
            }

            var params = {};
            var id = modalIdInput.value.trim();
            var width = readModalField('width');
            var height = readModalField('height');
            var fit = readModalField('fit');
            var extension = readModalField('extension');
            var quality = readModalField('quality');
            var bgcolor = readModalField('bgcolor');

            if (width !== '') {
                params.width = width;
            }

            if (height !== '') {
                params.height = height;
            }

            if (fit !== '') {
                params.fit = fit;
            }

            if (extension !== '') {
                params.extension = extension;
            }

            if (quality !== '' && quality !== '85') {
                params.quality = quality;
            }

            if (bgcolor !== '') {
                params.bgcolor = bgcolor;
            }

            var search = new URLSearchParams();
            if (id !== '') {
                search.set('id', id);
            }

            Object.keys(params).forEach(function (key) {
                search.set(key, String(params[key]));
            });

            var url = basePhotoPath + (search.toString() ? '?' + search.toString() : '');
            modalUrlLink.href = url;
            modalUrlLink.textContent = url;
            modalPreview.src = url;
            modalStatus.textContent = id === ''
                ? 'Renseigne un ID photo pour finaliser l\'URL.'
                : 'Aperçu mis à jour pour l\'ID ' + id + '.';
        }

        function openTransformModalFromUrl(url) {
            if (!modal || !modalForm || !modalIdInput) {
                return;
            }

            var parsed = parseApiMediaUrl(url);

            if (!parsed) {
                return;
            }

            modalForm.reset();
            modalIdInput.value = parsed.id;

            ['width', 'height', 'fit', 'extension', 'quality', 'bgcolor'].forEach(function (name) {
                var field = modalForm.querySelector('[name="' + name + '"]');
                if (!field) {
                    return;
                }

                field.value = parsed.params[name] || '';
            });

            syncModalBgcolorControlsFromToken(parsed.params.bgcolor || '');

            updateModalUrl();

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            }
        }

        document.addEventListener('click', function (event) {
            var copyButton = event.target.closest('[data-api-copy]');

            if (copyButton) {
                var copyCard = copyButton.closest('[data-api-url-card]');
                var copyLink = copyCard ? copyCard.querySelector('[data-api-url-link]') : null;
                var text = copyLink ? String(copyLink.href || copyLink.textContent || '').trim() : '';

                copyText(text).then(function () {
                    showToast();
                }).catch(function () {
                    // L'échec de copie reste silencieux pour ne pas polluer l'interface.
                });

                return;
            }

            var transformButton = event.target.closest('[data-api-transform]');

            if (transformButton) {
                var transformCard = transformButton.closest('[data-api-url-card]');
                var transformLink = transformCard ? transformCard.querySelector('[data-api-url-link]') : null;
                var url = transformLink ? String(transformLink.href || transformLink.textContent || '').trim() : '';

                openTransformModalFromUrl(url);
            }
        });

        photoIdOptions.forEach(function (optionButton) {
            optionButton.addEventListener('click', function () {
                var nextPhotoId = optionButton.getAttribute('data-photo-id') || '';
                setCurrentPhotoId(nextPhotoId);
            });
        });

        if (fitDemoCard && fitDemoSelect) {
            fitDemoSelect.addEventListener('change', updateFitDemoCard);
        }

        if (fitDemoCard && fitDemoSizeSelect) {
            fitDemoSizeSelect.addEventListener('change', updateFitDemoCard);
        }

        if (fitDemoCard && fitDemoBgSelect) {
            fitDemoBgSelect.addEventListener('change', updateFitDemoCard);
        }

        if (bgcolorColorInput) {
            bgcolorColorInput.addEventListener('input', updateBgcolorDemo);
        }

        if (bgcolorOpacityInput) {
            bgcolorOpacityInput.addEventListener('input', updateBgcolorDemo);
        }

        bgcolorPresetButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!bgcolorColorInput || !bgcolorOpacityInput) {
                    return;
                }

                bgcolorColorInput.value = button.getAttribute('data-color') || '#ff00ff';
                bgcolorOpacityInput.value = button.getAttribute('data-opacity') || '100';
                updateBgcolorDemo();
            });
        });

        if (modalForm) {
            modalForm.addEventListener('input', updateModalUrl);
            modalForm.addEventListener('change', updateModalUrl);
        }

        if (modalBgcolorColorInput) {
            modalBgcolorColorInput.addEventListener('input', function () {
                updateModalBgcolorUi();
                updateModalUrl();
            });
        }

        if (modalBgcolorOpacityInput) {
            modalBgcolorOpacityInput.addEventListener('input', function () {
                updateModalBgcolorUi();
                updateModalUrl();
            });
        }

        modalBgcolorPresetButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!modalBgcolorColorInput || !modalBgcolorOpacityInput) {
                    return;
                }

                modalBgcolorColorInput.value = button.getAttribute('data-color') || '#ff00ff';
                modalBgcolorOpacityInput.value = button.getAttribute('data-opacity') || '100';
                updateModalBgcolorUi();
                updateModalUrl();
            });
        });

        if (modalReset && modalForm && modalIdInput) {
            modalReset.addEventListener('click', function () {
                modalForm.reset();
                modalIdInput.value = currentPhotoId;
                syncModalBgcolorControlsFromToken('');
                updateModalUrl();
            });
        }

        renderPhotoPicker();
        updateFitDemoCard();
        updateAllPhotoCards();
        updateBgcolorDemo();
        syncModalBgcolorControlsFromToken('');
        updateModalUrl();
    })();
</script>









