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
    /** @var list<array{type: string, id: string, fileName: string, mimeType: string, fileSize: int, collection: string|null, url: string}> $items */
    /** @var string $selectedType */
    /** @var string|null $selectedCollection */
    /** @var list<string> $photoCollections */
    /** @var int $limit */
    /** @var int $currentPage */
    /** @var int $totalPages */
    /** @var int $totalItems */
    /** @var string|null $shuffleSeed */
    /** @var string $shuffleUrl */
    /** @var int $idsMissingCount */
    /** @var bool $idsWereSynced */
    /** @var int $idsAddedCount */
    /** @var int $idsRemovedCount */

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
     * Construit une URL de transformation photo à partir d'un ID.
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

    $buildCatalogueUrl = static function (int $page) use ($selectedType, $selectedCollection, $limit, $shuffleSeed): string {
        $query = [
            'type' => $selectedType,
            'limit' => $limit,
            'page' => $page,
        ];

        if ($selectedCollection !== null) {
            $query['collection'] = $selectedCollection;
        }

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

        <?php if ($idsWereSynced && $idsRemovedCount > 0): ?>
            <section class="alert alert-warning mb-6 text-sm">
                Nettoyage automatique effectué : <?= esc((string) $idsRemovedCount) ?> ID(s) orphelin(s) supprimé(s) du manifeste
                (fichier(s) introuvable(s) sur le disque).
                <?php if ($idsAddedCount > 0): ?>
                    <?= esc((string) $idsAddedCount) ?> nouveau(x) média(s) indexé(s) en même temps.
                <?php endif; ?>
            </section>
        <?php elseif ($idsWereSynced): ?>
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

                <?php if ($photoCollections !== [] && in_array($selectedType, ['photo', 'all'], true)): ?>
                    <label class="form-control">
                        <span class="label-text text-sm">Collection</span>
                        <select name="collection" class="select select-bordered" onchange="this.form.submit()">
                            <option value="" <?= $selectedCollection === null ? 'selected' : '' ?>>Toutes</option>
                            <?php foreach ($photoCollections as $col): ?>
                                <option value="<?= esc($col) ?>" <?= $selectedCollection === $col ? 'selected' : '' ?>>
                                    Photos / <?= esc($col) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php else: ?>
                    <input type="hidden" name="collection" value="">
                <?php endif; ?>

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

            <?= view('home/partials/photo-transform-modal', ['defaultDemoPhotoId' => $defaultPhotoId]) ?>
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
                                <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['fileName']) ?>" loading="lazy" class="h-full w-full object-contain">
                            <?php elseif (str_starts_with($item['mimeType'], 'video/')): ?>
                                <video src="<?= esc($item['url']) ?>" class="h-full w-full" controls preload="metadata"></video>
                            <?php else: ?>
                                <div class="flex h-full items-center justify-center text-sm opacity-70">Prévisualisation indisponible</div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body space-y-3 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-outline badge-sm uppercase">
                                        <?= esc($item['type']) ?>
                                    </span>
                                    <?php if ($item['type'] === 'photo' && $item['collection'] !== null): ?>
                                        <span class="badge badge-secondary badge-sm uppercase">
                                            <?= esc($item['collection']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs opacity-70"><?= esc($formatFileSize((int) $item['fileSize'])) ?></span>
                            </div>

                            <p class="truncate text-sm font-medium" title="<?= esc($item['fileName']) ?>">
                                <?= esc($item['fileName']) ?>
                            </p>

                            <div class="rounded-lg border border-base-300 bg-base-200 px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide opacity-70">ID</p>
                                        <p class="mt-0.5 font-mono text-sm text-success"><?= esc($item['id']) ?></p>
                                    </div>
                                    <div class="flex items-center gap-0.5 shrink-0">
                                        <!-- Ouvrir dans un nouvel onglet -->
                                        <a href="<?= esc($item['url']) ?>"
                                           target="_blank" rel="noopener"
                                           class="btn btn-ghost btn-xs"
                                           aria-label="Ouvrir dans un nouvel onglet"
                                           title="Ouvrir par ID">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                        </a>
                                        <!-- Copier l'URL -->
                                        <button type="button"
                                                class="btn btn-ghost btn-xs"
                                                data-catalogue-copy="<?= esc($item['url']) ?>"
                                                aria-label="Copier l'URL"
                                                title="Copier l'URL">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5"><path d="M9 7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2V7Z"/><path d="M5 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h1v-2H5V5h8V4a1 1 0 0 0-1-1H5Z"/></svg>
                                        </button>
                                        <?php if ($item['type'] === 'photo'): ?>
                                            <!-- Ouvrir dans le générateur -->
                                            <button type="button"
                                                    class="btn btn-ghost btn-xs js-use-photo-transform"
                                                    data-photo-id="<?= esc($item['id']) ?>"
                                                    data-photo-name="<?= esc($item['fileName']) ?>"
                                                    aria-label="Ouvrir le générateur"
                                                    title="Transformer">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5"><path d="M14.5 2a.75.75 0 0 1 .73.57l1.02 4.09 4.09 1.02a.75.75 0 0 1 0 1.46l-4.09 1.02-1.02 4.09a.75.75 0 0 1-1.46 0l-1.02-4.09-4.09-1.02a.75.75 0 0 1 0-1.46l4.09-1.02 1.02-4.09A.75.75 0 0 1 14.5 2Z"/><path d="M6 13.25a.75.75 0 0 1 .73.57l.45 1.79 1.79.45a.75.75 0 0 1 0 1.46l-1.79.45-.45 1.79a.75.75 0 0 1-1.46 0l-.45-1.79-1.79-.45a.75.75 0 0 1 0-1.46l1.79-.45.45-1.79A.75.75 0 0 1 6 13.25Z"/></svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                var baseUrl = <?= json_encode(site_url('photo'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                var defaultId = <?= json_encode($defaultPhotoId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                var openDefaultButton = document.getElementById('photo-transform-open-default');
                var copyToast = document.getElementById('global-copy-toast');
                var toastTimer = null;

                function showToast() {
                    if (!copyToast) { return; }
                    copyToast.classList.remove('hidden');
                    if (toastTimer !== null) { clearTimeout(toastTimer); }
                    toastTimer = window.setTimeout(function () { copyToast.classList.add('hidden'); }, 1600);
                }

                // On recrée l'objet HomeApp attendu par photo-transform-modal-script.php
                window.HomeApp = {
                    getPhotoId: function () { return defaultId; },
                    basePhotoPath: baseUrl,
                    showToast: showToast
                };

                if (openDefaultButton) {
                    openDefaultButton.addEventListener('click', function () {
                        if (window.HomeApp.openTransformModal) {
                            var url = baseUrl + '?id=' + encodeURIComponent(defaultId);
                            window.HomeApp.openTransformModal(url);
                        }
                    });
                }

                // Clic sur "Transformer" sur une carte photo
                document.addEventListener('click', function (event) {
                    // Icône copier dans une carte catalogue
                    var copyBtn = event.target.closest('[data-catalogue-copy]');
                    if (copyBtn) {
                        var urlToCopy = copyBtn.getAttribute('data-catalogue-copy') || '';
                        if (navigator.clipboard && urlToCopy) {
                            navigator.clipboard.writeText(urlToCopy).then(showToast);
                        }
                        return;
                    }

                    var button = event.target.closest('.js-use-photo-transform');
                    if (button) {
                        var nextId = button.getAttribute('data-photo-id') || '';
                        if (window.HomeApp.openTransformModal && nextId) {
                            var url = baseUrl + '?id=' + encodeURIComponent(nextId);
                            window.HomeApp.openTransformModal(url);
                        }
                        return;
                    }

                    var card = event.target.closest('.js-photo-item');
                    if (card && !event.target.closest('a, button, input, select, textarea, label, video, summary, details')) {
                        var cardPhotoId = card.getAttribute('data-photo-id') || '';
                        if (window.HomeApp.openTransformModal && cardPhotoId) {
                            var cUrl = baseUrl + '?id=' + encodeURIComponent(cardPhotoId);
                            window.HomeApp.openTransformModal(cUrl);
                        }
                    }
                });
            })();
        </script>
        <?= view('home/partials/photo-transform-modal-script', ['defaultDemoPhotoId' => $defaultPhotoId]) ?>
    <?php endif; ?>
    <div id="global-copy-toast" class="toast toast-top toast-end hidden z-[60]">
        <div class="alert alert-success py-2 px-3 text-sm">
            <span>copié</span>
        </div>
    </div>
<?= view('partials/footer') ?>

