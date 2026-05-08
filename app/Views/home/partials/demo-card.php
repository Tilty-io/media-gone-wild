<?php
/** @var array{title: string, description: string, url: string, previewType: string, previewUrl: string} $feature */
?>
<article class="overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-sm">
    <div class="flex aspect-video items-center justify-center bg-base-200 p-2">
        <?php if ($feature['previewType'] === 'video'): ?>
            <video
                src="<?= esc($feature['previewUrl']) ?>"
                class="h-full w-full object-contain"
                controls
                muted
                preload="metadata"
                playsinline
            ></video>
        <?php else: ?>
            <img
                src="<?= esc($feature['previewUrl']) ?>"
                alt="Aperçu : <?= esc($feature['title']) ?>"
                class="h-full w-full object-contain"
                loading="lazy"
            >
        <?php endif; ?>
    </div>

    <div class="space-y-3 p-4">
        <h3 class="text-base font-semibold"><?= esc($feature['title']) ?></h3>
        <p class="text-sm opacity-80"><?= esc($feature['description']) ?></p>

        <div class="rounded-lg border border-base-300 bg-base-200 p-3">
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide opacity-70">URL</p>
            <a
                href="<?= esc($feature['url']) ?>"
                target="_blank"
                rel="noopener"
                class="font-mono text-xs text-primary underline break-all"
            ><?= esc($feature['url']) ?></a>
        </div>
    </div>
</article>

