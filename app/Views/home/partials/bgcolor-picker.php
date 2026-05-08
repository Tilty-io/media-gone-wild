<?php
/** @var array<string, string> $bgcolorPickerBaseParams */
/** @var string $defaultDemoPhotoId */
$initialBgcolor = 'ff00ff';
$initialUrl = site_url('photo') . '?' . http_build_query(['id' => $defaultDemoPhotoId] + $bgcolorPickerBaseParams + ['bgcolor' => $initialBgcolor]);
?>
<section
    class="mb-8 rounded-box border border-base-300 bg-base-100 p-6 shadow-sm"
    id="bgcolor-picker-root"
    data-bgcolor-picker
    data-bgcolor-picker-base='<?= esc(json_encode($bgcolorPickerBaseParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold">Tester `bgcolor`</h2>
            <p class="mt-2 max-w-3xl text-sm opacity-80">
                Choisis une couleur, réduis l'opacité, ou passe en transparence totale.
                L'aperçu et l'URL sont mis à jour immédiatement sur la même photo.
            </p>
        </div>

        <div class="rounded-lg border border-base-300 bg-base-200 px-3 py-2 text-xs opacity-80">
            <p><code class="rounded bg-base-300 px-1">ffffff80</code> = semi-transparent</p>
            <p><code class="rounded bg-base-300 px-1">transparent</code> = transparence totale</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,340px)_minmax(0,1fr)]">
        <div class="rounded-box border border-base-300 bg-base-200 p-4">
            <div class="media-preview-checkerboard flex aspect-square items-center justify-center overflow-hidden rounded-lg border border-base-300 bg-base-100 p-2">
                <img
                    id="bgcolor-picker-preview"
                    src="<?= esc($initialUrl) ?>"
                    alt="Aperçu interactif du fond coloré"
                    class="h-full w-full object-contain"
                    loading="lazy"
                >
            </div>
            <p id="bgcolor-picker-status" class="mt-3 text-xs opacity-70">Fond actuel : <span class="font-mono"><?= esc($initialBgcolor) ?></span></p>
            <p id="bgcolor-picker-photo-id" class="mt-1 text-xs opacity-70">Photo testée : <span class="font-mono"><?= esc($defaultDemoPhotoId) ?></span></p>
        </div>

        <div class="space-y-4">
            <div>
                <p class="mb-2 text-sm font-medium">Couleurs rapides</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm" data-bgcolor-preset data-color="#ff00ff" data-opacity="100">Magenta</button>
                    <button type="button" class="btn btn-sm" data-bgcolor-preset data-color="#00ffff" data-opacity="100">Cyan</button>
                    <button type="button" class="btn btn-sm" data-bgcolor-preset data-color="#39ff14" data-opacity="100">Vert néon</button>
                    <button type="button" class="btn btn-sm" data-bgcolor-preset data-color="#ff5e00" data-opacity="100">Orange vif</button>
                    <button type="button" class="btn btn-sm btn-outline" data-bgcolor-preset data-color="#ff00ff" data-opacity="50">Magenta 50 %</button>
                    <button type="button" class="btn btn-sm btn-outline" data-bgcolor-preset data-color="#00ffff" data-opacity="0">Transparent</button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="form-control">
                    <span class="label-text text-sm">Couleur</span>
                    <input id="bgcolor-picker-color" type="color" value="#ff00ff" class="input input-bordered h-12 w-full p-1">
                </label>

                <label class="form-control">
                    <span class="label-text text-sm">Opacité <span id="bgcolor-picker-opacity-value" class="font-mono">100 %</span></span>
                    <input id="bgcolor-picker-opacity" type="range" min="0" max="100" step="1" value="100" class="range range-primary range-sm">
                </label>
            </div>

            <div class="rounded-lg border border-base-300 bg-base-200 p-3">
                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide opacity-70">bgcolor généré</p>
                <p id="bgcolor-picker-token" class="font-mono text-sm text-primary"><?= esc($initialBgcolor) ?></p>
            </div>

            <div id="bgcolor-picker-url-host">
                <?= view('components/api-url-actions', ['url' => $initialUrl]) ?>
            </div>
        </div>
    </div>
</section>

