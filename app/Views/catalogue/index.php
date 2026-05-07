<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue médias - Media Gone Wild</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <?php
    /** @var list<array{type: string, fileName: string, mimeType: string, fileSize: int, url: string}> $items */
    /** @var string $selectedType */
    /** @var int $limit */
    /** @var int $currentPage */
    /** @var int $totalPages */
    /** @var int $totalItems */

    $buildCatalogueUrl = static function (int $page) use ($selectedType, $limit): string {
        return site_url('catalogue') . '?' . http_build_query([
            'type' => $selectedType,
            'limit' => $limit,
            'page' => $page,
        ]);
    };
    ?>
    <main class="mx-auto w-full max-w-7xl px-6 py-10">
        <header class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-fuchsia-300">Parcourir les médias</p>
                    <h1 class="text-3xl font-bold text-white">Catalogue</h1>
                    <p class="mt-2 text-sm text-slate-300">Filtre par type et explore les fichiers disponibles.</p>
                </div>
                <a href="<?= esc(site_url()) ?>" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm text-slate-200 hover:border-sky-400 hover:text-sky-300">Retour à l'accueil</a>
            </div>
        </header>

        <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900 p-4">
            <form method="get" action="<?= esc(site_url('catalogue')) ?>" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="page" value="1">
                <label class="text-sm text-slate-300">
                    Type
                    <select name="type" class="mt-1 block rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100">
                        <option value="all" <?= $selectedType === 'all' ? 'selected' : '' ?>>Tout</option>
                        <option value="photo" <?= $selectedType === 'photo' ? 'selected' : '' ?>>Photo</option>
                        <option value="video" <?= $selectedType === 'video' ? 'selected' : '' ?>>Vidéo</option>
                        <option value="logo" <?= $selectedType === 'logo' ? 'selected' : '' ?>>Logo</option>
                    </select>
                </label>

                <label class="text-sm text-slate-300">
                    Limite
                    <input type="number" name="limit" min="1" max="500" step="1" value="<?= esc((string) $limit) ?>" class="mt-1 block w-28 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100">
                </label>

                <button type="submit" class="rounded-lg border border-sky-500/50 bg-sky-500/20 px-4 py-2 text-sm font-semibold text-sky-200 hover:border-sky-400 hover:text-sky-100">
                    Appliquer
                </button>

                <p class="text-sm text-slate-400">
                    <?= esc((string) $totalItems) ?> média(s) au total
                </p>
            </form>
        </section>

        <?php if ($items === []): ?>
            <section class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-6 text-amber-100">
                Aucun média disponible pour ce filtre.
            </section>
        <?php else: ?>
            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($items as $item): ?>
                    <article class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                        <div class="aspect-video bg-slate-950">
                            <?php if (str_starts_with($item['mimeType'], 'image/')): ?>
                                <img src="<?= esc($item['url']) ?>" alt="<?= esc($item['fileName']) ?>" class="h-full w-full object-contain">
                            <?php elseif (str_starts_with($item['mimeType'], 'video/')): ?>
                                <video src="<?= esc($item['url']) ?>" class="h-full w-full" controls preload="metadata"></video>
                            <?php else: ?>
                                <div class="flex h-full items-center justify-center text-sm text-slate-400">Prévisualisation indisponible</div>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-full border border-slate-700 bg-slate-800 px-2 py-1 text-xs uppercase tracking-wide text-slate-300">
                                    <?= esc($item['type']) ?>
                                </span>
                                <span class="text-xs text-slate-400"><?= esc(number_format((int) $item['fileSize'], 0, ',', ' ')) ?> octets</span>
                            </div>

                            <p class="truncate text-sm font-medium text-slate-100" title="<?= esc($item['fileName']) ?>">
                                <?= esc($item['fileName']) ?>
                            </p>

                            <div class="flex gap-2">
                                <a href="<?= esc($item['url']) ?>" target="_blank" rel="noopener" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 hover:border-sky-400 hover:text-sky-300">Ouvrir</a>
                                <a href="<?= esc(site_url($item['type'])) ?>" target="_blank" rel="noopener" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs text-slate-200 hover:border-fuchsia-400 hover:text-fuchsia-300">Endpoint aléatoire</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-8 flex flex-wrap items-center gap-2" aria-label="Pagination du catalogue">
                    <?php $previousPage = max(1, $currentPage - 1); ?>
                    <?php $nextPage = min($totalPages, $currentPage + 1); ?>

                    <a
                        href="<?= esc($buildCatalogueUrl($previousPage)) ?>"
                        class="rounded-lg border px-3 py-2 text-sm <?= $currentPage === 1 ? 'pointer-events-none border-slate-800 bg-slate-900 text-slate-600' : 'border-slate-700 bg-slate-800 text-slate-200 hover:border-sky-400 hover:text-sky-300' ?>"
                    >Précédent</a>

                    <?php
                    $windowStart = max(1, $currentPage - 2);
                    $windowEnd = min($totalPages, $currentPage + 2);
                    for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++):
                    ?>
                        <a
                            href="<?= esc($buildCatalogueUrl($pageNumber)) ?>"
                            class="rounded-lg border px-3 py-2 text-sm <?= $pageNumber === $currentPage ? 'border-sky-400 bg-sky-500/20 text-sky-200' : 'border-slate-700 bg-slate-800 text-slate-200 hover:border-sky-400 hover:text-sky-300' ?>"
                        ><?= esc((string) $pageNumber) ?></a>
                    <?php endfor; ?>

                    <a
                        href="<?= esc($buildCatalogueUrl($nextPage)) ?>"
                        class="rounded-lg border px-3 py-2 text-sm <?= $currentPage === $totalPages ? 'pointer-events-none border-slate-800 bg-slate-900 text-slate-600' : 'border-slate-700 bg-slate-800 text-slate-200 hover:border-sky-400 hover:text-sky-300' ?>"
                    >Suivant</a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>






