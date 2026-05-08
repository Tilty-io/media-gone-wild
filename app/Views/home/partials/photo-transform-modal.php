<?php
/** @var string $defaultDemoPhotoId */

$allowedFitModes = \App\DTO\MediaTransformOptions::ALLOWED_FIT_MODES;
$allowedExtensions = array_values(array_filter(
    \App\DTO\MediaTransformOptions::ALLOWED_EXTENSIONS,
    static fn (string $extension): bool => $extension !== 'jpeg',
));

$initialModalUrl = site_url('photo') . '?' . http_build_query(['id' => $defaultDemoPhotoId]);
?>
<dialog id="home-photo-transform-modal" class="modal">
    <div class="modal-box h-screen w-screen max-w-none overflow-y-auto rounded-none">
        <form method="dialog" class="absolute right-2 top-2">
            <button class="btn btn-circle btn-ghost btn-sm" aria-label="Fermer">x</button>
        </form>

        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Transformation photo</p>
        <h2 class="mt-1 text-xl font-semibold">Ajuster une URL /photo</h2>

        <form id="home-photo-transform-builder" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="form-control xl:col-span-2">
                <span class="label-text text-sm">ID photo exact</span>
                <input id="home-photo-transform-id" name="id" type="text" value="<?= esc($defaultDemoPhotoId) ?>" class="input input-bordered font-mono">
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

            <div class="form-control xl:col-span-2">
                <span class="label-text text-sm">Couleur de fond</span>
                <input id="home-photo-transform-bgcolor" name="bgcolor" type="hidden" value="">

                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#ff00ff" data-opacity="100">Magenta</button>
                    <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#00ffff" data-opacity="100">Cyan</button>
                    <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#39ff14" data-opacity="100">Vert néon</button>
                    <button type="button" class="btn btn-xs" data-home-photo-transform-bgcolor-preset data-color="#ff5e00" data-opacity="100">Orange vif</button>
                    <button type="button" class="btn btn-xs btn-outline" data-home-photo-transform-bgcolor-preset data-color="#ff00ff" data-opacity="50">Magenta 50 %</button>
                    <button type="button" class="btn btn-xs btn-outline" data-home-photo-transform-bgcolor-preset data-color="#00ffff" data-opacity="0">Transparent</button>
                </div>

                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <label class="form-control">
                        <span class="label-text text-sm">Couleur</span>
                        <input id="home-photo-transform-bgcolor-color" type="color" value="#ff00ff" class="input input-bordered h-12 w-full p-1">
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Opacité <span id="home-photo-transform-bgcolor-opacity-value" class="font-mono">100 %</span></span>
                        <input id="home-photo-transform-bgcolor-opacity" type="range" min="0" max="100" step="1" value="100" class="range range-primary range-sm">
                    </label>
                </div>

                <div class="mt-3 rounded-lg border border-base-300 bg-base-200 p-3">
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide opacity-70">bgcolor généré</p>
                    <p id="home-photo-transform-bgcolor-token" class="font-mono text-sm text-primary">non défini</p>
                </div>
            </div>

            <div class="xl:col-span-4" id="home-photo-transform-url-host">
                <?= view('components/api-url-actions', [
                    'url' => $initialModalUrl,
                    'label' => 'URL générée',
                ]) ?>
            </div>

            <div class="xl:col-span-4 rounded-lg border border-base-300 bg-base-200 p-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide opacity-70">Aperçu direct</p>
                <div class="media-preview-checkerboard mt-2 flex max-h-[70vh] items-center justify-center overflow-auto rounded-lg border border-base-300 bg-base-100 p-2">
                    <img
                        id="home-photo-transform-preview"
                        src="<?= esc($initialModalUrl) ?>"
                        alt="Aperçu de l'image générée"
                        loading="lazy"
                        class="h-auto w-auto max-w-none"
                    >
                </div>
                <p id="home-photo-transform-status" class="mt-2 text-xs opacity-70" aria-live="polite"></p>
            </div>

            <div class="xl:col-span-4 sticky bottom-0 z-10 -mx-1 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-base-300 bg-base-100/95 p-3 backdrop-blur">
                <button id="home-photo-transform-reset" type="button" class="btn btn-ghost btn-sm">Réinitialiser</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="this.closest('dialog').close();">Fermer</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>Fermer</button>
    </form>
</dialog>





