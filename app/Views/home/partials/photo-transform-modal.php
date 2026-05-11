<?php
/** @var string $defaultDemoPhotoId */

$allowedFitModes = \App\DTO\MediaTransformOptions::ALLOWED_FIT_MODES;
$allowedExtensions = array_values(array_filter(
    \App\DTO\MediaTransformOptions::ALLOWED_EXTENSIONS,
    static fn (string $ext): bool => $ext !== 'jpeg',
));

$initialModalUrl = site_url('photo') . '?' . http_build_query(['id' => $defaultDemoPhotoId]);
?>
<dialog id="home-photo-transform-modal" class="modal">
    <div class="modal-box flex h-[100dvh] w-screen max-w-none flex-col overflow-hidden rounded-none p-0">

        <!-- En-tête -->
        <header class="flex shrink-0 items-center justify-between border-b border-base-300 bg-base-200 px-4 py-2">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-primary opacity-80">Transformation photo</p>
                <p class="text-sm font-semibold leading-tight">Ajuster l'URL /photo</p>
            </div>
            <form method="dialog">
                <button class="btn btn-circle btn-ghost btn-sm" aria-label="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </form>
        </header>

        <!-- Corps : photo (gauche/haut) + toolbar scrollable (droite/bas) -->
        <form id="home-photo-transform-builder"
              class="flex min-h-0 flex-1 flex-col overflow-hidden lg:flex-row">

            <!-- Zone photo : image centrée + zoom via transform scale -->
            <div id="modal-preview-container"
                 class="media-preview-checkerboard relative min-h-[30dvh] flex-1 overflow-hidden bg-base-200">

                <img
                    id="home-photo-transform-preview"
                    src="<?= esc($initialModalUrl) ?>"
                    alt="Aperçu de l'image générée"
                    loading="lazy"
                    class="absolute left-1/2 top-1/2 h-auto w-auto max-w-none"
                    style="transform: translate(-50%, -50%) scale(1); transform-origin: center center; transition: transform 0.12s ease;">

                <!-- Contrôles de zoom -->
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-0.5 rounded-full bg-base-100/80 px-2 py-1 shadow backdrop-blur">
                    <button type="button" id="modal-zoom-out"
                            class="btn btn-ghost btn-xs h-6 min-h-0 px-2"
                            aria-label="Dézoomer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                    </button>
                    <span id="modal-zoom-label"
                          class="min-w-[3.5rem] text-center font-mono text-[11px]">Auto</span>
                    <button type="button" id="modal-zoom-in"
                            class="btn btn-ghost btn-xs h-6 min-h-0 px-2"
                            aria-label="Zoomer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="11" x2="11" y1="8" y2="14"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                    </button>
                    <span class="mx-1 opacity-20 select-none">|</span>
                    <button type="button" id="modal-zoom-auto"
                            class="btn btn-ghost btn-xs h-6 min-h-0 px-2 opacity-60"
                            aria-label="Zoom automatique"
                            title="Ajuster automatiquement">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Toolbar scrollable -->
            <div class="flex max-h-[55dvh] shrink-0 flex-col gap-0 divide-y divide-base-300 overflow-y-auto border-t border-base-300 bg-base-100 lg:max-h-none lg:w-72 lg:border-l lg:border-t-0">

                <!-- ID -->
                <section class="px-4 py-3">
                    <label class="label p-0 mb-1.5">
                        <span class="label-text text-[10px] font-semibold uppercase tracking-widest opacity-50">ID de la photo</span>
                    </label>
                    <input id="home-photo-transform-id" name="id"
                           type="text"
                           value="<?= esc($defaultDemoPhotoId) ?>"
                           placeholder="vide = aléatoire"
                           class="input input-sm input-bordered font-mono text-xs w-full">
                    <p id="home-photo-transform-status" class="mt-1.5 text-[10px] opacity-40" aria-live="polite"></p>
                </section>

                <!-- Dimensions -->
                <section class="px-4 py-3 flex flex-col gap-2">
                    <p class="text-[10px] font-semibold uppercase tracking-widest opacity-50">Dimensions</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label p-0 mb-1">
                                <span class="label-text text-[10px] opacity-60">Largeur</span>
                            </label>
                            <div class="input input-sm input-bordered flex items-center gap-1 pr-2">
                                <input name="width" type="number" min="1" step="1" placeholder="—"
                                       class="w-full bg-transparent text-xs outline-none">
                                <span class="shrink-0 text-[10px] opacity-40">px</span>
                            </div>
                        </div>
                        <div>
                            <label class="label p-0 mb-1">
                                <span class="label-text text-[10px] opacity-60">Hauteur</span>
                            </label>
                            <div class="input input-sm input-bordered flex items-center gap-1 pr-2">
                                <input name="height" type="number" min="1" step="1" placeholder="—"
                                       class="w-full bg-transparent text-xs outline-none">
                                <span class="shrink-0 text-[10px] opacity-40">px</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="label p-0 mb-1">
                            <span class="label-text text-[10px] opacity-60">Fit</span>
                        </label>
                        <select name="fit" class="select select-sm select-bordered w-full text-xs">
                            <option value="">contain (par défaut)</option>
                            <?php foreach ($allowedFitModes as $fitMode): ?>
                                <option value="<?= esc($fitMode) ?>"><?= esc($fitMode) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <!-- Format & Qualité -->
                <section class="px-4 py-3 flex flex-col gap-3">
                    <p class="text-[10px] font-semibold uppercase tracking-widest opacity-50">Format & Qualité</p>
                    <div>
                        <label class="label p-0 mb-1">
                            <span class="label-text text-[10px] opacity-60">Format de sortie</span>
                        </label>
                        <select name="extension" class="select select-sm select-bordered w-full text-xs">
                            <option value="">Original</option>
                            <?php foreach ($allowedExtensions as $ext): ?>
                                <option value="<?= esc($ext) ?>"><?= strtoupper(esc($ext)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label p-0 mb-1.5">
                            <span class="label-text text-[10px] opacity-60">Qualité</span>
                            <span id="home-photo-transform-quality-value" class="label-text-alt font-mono text-xs text-primary">85</span>
                        </label>
                        <input id="home-photo-transform-quality" name="quality"
                               type="range" min="1" max="100" step="1" value="85"
                               class="range range-primary range-xs w-full">
                        <div class="mt-0.5 flex justify-between px-0.5 text-[9px] opacity-30">
                            <span>1</span><span>50</span><span>100</span>
                        </div>
                    </div>
                </section>

                <!-- Couleur de fond -->
                <section class="px-4 py-3 flex flex-col gap-3">
                    <p class="text-[10px] font-semibold uppercase tracking-widest opacity-50">Couleur de fond</p>
                    <input id="home-photo-transform-bgcolor" name="bgcolor" type="hidden" value="">
                    <div class="flex flex-wrap gap-1">
                        <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#ff00ff" data-opacity="100">Magenta</button>
                        <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#00ffff" data-opacity="100">Cyan</button>
                        <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#39ff14" data-opacity="100">Vert</button>
                        <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#ff5e00" data-opacity="100">Orange</button>
                        <button type="button" class="btn btn-xs btn-outline" data-home-photo-transform-bgcolor-preset data-color="#ff00ff" data-opacity="50">50 %</button>
                        <button type="button" class="btn btn-xs btn-outline" data-home-photo-transform-bgcolor-preset data-color="#00ffff" data-opacity="0">Transparent</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label p-0 mb-1">
                                <span class="label-text text-[10px] opacity-60">Couleur</span>
                            </label>
                            <input id="home-photo-transform-bgcolor-color" type="color" value="#ff00ff"
                                   class="input input-sm input-bordered h-9 w-full cursor-pointer p-0.5">
                        </div>
                        <div>
                            <label class="label p-0 mb-1.5">
                                <span class="label-text text-[10px] opacity-60">Opacité</span>
                                <span id="home-photo-transform-bgcolor-opacity-value" class="label-text-alt font-mono text-[10px]">100 %</span>
                            </label>
                            <input id="home-photo-transform-bgcolor-opacity" type="range"
                                   min="0" max="100" step="1" value="100"
                                   class="range range-primary range-xs mt-1.5 w-full">
                        </div>
                    </div>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[9px] font-semibold uppercase tracking-wider opacity-40">bgcolor généré</p>
                        <p id="home-photo-transform-bgcolor-token" class="mt-0.5 font-mono text-xs text-primary">non défini</p>
                    </div>
                </section>

                <!-- URL générée -->
                <section class="px-4 py-3" id="home-photo-transform-url-host">
                    <?= view('components/api-url-actions', [
                        'url'          => $initialModalUrl,
                        'label'        => 'URL générée',
                        'canTransform' => false,
                    ]) ?>
                </section>

                <!-- Infos originale / transformée -->
                <section class="px-4 py-3 grid grid-cols-2 gap-2 text-[11px]">
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="mb-1 font-semibold uppercase tracking-wider opacity-40">Originale</p>
                        <div id="home-photo-transform-original-info" class="font-mono">
                            <span class="opacity-30">—</span>
                        </div>
                    </div>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="mb-1 font-semibold uppercase tracking-wider opacity-40">Transformée</p>
                        <div id="home-photo-transform-result-info" class="font-mono">
                            <span class="opacity-30">—</span>
                        </div>
                    </div>
                </section>

                <!-- Réinitialiser (en bas de la toolbar) -->
                <section class="px-4 py-3">
                    <button id="home-photo-transform-reset" type="button" class="btn btn-ghost btn-sm w-full">
                        Réinitialiser
                    </button>
                </section>

            </div><!-- fin toolbar -->

        </form>

    </div>
    <form method="dialog" class="modal-backdrop">
        <button>Fermer</button>
    </form>
</dialog>


