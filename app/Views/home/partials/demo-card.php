<?php
/** @var array{title: string, description: string, params: array<string, string>} $feature */
/** @var string $defaultDemoPhotoId */

$initialUrl = site_url('photo') . '?' . http_build_query(['id' => $defaultDemoPhotoId] + $feature['params']);
?>
<article
    class="overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-sm"
    data-photo-template='<?= esc(json_encode($feature['params'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'
>
    <div class="flex aspect-video items-center justify-center bg-base-200 p-2">
        <img
            src="<?= esc($initialUrl) ?>"
            alt="Aperçu : <?= esc($feature['title']) ?>"
            class="h-full w-full object-contain"
            loading="lazy"
            data-photo-preview
        >
    </div>

    <div class="space-y-3 p-4">
        <h3 class="text-base font-semibold"><?= esc($feature['title']) ?></h3>
        <p class="text-sm opacity-80"><?= esc($feature['description']) ?></p>

        <div data-photo-url-host>
            <?= view('components/api-url-actions', ['url' => $initialUrl]) ?>
        </div>
    </div>
</article>

