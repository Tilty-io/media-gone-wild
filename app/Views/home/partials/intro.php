<header class="mb-8 rounded-box border border-base-300 bg-base-100 px-6 py-6 shadow-sm">
    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-primary">Faker de médias</p>
    <h1 class="text-3xl font-bold">Media Gone Wild</h1>
    <p class="mt-3 max-w-4xl text-sm opacity-80">
        Ce projet sert des médias prêts à l'emploi via API pour alimenter des maquettes, des tests et des démos.
        Tu récupères rapidement une photo, une vidéo ou un logo avec une URL simple.
    </p>

    <section class="mt-5 grid gap-4 md:grid-cols-3">
        <article class="rounded-lg border border-base-300 bg-base-200 p-4">
            <h2 class="text-base font-semibold">Collection photos</h2>
            <p class="mt-2 text-sm opacity-80">Génère des visuels raster, puis applique des transformations avec des paramètres comme <code class="rounded bg-base-300 px-1 py-0.5">fit</code> et <code class="rounded bg-base-300 px-1 py-0.5">bgcolor</code>.</p>
            <div class="media-preview-checkerboard mt-3 flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-base-300 bg-base-100 p-2">
                <img src="<?= esc(site_url('photo')) ?>" alt="Résultat de l'URL /photo" class="h-full w-full object-contain" loading="lazy">
            </div>
            <div class="mt-3">
                <?= view('components/api-url-actions', ['url' => site_url('photo')]) ?>
            </div>
        </article>

        <article class="rounded-lg border border-base-300 bg-base-200 p-4">
            <h2 class="text-base font-semibold">Collection vidéos</h2>
            <p class="mt-2 text-sm opacity-80">Expose une vidéo aléatoire ou ciblée par <code class="rounded bg-base-300 px-1 py-0.5">id</code>, idéale pour des fonds et tests de lecteurs.</p>
            <div class="media-preview-checkerboard mt-3 flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-base-300 bg-base-100 p-2">
                <video src="<?= esc(site_url('video')) ?>" class="h-full w-full object-contain" controls muted preload="metadata" playsinline></video>
            </div>
            <div class="mt-3">
                <?= view('components/api-url-actions', ['url' => site_url('video')]) ?>
            </div>
        </article>

        <article class="rounded-lg border border-base-300 bg-base-200 p-4">
            <h2 class="text-base font-semibold">Collection logos</h2>
            <p class="mt-2 text-sm opacity-80">Retourne des logos orientés marque pour prototyper des headers, footers et pages d'authentification.</p>
            <div class="media-preview-checkerboard mt-3 flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-base-300 bg-base-100 p-2">
                <img src="<?= esc(site_url('logo')) ?>" alt="Résultat de l'URL /logo" class="h-full w-full object-contain" loading="lazy">
            </div>
            <div class="mt-3">
                <?= view('components/api-url-actions', ['url' => site_url('logo')]) ?>
            </div>
        </article>
    </section>
</header>

