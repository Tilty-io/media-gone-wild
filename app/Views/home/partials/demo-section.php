<?php
/** @var list<array{title: string, description: string, params: array<string, string>}> $features */
/** @var list<string> $demoPhotoIds */
/** @var string $defaultDemoPhotoId */

$fitDemoInitialParams = [
    'width' => '300',
    'height' => '400',
    'fit' => 'contain',
    'extension' => 'png',
    'bgcolor' => 'ffffff',
];

$fitDemoInitialUrl = site_url('photo') . '?' . http_build_query(['id' => $defaultDemoPhotoId] + $fitDemoInitialParams);

$photoPickerItems = array_map(
    static fn (string $photoId): array => [
        'id' => $photoId,
        'previewUrl' => site_url('photo') . '?' . http_build_query(['id' => $photoId]),
    ],
    $demoPhotoIds,
);
?>
<section class="mb-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold">Démonstrations</h2>
            <p class="mt-1 text-sm opacity-70">Même photo, un paramètre à la fois, pour voir clairement ce que chaque option change.</p>
        </div>

        <div class="form-control">
            <span class="label-text text-sm">Photo testée dans tous les exemples</span>

            <input id="home-photo-id-select" type="hidden" value="<?= esc($defaultDemoPhotoId) ?>">

            <div class="dropdown dropdown-end">
                <button tabindex="0" type="button" class="btn btn-sm btn-outline mt-1 min-w-72 justify-between">
                    <span class="flex items-center gap-2">
                        <span class="avatar">
                            <span class="h-7 w-7 rounded bg-base-300">
                                <img id="home-photo-id-preview" src="<?= esc(site_url('photo') . '?' . http_build_query(['id' => $defaultDemoPhotoId])) ?>" alt="Aperçu de l'ID sélectionné" class="h-full w-full object-cover">
                            </span>
                        </span>
                        <span id="home-photo-id-display" class="font-mono text-xs"><?= esc($defaultDemoPhotoId) ?></span>
                    </span>
                    <span aria-hidden="true">▾</span>
                </button>

                <ul tabindex="0" class="dropdown-content menu z-[30] mt-1 w-72 rounded-box border border-base-300 bg-base-100 p-2 shadow">
                    <?php foreach ($photoPickerItems as $photoPickerItem): ?>
                        <li>
                            <button
                                type="button"
                                class="gap-3"
                                data-photo-id-option
                                data-photo-id="<?= esc($photoPickerItem['id']) ?>"
                                data-photo-preview-url="<?= esc($photoPickerItem['previewUrl']) ?>"
                            >
                                <span class="avatar">
                                    <span class="h-8 w-8 rounded bg-base-300">
                                        <img src="<?= esc($photoPickerItem['previewUrl']) ?>" alt="Aperçu de <?= esc($photoPickerItem['id']) ?>" class="h-full w-full object-cover" loading="lazy">
                                    </span>
                                </span>
                                <span class="font-mono text-xs"><?= esc($photoPickerItem['id']) ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <article
        class="mt-4 overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-sm"
        data-photo-template='<?= esc(json_encode($fitDemoInitialParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'
        data-fit-demo-card
    >
        <div class="grid gap-5 p-4 lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)]">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-primary">Carte interactive</p>
                <h3 class="mt-1 text-lg font-semibold">Comparer les modes <code class="rounded bg-base-200 px-1 py-0.5">fit</code></h3>
                <p class="mt-2 text-sm opacity-80">Choisis un mode, une proportion et un fond pour voir immédiatement l'impact sur le rendu.</p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="form-control">
                        <span class="label-text text-sm">Mode fit</span>
                        <select id="fit-demo-select" class="select select-bordered" data-fit-demo-select>
                            <option value="contain" selected>contain</option>
                            <option value="cover">cover</option>
                            <option value="fill">fill</option>
                            <option value="scale">scale</option>
                        </select>
                    </label>

                    <label class="form-control">
                        <span class="label-text text-sm">Proportion (taille)</span>
                        <select id="fit-demo-size-select" class="select select-bordered" data-fit-demo-size-select>
                            <option value="4:3">4/3 (400x300 px)</option>
                            <option value="3:4" selected>3/4 (300x400 px)</option>
                            <option value="16:9">16/9 (400x225 px)</option>
                            <option value="1:1">1/1 (320x320 px)</option>
                        </select>
                    </label>
                </div>

                <label class="form-control mt-3 max-w-xs">
                    <span class="label-text text-sm">Fond (bgcolor)</span>
                    <select id="fit-demo-bg-select" class="select select-bordered" data-fit-demo-bg-select>
                        <option value="000000">Noir</option>
                        <option value="ffffff" selected>Blanc</option>
                        <option value="ffff0080">Jaune semi-transparent</option>
                    </select>
                </label>

                <div class="mt-3 rounded-lg border border-base-300 bg-base-200 p-3 text-sm opacity-85">
                    <p class="text-[11px] font-semibold uppercase tracking-wide opacity-70">Comportement du mode sélectionné</p>
                    <p id="fit-demo-description" class="mt-1" data-fit-demo-description>
                        <code class="rounded bg-base-300 px-1 py-0.5">contain</code> conserve les proportions et affiche toute l'image ; des marges peuvent apparaître dans le canvas.
                    </p>
                </div>

                <div class="mt-4" data-photo-url-host>
                    <?= view('components/api-url-actions', ['url' => $fitDemoInitialUrl]) ?>
                </div>
            </div>

            <div class="rounded-box border border-base-300 bg-base-200 p-4">
                <div class="media-preview-checkerboard flex aspect-square items-center justify-center overflow-auto rounded-lg border border-base-300 bg-base-100 p-2">
                    <img
                        src="<?= esc($fitDemoInitialUrl) ?>"
                        alt="Aperçu interactif des modes fit"
                        class="h-auto w-auto max-w-none ring-1 ring-base-content/20"
                        loading="lazy"
                        data-photo-preview
                    >
                </div>
                <p class="mt-2 text-center text-xs opacity-70">taille réelle</p>
            </div>
        </div>
    </article>

    <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($features as $feature): ?>
            <?= view('home/partials/demo-card', ['feature' => $feature, 'defaultDemoPhotoId' => $defaultDemoPhotoId]) ?>
        <?php endforeach; ?>
    </div>
</section>

