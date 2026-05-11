<?php
/** @var string $defaultDemoPhotoId */
?>
<script>
    (function () {
        var HomeApp = window.HomeApp;

        if (!HomeApp) {
            return;
        }

        var basePhotoPath = HomeApp.basePhotoPath;

        // --- Éléments DOM de la modale ---
        var modal              = document.getElementById('home-photo-transform-modal');
        var modalForm          = document.getElementById('home-photo-transform-builder');
        var modalIdInput       = document.getElementById('home-photo-transform-id');
        var modalPreview       = document.getElementById('home-photo-transform-preview');
        var modalStatus        = document.getElementById('home-photo-transform-status');
        var modalReset         = document.getElementById('home-photo-transform-reset');
        var modalUrlLink       = modal ? modal.querySelector('[data-api-url-link]') : null;

        var modalBgcolorInput        = document.getElementById('home-photo-transform-bgcolor');
        var modalBgcolorColorInput   = document.getElementById('home-photo-transform-bgcolor-color');
        var modalBgcolorOpacityInput = document.getElementById('home-photo-transform-bgcolor-opacity');
        var modalBgcolorOpacityValue = document.getElementById('home-photo-transform-bgcolor-opacity-value');
        var modalBgcolorToken        = document.getElementById('home-photo-transform-bgcolor-token');
        var modalBgcolorPresetBtns   = document.querySelectorAll('[data-home-photo-transform-bgcolor-preset]');

        var modalQualityInput = document.getElementById('home-photo-transform-quality');
        var modalQualityValue = document.getElementById('home-photo-transform-quality-value');

        var originalInfoEl = document.getElementById('home-photo-transform-original-info');
        var resultInfoEl   = document.getElementById('home-photo-transform-result-info');

        // --- Zoom de l'aperçu ---
        var previewContainer = document.getElementById('modal-preview-container');
        var zoomOutBtn       = document.getElementById('modal-zoom-out');
        var zoomInBtn        = document.getElementById('modal-zoom-in');
        var zoomAutoBtn      = document.getElementById('modal-zoom-auto');
        var zoomLabel        = document.getElementById('modal-zoom-label');

        var ZOOM_STEPS    = [0.1, 0.25, 0.33, 0.5, 0.67, 0.75, 1.0, 1.25, 1.5, 2.0, 3.0, 4.0];
        var currentZoom   = 'auto'; // 'auto' ou nombre
        var autoZoomValue = 1;

        if (!modal || !modalForm || !modalIdInput) {
            return;
        }

        // --- Utilitaires couleur ---

        function toAlphaHex(opacityPercent) {
            var alpha = Math.max(0, Math.min(255, Math.round((opacityPercent / 100) * 255)));
            return alpha.toString(16).padStart(2, '0');
        }

        function getModalBgcolorTokenFromControls() {
            if (!modalBgcolorColorInput || !modalBgcolorOpacityInput) {
                return '';
            }

            var color   = String(modalBgcolorColorInput.value || '#ff00ff').replace('#', '').toLowerCase();
            var opacity = Number(modalBgcolorOpacityInput.value || '100');

            if (opacity <= 0) { return 'transparent'; }
            if (opacity >= 100) { return color; }

            return color + toAlphaHex(opacity);
        }

        function updateModalBgcolorUi() {
            if (!modalBgcolorInput || !modalBgcolorOpacityInput || !modalBgcolorOpacityValue || !modalBgcolorToken) {
                return;
            }

            var token   = getModalBgcolorTokenFromControls();
            var opacity = Number(modalBgcolorOpacityInput.value || '100');

            modalBgcolorInput.value          = token;
            modalBgcolorOpacityValue.textContent = opacity + ' %';
            modalBgcolorToken.textContent    = token;
        }

        function syncModalBgcolorControlsFromToken(rawToken) {
            if (!modalBgcolorInput || !modalBgcolorColorInput || !modalBgcolorOpacityInput || !modalBgcolorOpacityValue || !modalBgcolorToken) {
                return;
            }

            var normalized = String(rawToken || '').trim().toLowerCase();

            if (normalized === '') {
                modalBgcolorInput.value          = '';
                modalBgcolorColorInput.value     = '#ff00ff';
                modalBgcolorOpacityInput.value   = '100';
                modalBgcolorOpacityValue.textContent = '100 %';
                modalBgcolorToken.textContent    = 'non défini';
                return;
            }

            if (normalized === 'transparent') {
                modalBgcolorColorInput.value   = '#000000';
                modalBgcolorOpacityInput.value = '0';
                updateModalBgcolorUi();
                return;
            }

            if (/^[0-9a-f]{6}$/i.test(normalized)) {
                modalBgcolorColorInput.value   = '#' + normalized;
                modalBgcolorOpacityInput.value = '100';
                updateModalBgcolorUi();
                return;
            }

            if (/^[0-9a-f]{8}$/i.test(normalized)) {
                var rgb          = normalized.slice(0, 6);
                var alphaHex     = normalized.slice(6, 8);
                var alphaValue   = parseInt(alphaHex, 16);
                var opacityPct   = Math.max(0, Math.min(100, Math.round((alphaValue / 255) * 100)));
                modalBgcolorColorInput.value   = '#' + rgb;
                modalBgcolorOpacityInput.value = String(opacityPct);
                updateModalBgcolorUi();
                return;
            }

            modalBgcolorInput.value       = '';
            modalBgcolorToken.textContent = 'non défini';
        }

        // --- Panneaux d'informations photo ---

        function formatFileSize(bytes) {
            if (!bytes || isNaN(bytes) || bytes <= 0) { return '—'; }
            var n = parseInt(bytes, 10);
            if (n < 1024)    { return n + '\u00a0o'; }
            if (n < 1048576) { return (n / 1024).toFixed(1) + '\u00a0Ko'; }
            return (n / 1048576).toFixed(2) + '\u00a0Mo';
        }

        /**
         * Déduit le type MIME de l'image à partir de l'extension présente dans l'URL.
         * Pour `/photo` sans extension (JPEG par défaut côté serveur), retourne 'image/jpeg'.
         */
        function mimeFromUrl(url) {
            try {
                var pathname = new URL(url, window.location.href).pathname;
                var m = pathname.match(/\.(jpe?g|png|webp|gif)$/i);
                if (!m) { return 'image/jpeg'; }
                var ext = m[1].toLowerCase();
                var map = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp', gif: 'image/gif' };
                return map[ext] || 'image/jpeg';
            } catch (e) { return 'image/jpeg'; }
        }

        /**
         * Lit le poids du fichier via l'API Performance (disponible pour les ressources same-origin).
         * Retourne null si l'entrée n'est pas encore disponible.
         */
        function getSizeFromPerformance(url) {
            if (!window.performance || typeof window.performance.getEntriesByName !== 'function') { return null; }
            var entries = window.performance.getEntriesByName(url);
            if (!entries.length) { return null; }
            var e = entries[entries.length - 1];
            // decodedBodySize = taille réelle décompressée, encodedBodySize = compressé, transferSize = réseau
            var size = e.decodedBodySize || e.encodedBodySize || e.transferSize || 0;
            return size > 0 ? size : null;
        }

        function renderInfoPanel(el, state) {
            if (!el) { return; }

            if (state === null) {
                el.innerHTML = '<span class="loading loading-spinner loading-xs opacity-40"></span>';
                return;
            }

            if (state === false) {
                el.innerHTML = '<span class="opacity-40 text-xs">—</span>';
                return;
            }

            var lines = [];

            if (state.width && state.height) {
                lines.push('<div class="text-primary">' + state.width + '\u00a0\u00d7\u00a0' + state.height + '\u00a0px</div>');
            }

            if (state.mime) {
                lines.push('<div class="opacity-60 text-xs">' + state.mime + '</div>');
            }

            if (state.size) {
                lines.push('<div class="opacity-60 text-xs">' + formatFileSize(state.size) + '</div>');
            }

            el.innerHTML = lines.length ? lines.join('') : '<span class="opacity-40 text-xs">—</span>';
        }

        /**
         * Charge une image et lit ses informations (dimensions, MIME, poids via Performance API).
         * Aucune requête HEAD supplémentaire : tout est issu du chargement natif de l'image.
         */
        function loadPhotoInfo(url, callback) {
            var img = new window.Image();
            img.onload = function () {
                callback({
                    width:  img.naturalWidth,
                    height: img.naturalHeight,
                    mime:   mimeFromUrl(url),
                    size:   getSizeFromPerformance(url),
                });
            };
            img.onerror = function () { callback({}); };
            img.src = url;
        }

        var infoDebounceTimer = null;

        /**
         * Met à jour les deux panneaux d'info après un délai (évite les appels excessifs).
         * Ne charge les infos que si un ID est fourni ; sinon, affiche « — ».
         * Le paramètre previewUrl est l'URL réellement chargée dans le preview (peut contenir _t).
         */
        function scheduleInfoPanelsUpdate(id, originalUrl, cleanUrl, previewUrl) {
            clearTimeout(infoDebounceTimer);

            if (!id) {
                renderInfoPanel(originalInfoEl, false);
                renderInfoPanel(resultInfoEl, false);
                return;
            }

            renderInfoPanel(originalInfoEl, null);
            renderInfoPanel(resultInfoEl, null);

            infoDebounceTimer = window.setTimeout(function () {
                loadPhotoInfo(originalUrl, function (data) { renderInfoPanel(originalInfoEl, data); });

                // Si aucune transformation n'est active, les deux panneaux sont identiques.
                var resultUrl = (cleanUrl === originalUrl) ? originalUrl : cleanUrl;
                loadPhotoInfo(resultUrl, function (data) { renderInfoPanel(resultInfoEl, data); });
            }, 500);
        }

        // --- Zoom de l'aperçu ---

        /**
         * Calcule le facteur de zoom pour que l'image remplisse le container (mode contain).
         * Prend en compte les dimensions réelles de l'image et celles du container.
         */
        function calculateAutoZoom() {
            if (!modalPreview || !previewContainer) { return 1; }
            var iw = modalPreview.naturalWidth  || 0;
            var ih = modalPreview.naturalHeight || 0;
            if (!iw || !ih) { return 1; }
            var cw = previewContainer.clientWidth;
            var ch = previewContainer.clientHeight;
            if (!cw || !ch) { return 1; }
            return Math.min(cw / iw, ch / ih);
        }

        function getEffectiveZoom() {
            return currentZoom === 'auto' ? autoZoomValue : currentZoom;
        }

        function applyZoom() {
            var scale = getEffectiveZoom();
            if (modalPreview) {
                modalPreview.style.transform = 'translate(-50%, -50%) scale(' + scale + ')';
            }
            if (zoomLabel) {
                zoomLabel.textContent = currentZoom === 'auto'
                    ? 'Auto'
                    : Math.round(scale * 100) + '\u00a0%';
            }
        }

        function recalcAutoZoom() {
            autoZoomValue = calculateAutoZoom();
            if (currentZoom === 'auto') {
                applyZoom();
            }
        }

        // Recalcule le zoom auto quand l'image change.
        if (modalPreview) {
            modalPreview.addEventListener('load', recalcAutoZoom);
        }

        // Recalcule quand le container est redimensionné (ex. ouverture du dialog, resize fenêtre).
        if (window.ResizeObserver && previewContainer) {
            new ResizeObserver(recalcAutoZoom).observe(previewContainer);
        }

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', function () {
                var current = getEffectiveZoom();
                var next = null;
                for (var i = 0; i < ZOOM_STEPS.length; i++) {
                    if (ZOOM_STEPS[i] > current + 0.001) { next = ZOOM_STEPS[i]; break; }
                }
                if (next !== null) { currentZoom = next; applyZoom(); }
            });
        }

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', function () {
                var current = getEffectiveZoom();
                var prev = null;
                for (var i = 0; i < ZOOM_STEPS.length; i++) {
                    if (ZOOM_STEPS[i] < current - 0.001) { prev = ZOOM_STEPS[i]; }
                }
                if (prev !== null) { currentZoom = prev; applyZoom(); }
            });
        }

        if (zoomAutoBtn) {
            zoomAutoBtn.addEventListener('click', function () {
                currentZoom = 'auto';
                recalcAutoZoom();
            });
        }

        // --- Construction et analyse d'URL ---

        /**
         * Reconnaît `/photo`, `/photo.jpg`, `/video`, `/logo.svg`, etc.
         * Extrait l'extension du chemin (prioritaire sur ?extension=).
         */
        function parseApiMediaUrl(url) {
            try {
                var parsed       = new URL(url, window.location.href);
                var endpointMatch = parsed.pathname.match(/\/(photo|video|logo)(?:\.([a-z]+))?\/?$/);

                if (!endpointMatch) {
                    return null;
                }

                var urlExtension    = endpointMatch[2] || null;
                var extractedParams = {};

                if (urlExtension) {
                    extractedParams.extension = urlExtension;
                } else {
                    var extFromQuery = parsed.searchParams.get('extension');
                    if (extFromQuery !== null && extFromQuery !== '') {
                        extractedParams.extension = extFromQuery;
                    }
                }

                ['width', 'height', 'fit', 'quality', 'bgcolor'].forEach(function (name) {
                    var value = parsed.searchParams.get(name);
                    if (value !== null && value !== '') {
                        extractedParams[name] = value;
                    }
                });

                return {
                    id:     parsed.searchParams.get('id') || HomeApp.getPhotoId(),
                    params: extractedParams,
                };
            } catch (err) {
                return null;
            }
        }

        function readModalField(name) {
            if (!modalForm) { return ''; }
            var field = modalForm.querySelector('[name="' + name + '"]');
            return field ? String(field.value || '').trim() : '';
        }

        // --- Mise à jour de l'URL générée ---

        function updateModalUrl() {
            if (!modalForm || !modalUrlLink || !modalPreview || !modalIdInput) {
                return;
            }

            var id        = modalIdInput.value.trim();
            var width     = readModalField('width');
            var height    = readModalField('height');
            var fit       = readModalField('fit');
            var extension = readModalField('extension');
            var quality   = readModalField('quality');
            var bgcolor   = readModalField('bgcolor');

            var params = {};
            if (width  !== '') { params.width  = width; }
            if (height !== '') { params.height = height; }
            if (fit    !== '') { params.fit    = fit; }
            if (quality !== '' && quality !== '85') { params.quality = quality; }
            if (bgcolor !== '') { params.bgcolor = bgcolor; }

            var search = new URLSearchParams();
            if (id !== '') { search.set('id', id); }

            Object.keys(params).forEach(function (key) {
                search.set(key, String(params[key]));
            });

            // L'extension est intégrée dans le chemin, pas en query string.
            var effectivePath = extension !== '' ? basePhotoPath + '.' + extension : basePhotoPath;
            var cleanUrl      = effectivePath + (search.toString() ? '?' + search.toString() : '');

            // Pour la prévisualisation sans ID, on force un rechargement via _t.
            var previewSrc;
            if (id === '') {
                var previewSearch = new URLSearchParams(search.toString());
                previewSearch.set('_t', String(Date.now()));
                previewSrc = effectivePath + '?' + previewSearch.toString();
            } else {
                previewSrc = cleanUrl;
            }

            modalUrlLink.href        = cleanUrl;
            modalUrlLink.textContent = cleanUrl;
            modalPreview.src         = previewSrc;
            modalStatus.textContent  = id === '' ? 'Photo aléatoire.' : '';

            // Panneaux d'info (originalUrl = photo sans transforms, cleanUrl = photo transformée)
            var originalUrl = basePhotoPath + (id ? '?id=' + encodeURIComponent(id) : '');
            scheduleInfoPanelsUpdate(id, originalUrl, cleanUrl, previewSrc);
        }

        // --- Ouverture de la modale depuis une URL externe ---

        function openTransformModalFromUrl(url) {
            if (!modal || !modalForm || !modalIdInput) {
                return;
            }

            var parsed = parseApiMediaUrl(url);

            if (!parsed) {
                return;
            }

            modalForm.reset();
            syncModalBgcolorControlsFromToken('');

            modalIdInput.value = parsed.id;

            ['width', 'height', 'fit', 'extension', 'quality', 'bgcolor'].forEach(function (name) {
                var field = modalForm.querySelector('[name="' + name + '"]');
                if (!field) { return; }
                field.value = parsed.params[name] || '';
            });

            // Synchronise le slider de qualité
            if (modalQualityInput && modalQualityValue) {
                var q = parsed.params.quality || '85';
                modalQualityInput.value  = q;
                modalQualityValue.textContent = q;
            }

            syncModalBgcolorControlsFromToken(parsed.params.bgcolor || '');
            currentZoom = 'auto';
            updateModalUrl();

            if (typeof modal.showModal === 'function') {
                modal.showModal();
            }
        }

        // Rend la fonction accessible depuis le script principal.
        window.HomeApp.openTransformModal = openTransformModalFromUrl;

        // --- Écouteurs d'événements ---

        // Slider qualité : mise à jour de l'affichage en temps réel
        if (modalQualityInput && modalQualityValue) {
            modalQualityInput.addEventListener('input', function () {
                modalQualityValue.textContent = modalQualityInput.value;
            });
        }

        if (modalForm) {
            modalForm.addEventListener('input',  updateModalUrl);
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

        modalBgcolorPresetBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!modalBgcolorColorInput || !modalBgcolorOpacityInput) { return; }
                modalBgcolorColorInput.value   = btn.getAttribute('data-color')   || '#ff00ff';
                modalBgcolorOpacityInput.value = btn.getAttribute('data-opacity') || '100';
                updateModalBgcolorUi();
                updateModalUrl();
            });
        });

        if (modalReset && modalForm && modalIdInput) {
            modalReset.addEventListener('click', function () {
                modalForm.reset();
                modalIdInput.value = HomeApp.getPhotoId();
                if (modalQualityValue) { modalQualityValue.textContent = '85'; }
                syncModalBgcolorControlsFromToken('');
                currentZoom = 'auto';
                updateModalUrl();
            });
        }

        // --- Initialisation ---
        syncModalBgcolorControlsFromToken('');
        updateModalUrl();
    })();
</script>

