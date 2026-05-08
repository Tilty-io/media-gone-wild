<?php
/** @var string $url */
/** @var bool|null $canTransform */
/** @var string|null $label */
/** @var string|null $wrapperClass */

$canTransform = $canTransform ?? true;
$label = $label ?? 'URL API';
$wrapperClass = $wrapperClass ?? '';
?>
<div class="rounded-lg border border-base-300 bg-base-200 p-3 <?= esc($wrapperClass) ?>" data-api-url-card>
    <div class="mb-2 flex items-center justify-between gap-3">
        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-70"><?= esc($label) ?></p>
        <div class="flex items-center gap-1">
            <button
                type="button"
                class="btn btn-ghost btn-xs"
                data-api-copy
                aria-label="Copier l'URL API"
                title="Copier"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path d="M9 7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2V7Z" />
                    <path d="M5 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h1v-2H5V5h8V4a1 1 0 0 0-1-1H5Z" />
                </svg>
            </button>
            <?php if ($canTransform): ?>
                <button
                    type="button"
                    class="btn btn-ghost btn-xs"
                    data-api-transform
                    aria-label="Ouvrir la popin de transformation"
                    title="Ouvrir dans la popin de transformation"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path d="M14.5 2a.75.75 0 0 1 .73.57l1.02 4.09 4.09 1.02a.75.75 0 0 1 0 1.46l-4.09 1.02-1.02 4.09a.75.75 0 0 1-1.46 0l-1.02-4.09-4.09-1.02a.75.75 0 0 1 0-1.46l4.09-1.02 1.02-4.09A.75.75 0 0 1 14.5 2Z" />
                        <path d="M6 13.25a.75.75 0 0 1 .73.57l.45 1.79 1.79.45a.75.75 0 0 1 0 1.46l-1.79.45-.45 1.79a.75.75 0 0 1-1.46 0l-.45-1.79-1.79-.45a.75.75 0 0 1 0-1.46l1.79-.45.45-1.79A.75.75 0 0 1 6 13.25Z" />
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <a
        href="<?= esc($url) ?>"
        target="_blank"
        rel="noopener"
        class="font-mono text-xs text-primary underline break-all"
        data-api-url-link
    ><?= esc($url) ?></a>
</div>

