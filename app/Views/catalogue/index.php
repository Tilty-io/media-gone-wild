<?= view('partials/header', ['pageTitle' => 'Catalogue médias - Media Gone Wild']) ?>
<style>
        .media-preview-checkerboard {
            background-color: hsl(0 0% 92%);
            background-image:
                linear-gradient(45deg, rgb(0 0 0 / 30%) 25%, transparent 25%),
                linear-gradient(-45deg, rgb(0 0 0 / 30%) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, rgb(0 0 0 / 30%) 75%),
                linear-gradient(-45deg, transparent 75%, rgb(0 0 0 / 30%) 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0;
        }

        [data-theme="dark"] .media-preview-checkerboard {
            background-color: hsl(220 15% 20%);
            background-image:
                linear-gradient(45deg, rgb(255 255 255 / 30%) 25%, transparent 25%),
                linear-gradient(-45deg, rgb(255 255 255 / 30%) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, rgb(255 255 255 / 30%) 75%),
                linear-gradient(-45deg, transparent 75%, rgb(255 255 255 / 30%) 75%);
        }
</style>
<?php
    /** @var list<array{type: string, id: string, fileName: string, mimeType: string, fileSize: int, url: string}> $items */
    /** @var string $selectedType */
    /** @var int $limit */
    /** @var int $currentPage */
    /** @var int $totalPages */
    /** @var int $totalItems */
    /** @var string|null $shuffleSeed */
    /** @var string $shuffleUrl */
    /** @var int $idsMissingCount */
    /** @var bool $idsWereSynced */
    /** @var int $idsAddedCount */

    $photoItems = array_values(array_filter(
        $items,
        static fn (array $item): bool => $item['type'] === 'photo',
    ));

    $defaultPhotoId = $photoItems[0]['id'] ?? '';
    $allowedFitModes = \App\DTO\MediaTransformOptions::ALLOWED_FIT_MODES;
    $allowedExtensions = array_values(array_filter(
        \App\DTO\MediaTransformOptions::ALLOWED_EXTENSIONS,
        static fn (string $extension): bool => $extension !== 'jpeg',
    ));

    /**
     * Construit une URL de transformation photo à partir d'un ID exact.
     *
     * @param string $id L'identifiant stable de la photo.
     * @param array<string, scalar|null> $params Les paramètres de transformation à ajouter.
     */
    $buildPhotoTransformUrl = static function (string $id, array $params = []): string {
        $query = ['id' => $id];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query[$key] = (string) $value;
        }

        return site_url('photo') . '?' . http_build_query($query);
    };

    $buildCatalogueUrl = static function (int $page) use ($selectedType, $limit, $shuffleSeed): string {
        $query = [
            'type' => $selectedType,
            'limit' => $limit,
            'page' => $page,
        ];

        if ($shuffleSeed !== null) {
            $query['shuffle'] = $shuffleSeed;
        }

        return site_url('catalogue') . '?' . http_build_query($query);
    };

    // Formate une taille de fichier dans une unité plus lisible pour l'interface.
    $formatFileSize = static function (int $bytes): string {
        if ($bytes < 1024) {
            return number_format($bytes, 0, ',', ' ') . ' octets';
        }

        $units = ['Ko', 'Mo', 'Go', 'To'];
        $value = (float) $bytes;
        $unitIndex = -1;

        do {
            $value /= 1024;
            $unitIndex++;
        } while ($value >= 1024 && isset($units[$unitIndex + 1]));

        $precision = $value >= 10 ? 0 : 1;

        return number_format($value, $precision, ',', ' ') . ' ' . $units[$unitIndex];
    };
    ?>
    <main class="mx-auto w-full max-w-7xl px-6 py-10 pt-28">
        <header class="mb-8 rounded-box border border-base-300 bg-base-100 px-5 py-4 shadow-sm">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-primary">Parcourir les médias</p>
            <h1 class="text-3xl font-bold">Catalogue</h1>
            <p class="mt-2 text-sm opacity-80">Filtre par type et explore les fichiers disponibles.</p>
        </header>

        <?php if ($idsWereSynced): ?>
            <section class="alert alert-success mb-6 text-sm">
                Synchronisation automatique des IDs effectuée (<?= esc((string) $idsAddedCount) ?> média(s) ajouté(s) au manifeste).
            </section>
        <?php endif; ?>

        <?php if ($idsMissingCount > 0): ?>
            <section class="alert alert-warning mb-6 text-sm">
                Des médias n'ont pas encore d'ID stable (<?= esc((string) $idsMissingCount) ?>). Lancez
                <code class="rounded bg-base-300 px-1 py-0.5">php spark media:sync-ids</code>
                pour compléter <code class="rounded bg-base-300 px-1 py-0.5">media/ids.json</code>.
            </section>
        <?php endif; ?>

        <section class="mb-8 rounded-box border border-base-300 bg-base-100 p-4 shadow-sm">
            <form method="get" action="<?= esc(site_url('catalogue')) ?>" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="page" value="1">
                <label class="form-control">
                    <span class="label-text text-sm">Type</span>
                    <select name="type" class="select select-bordered" onchange="this.form.submit()">
                        <option value="all" <?= $selectedType === 'all' ? 'selected' : '' ?>>Tout</option>
                        <option value="photo" <?= $selectedType === 'photo' ? 'selected' : '' ?>>Photo</option>
                        <option value="video" <?= $selectedType === 'video' ? 'selected' : '' ?>>Vidéo</option>
                        <option value="logo" <?= $selectedType === 'logo' ? 'selected' : '' ?>>Logo</option>
                    </select>
                </label>

                <label class="form-control">
                    <span class="label-text text-sm">Limite</span>
                    <input type="number" name="limit" min="1" max="500" step="1" value="<?= esc((string) $limit) ?>" class="input input-bordered w-28" onchange="this.form.submit()">
                </label>

                <a href="<?= esc($shuffleUrl) ?>" class="btn btn-accent btn-sm">Mélanger</a>

                <p class="text-sm opacity-70">
                    <?= esc((string) $totalItems) ?> média(s) au total
                </p>
            </form>
        </section>

        <?php if ($photoItems !== []): ?>
            <section class="mb-8 rounded-box border border-primary/20 bg-base-100 p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Générateur d'URL photo</p>
                        <h2 class="mt-1 text-xl font-semibold">Générateur en popin</h2>
                        <p class="mt-2 max-w-3xl text-sm opacity-80">
                            Clique sur une carte photo (ou sur le bouton dédié) pour ouvrir le générateur dans une popin.
                            Les paramètres déjà saisis sont conservés automatiquement ; seul l'ID photo change.
                        </p>
                    </div>

                    <button id="photo-transform-open-default" type="button" class="btn btn-primary btn-sm">
                        Ouvrir le générateur
                    </button>
                </div>
            </section>

            <dialog id="photo-transform-modal" class="modal">
                <div class="modal-box w-11/12 max-w-5xl">
                    <form method="dialog" class="absolute right-2 top-2">
                        <button class="btn btn-circle btn-ghost btn-sm" aria-label="Fermer">✕</button>
                    </form>

                    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Générateur d'URL photo</p>
                    <h2 id="photo-transform-modal-title" class="mt-1 text-xl font-semibold">Compose une URL de transformation prête à copier</h2>

                    <div class="mt-3 rounded-lg border border-base-300 bg-base-200 px-3 py-2 text-xs opacity-80">
                        <p>Alpha sur 8 caractères : <code class="rounded bg-base-300 px-1">ffffff00</code> = transparent</p>
                        <p><code class="rounded bg-base-300 px-1">ffffff80</code> = semi-transparent, <code class="rounded bg-base-300 px-1">transparent</code> = alias de <code class="rounded bg-base-300 px-1">00000000</code></p>
                    </div>

                    <form
                        id="photo-transform-builder"
                        class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                        data-base-url="<?= esc(site_url('photo')) ?>"
                        data-default-id="<?= esc($defaultPhotoId) ?>"
                    >
                    <label class="form-control xl:col-span-2">
                        <span class="label-text text-sm">ID photo exact</span>
                        <input id="photo-transform-id" name="id" type="text" value="<?= esc($defaultPhotoId) ?>" placeholder="abc123def456" class="input input-bordered font-mono">
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Largeur</span>
                        <input name="width" type="number" min="1" step="1" placeholder="800" class="input input-bordered">
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Hauteur</span>
                        <input name="height" type="number" min="1" step="1" placeholder="600" class="input input-bordered">
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Fit</span>
                        <select name="fit" class="select select-bordered">
                            <option value="">contain (par défaut si width + height)</option>
                            <?php foreach ($allowedFitModes as $fitMode): ?>
                                <option value="<?= esc($fitMode) ?>"><?= esc($fitMode) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Extension</span>
                        <select name="extension" class="select select-bordered">
                            <option value="">Originale</option>
                            <?php foreach ($allowedExtensions as $extension): ?>
                                <option value="<?= esc($extension) ?>"><?= esc($extension) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Qualité</span>
                        <input name="quality" type="number" min="1" max="100" step="1" value="85" class="input input-bordered">
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Couleur de fond</span>
                        <input name="bgcolor" type="text" placeholder="ffffff00 ou transparent" class="input input-bordered font-mono">
                    </label>

                    <div class="form-control xl:col-span-4">
                        <span class="label-text text-sm">URL générée (clique pour copier)</span>
                        <div
                            id="photo-transform-url"
                            class="mt-1 max-h-40 overflow-auto whitespace-pre-wrap break-all rounded-lg bg-base-200 px-3 py-3 font-mono text-sm md:text-base cursor-copy"
                            role="button"
                            tabindex="0"
                            title="Clique pour copier l'URL générée"
                        ></div>
                    </div>

                    <div class="xl:col-span-4 rounded-lg border border-base-300 bg-base-200 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-70">Aperçu direct</p>
                        <div class="media-preview-checkerboard mt-2 flex max-h-[70vh] items-center justify-center overflow-auto rounded-lg border border-base-300 bg-base-100 p-2">
                            <img
                                id="photo-transform-preview"
                                src=""
                                alt="Aperçu de l'image générée"
                                loading="lazy"
                                class="hidden h-auto w-auto max-w-none"
                            >
                            <div id="photo-transform-preview-placeholder" class="flex min-h-56 min-w-full items-center justify-center px-4 text-center text-sm opacity-70">
                                L'aperçu s'affiche ici dès qu'un ID photo valide est renseigné.
                            </div>
                        </div>
                        <p id="photo-transform-preview-status" class="mt-2 text-xs opacity-70" aria-live="polite">
                            En attente d'un ID photo pour afficher l'aperçu.
                        </p>
                    </div>

                    <div class="xl:col-span-4 flex flex-wrap items-center gap-3">
                        <a id="photo-transform-open" href="<?= esc(site_url('photo')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Ouvrir l'URL</a>
                        <button id="photo-transform-copy" type="button" class="btn btn-outline btn-sm">Copier l'URL</button>
                        <button id="photo-transform-reset" type="button" class="btn btn-ghost btn-sm">Réinitialiser</button>
                        <p id="photo-transform-status" class="text-sm opacity-70"></p>
                    </div>
                    </form>

                    <div class="modal-action">
                        <form method="dialog">
                            <button class="btn btn-ghost btn-sm">Fermer</button>
                        </form>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>Fermer</button>
                </form>
            </dialog>
        <?php endif; ?>

        <?php if ($items === []): ?>
            <section class="alert alert-warning">
                Aucun média disponible pour ce filtre.
            </section>
        <?php else: ?>
            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($items as $item): ?>
                    <?php
                    $photoExamples = $item['type'] === 'photo'
                        ? [
                            [
                                'label' => 'Largeur 1200 JPG',
                                'url' => $buildPhotoTransformUrl($item['id'], ['width' => 1200]),
                            ],
                            [
                                'label' => 'Carré PNG transparent',
                                'url' => $buildPhotoTransformUrl($item['id'], [
                                    'width' => 256,
                                    'height' => 256,
                                    'fit' => 'contain',
                                    'extension' => 'png',
                                    'bgcolor' => 'ffffff00',
                                ]),
                            ],
                            [
                                'label' => 'Cover WebP 1200×630',
                                'url' => $buildPhotoTransformUrl($item['id'], [
                                    'width' => 1200,
                                    'height' => 630,
                                    'fit' => 'cover',
                                    'extension' => 'webp',
                                    'quality' => 90,
                                ]),
                            ],
                        ]
                        : [];
                    ?>
                    <article
                        class="card overflow-hidden border border-base-300 bg-base-100 shadow-sm <?= $item['type'] === 'photo' ? 'js-photo-item cursor-pointer transition hover:border-primary/40' : '' ?>"
                        <?php if ($item['type'] === 'photo'): ?>
                            data-photo-id="<?= esc($item['id']) ?>"
                            data-photo-name="<?= esc($item['fileName']) ?>"
                        <?php endif; ?>
                    >
                        <div class="media-preview-checkerboard aspect-video <?= $item['type'] === 'logo' ? 'p-4' : '' ?>">
                            <?php if (str_starts_with($item['mimeType'], 'image/')): ?>
                                <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['fileName']) ?>" class="h-full w-full object-contain">
                            <?php elseif (str_starts_with($item['mimeType'], 'video/')): ?>
                                <video src="<?= esc($item['url']) ?>" class="h-full w-full" controls preload="metadata"></video>
                            <?php else: ?>
                                <div class="flex h-full items-center justify-center text-sm opacity-70">Prévisualisation indisponible</div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body space-y-3 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="badge badge-outline uppercase">
                                    <?= esc($item['type']) ?>
                                </span>
                                <span class="text-xs opacity-70"><?= esc($formatFileSize((int) $item['fileSize'])) ?></span>
                            </div>

                            <p class="truncate text-sm font-medium" title="<?= esc($item['fileName']) ?>">
                                <?= esc($item['fileName']) ?>
                            </p>

                            <div class="rounded-lg border border-base-300 bg-base-200 px-3 py-2">
                                <p class="text-[11px] uppercase tracking-wide opacity-70">ID exact</p>
                                <p class="mt-1 font-mono text-sm text-success"><?= esc($item['id']) ?></p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="<?= esc($item['url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Ouvrir par ID</a>
                                <?php if ($item['type'] === 'photo'): ?>
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm js-use-photo-transform"
                                        data-photo-id="<?= esc($item['id']) ?>"
                                        data-photo-name="<?= esc($item['fileName']) ?>"
                                    >Configurer transformation</button>
                                <?php endif; ?>
                            </div>

                            <?php if ($photoExamples !== []): ?>
                                <div class="rounded-lg border border-base-300 bg-base-200 px-3 py-3">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-70">Exemples directs</p>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs js-use-photo-transform"
                                            data-photo-id="<?= esc($item['id']) ?>"
                                            data-photo-name="<?= esc($item['fileName']) ?>"
                                        >Utiliser dans le générateur</button>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($photoExamples as $example): ?>
                                            <a
                                                href="<?= esc($example['url']) ?>"
                                                target="_blank"
                                                rel="noopener"
                                                class="btn btn-xs btn-outline"
                                                title="<?= esc($example['url']) ?>"
                                            ><?= esc($example['label']) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-10 flex flex-col items-center gap-4" aria-label="Pagination du catalogue">
                    <?php $previousPage = max(1, $currentPage - 1); ?>
                    <?php $nextPage = min($totalPages, $currentPage + 1); ?>
                    <?php $windowStart = max(1, $currentPage - 2); ?>
                    <?php $windowEnd = min($totalPages, $currentPage + 2); ?>

                    <p class="text-sm opacity-70">
                        Page <span class="font-semibold"><?= esc((string) $currentPage) ?></span>
                        sur <span class="font-semibold"><?= esc((string) $totalPages) ?></span>
                    </p>

                    <div class="join flex-wrap justify-center rounded-full bg-base-200/60 p-1">
                        <a
                            href="<?= esc($buildCatalogueUrl($previousPage)) ?>"
                            class="join-item btn btn-ghost btn-sm border-0 shadow-none <?= $currentPage === 1 ? 'btn-disabled opacity-50' : '' ?>"
                            <?= $currentPage === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                        >Précédent</a>

                        <?php if ($windowStart > 1): ?>
                            <a href="<?= esc($buildCatalogueUrl(1)) ?>" class="join-item btn btn-ghost btn-sm border-0 shadow-none">1</a>

                            <?php if ($windowStart > 2): ?>
                                <span class="join-item btn btn-ghost btn-sm border-0 opacity-50">…</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++): ?>
                            <a
                                href="<?= esc($buildCatalogueUrl($pageNumber)) ?>"
                                class="join-item btn btn-sm border-0 shadow-none <?= $pageNumber === $currentPage ? 'btn-primary' : 'btn-ghost' ?>"
                                <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>
                            ><?= esc((string) $pageNumber) ?></a>
                        <?php endfor; ?>

                        <?php if ($windowEnd < $totalPages): ?>
                            <?php if ($windowEnd < $totalPages - 1): ?>
                                <span class="join-item btn btn-ghost btn-sm border-0 opacity-50">…</span>
                            <?php endif; ?>

                            <a href="<?= esc($buildCatalogueUrl($totalPages)) ?>" class="join-item btn btn-ghost btn-sm border-0 shadow-none"><?= esc((string) $totalPages) ?></a>
                        <?php endif; ?>

                        <a
                            href="<?= esc($buildCatalogueUrl($nextPage)) ?>"
                            class="join-item btn btn-ghost btn-sm border-0 shadow-none <?= $currentPage === $totalPages ? 'btn-disabled opacity-50' : '' ?>"
                            <?= $currentPage === $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                        >Suivant</a>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php if ($photoItems !== []): ?>
        <script>
            (function () {
                var builder = document.getElementById('photo-transform-builder');
                var modal = document.getElementById('photo-transform-modal');
                var openDefaultButton = document.getElementById('photo-transform-open-default');
                var modalTitle = document.getElementById('photo-transform-modal-title');

                if (!builder) {
                    return;
                }

                var baseUrl = builder.getAttribute('data-base-url') || '';
                var defaultId = builder.getAttribute('data-default-id') || '';
                var output = document.getElementById('photo-transform-url');
                var status = document.getElementById('photo-transform-status');
                var openLink = document.getElementById('photo-transform-open');
                var copyButton = document.getElementById('photo-transform-copy');
                var resetButton = document.getElementById('photo-transform-reset');
                var idInput = document.getElementById('photo-transform-id');
                var previewImage = document.getElementById('photo-transform-preview');
                var previewPlaceholder = document.getElementById('photo-transform-preview-placeholder');
                var previewStatus = document.getElementById('photo-transform-preview-status');
                var previewSequence = 0;
                var activePhotoId = defaultId;
                var storageKey = 'mgw-photo-transform-params-v1';
                var persistedFields = ['width', 'height', 'fit', 'extension', 'quality', 'bgcolor'];

                if (!builder || !modal || !output || !status || !openLink || !copyButton || !resetButton || !idInput || !previewImage || !previewPlaceholder || !previewStatus) {
                    return;
                }

                function setStatus(message) {
                    status.textContent = message;
                }

                function copyGeneratedUrl() {
                    var generatedUrl = String(output.textContent || '').trim();

                    if (!generatedUrl) {
                        setStatus('Aucune URL à copier.');
                        return;
                    }

                    if (!navigator.clipboard) {
                        var range = document.createRange();
                        range.selectNodeContents(output);

                        var selection = window.getSelection();
                        if (selection) {
                            selection.removeAllRanges();
                            selection.addRange(range);
                        }

                        document.execCommand('copy');

                        if (selection) {
                            selection.removeAllRanges();
                        }

                        setStatus('URL copiée.');
                        return;
                    }

                    navigator.clipboard.writeText(generatedUrl).then(function () {
                        setStatus('URL copiée.');
                    }).catch(function () {
                        setStatus('Impossible de copier automatiquement l\'URL.');
                    });
                }

                function readField(name) {
                    var field = builder.querySelector('[name="' + name + '"]');

                    if (!field) {
                        return '';
                    }

                    return String(field.value || '').trim();
                }

                function getField(name) {
                    return builder.querySelector('[name="' + name + '"]');
                }

                function loadPersistedParams() {
                    try {
                        var raw = localStorage.getItem(storageKey);

                        if (!raw) {
                            return;
                        }

                        var parsed = JSON.parse(raw);

                        if (!parsed || typeof parsed !== 'object') {
                            return;
                        }

                        persistedFields.forEach(function (fieldName) {
                            var field = getField(fieldName);

                            if (!field || !(fieldName in parsed)) {
                                return;
                            }

                            var value = parsed[fieldName];
                            field.value = typeof value === 'string' ? value : '';
                        });
                    } catch (error) {
                        // Ignore un stockage invalide sans bloquer l'interface.
                    }
                }

                function savePersistedParams() {
                    var payload = {};

                    persistedFields.forEach(function (fieldName) {
                        payload[fieldName] = readField(fieldName);
                    });

                    try {
                        localStorage.setItem(storageKey, JSON.stringify(payload));
                    } catch (error) {
                        // Ignore l'absence de localStorage (mode privé, quota, etc.).
                    }
                }

                function clearPersistedParams() {
                    try {
                        localStorage.removeItem(storageKey);
                    } catch (error) {
                        // Ignore silencieusement en cas d'indisponibilité.
                    }
                }

                function showPreviewPlaceholder(message) {
                    previewImage.classList.add('hidden');
                    previewPlaceholder.classList.remove('hidden');
                    previewPlaceholder.textContent = message;
                    previewStatus.textContent = message;
                }

                function refreshPreview(url, id) {
                    if (id === '') {
                        previewImage.removeAttribute('src');
                        showPreviewPlaceholder('Ajoute un ID photo pour afficher l\'aperçu.');
                        return;
                    }

                    var token = String(++previewSequence);
                    previewImage.dataset.previewToken = token;

                    previewImage.classList.add('hidden');
                    previewPlaceholder.textContent = 'Chargement de l\'aperçu...';
                    previewStatus.textContent = 'Chargement de l\'aperçu...';

                    previewImage.onload = function () {
                        if (previewImage.dataset.previewToken !== token) {
                            return;
                        }

                        previewImage.classList.remove('hidden');
                        previewPlaceholder.classList.add('hidden');
                        previewPlaceholder.textContent = '';
                        previewStatus.textContent = 'Aperçu à jour en taille réelle : ' + previewImage.naturalWidth + '×' + previewImage.naturalHeight + ' px.';
                    };

                    previewImage.onerror = function () {
                        if (previewImage.dataset.previewToken !== token) {
                            return;
                        }

                        previewImage.classList.add('hidden');
                        previewPlaceholder.classList.remove('hidden');
                        previewPlaceholder.textContent = 'Impossible de charger l\'aperçu (ID ou paramètres invalides).';
                        previewStatus.textContent = 'Erreur de chargement de l\'aperçu.';
                    };

                    previewImage.src = url;
                }

                function openModalForPhoto(photoId, photoName) {
                    if (photoId !== '') {
                        activePhotoId = photoId;
                        idInput.value = photoId;
                    }

                    if (modalTitle) {
                        modalTitle.textContent = photoName !== ''
                            ? 'Transformation de ' + photoName
                            : 'Compose une URL de transformation prête à copier';
                    }

                    updateUrl();

                    if (typeof modal.showModal === 'function') {
                        modal.showModal();
                    }
                }

                function updateUrl() {
                    var params = new URLSearchParams();
                    var id = idInput.value.trim();
                    var width = readField('width');
                    var height = readField('height');
                    var fit = readField('fit');
                    var extension = readField('extension');
                    var quality = readField('quality');
                    var bgcolor = readField('bgcolor');

                    if (id !== '') {
                        params.set('id', id);
                    }

                    if (width !== '') {
                        params.set('width', width);
                    }

                    if (height !== '') {
                        params.set('height', height);
                    }

                    if (fit !== '') {
                        params.set('fit', fit);
                    }

                    if (extension !== '') {
                        params.set('extension', extension);
                    }

                    if (quality !== '' && quality !== '85') {
                        params.set('quality', quality);
                    }

                    if (bgcolor !== '') {
                        params.set('bgcolor', bgcolor);
                    }

                    var url = baseUrl + (params.toString() ? '?' + params.toString() : '');

                    output.textContent = url;
                    openLink.href = url;
                    refreshPreview(url, id);
                }

                builder.addEventListener('input', function () {
                    setStatus('');
                    savePersistedParams();
                    updateUrl();
                });

                builder.addEventListener('change', function () {
                    setStatus('');
                    savePersistedParams();
                    updateUrl();
                });

                copyButton.addEventListener('click', function () {
                    copyGeneratedUrl();
                });

                output.addEventListener('click', function () {
                    copyGeneratedUrl();
                });

                output.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        copyGeneratedUrl();
                    }
                });

                resetButton.addEventListener('click', function () {
                    builder.reset();
                    idInput.value = activePhotoId || defaultId;
                    clearPersistedParams();
                    setStatus('Générateur réinitialisé.');
                    updateUrl();
                });

                if (openDefaultButton) {
                    openDefaultButton.addEventListener('click', function () {
                        openModalForPhoto(activePhotoId || defaultId, '');
                    });
                }

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('.js-use-photo-transform');

                    if (!button) {
                        return;
                    }

                    var nextId = button.getAttribute('data-photo-id') || '';
                    var nextName = button.getAttribute('data-photo-name') || '';
                    openModalForPhoto(nextId, nextName);
                    setStatus('ID photo injecté dans le générateur.');
                });

                document.addEventListener('click', function (event) {
                    var card = event.target.closest('.js-photo-item');

                    if (!card) {
                        return;
                    }

                    if (event.target.closest('a, button, input, select, textarea, label, video, summary, details')) {
                        return;
                    }

                    var cardPhotoId = card.getAttribute('data-photo-id') || '';
                    var cardPhotoName = card.getAttribute('data-photo-name') || '';
                    openModalForPhoto(cardPhotoId, cardPhotoName);
                    setStatus('ID photo injecté dans le générateur.');
                });

                loadPersistedParams();
                updateUrl();
            })();
        </script>
    <?php endif; ?>
<?= view('partials/footer') ?>

