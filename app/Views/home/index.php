<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Gone Wild - Accueil</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="mx-auto w-full max-w-5xl px-6 py-10">
        <header class="mb-10 rounded-2xl border border-slate-800 bg-slate-900/70 p-8">
            <p class="mb-3 inline-flex rounded-full border border-fuchsia-400/30 bg-fuchsia-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-fuchsia-300">
                API médias aléatoires
            </p>
            <h1 class="text-3xl font-bold leading-tight text-white md:text-4xl">Media Gone Wild</h1>
            <p class="mt-4 max-w-3xl text-slate-300">
                Cette API retourne des photos, des vidéos et des logos de façon aléatoire. Vous pouvez l'utiliser telle quelle
                ou figer le résultat avec un seed pour obtenir un rendu reproductible.
            </p>
        </header>

        <section class="mb-8 grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <h2 class="text-lg font-semibold text-white">1. Choisir un endpoint</h2>
                <p class="mt-2 text-sm text-slate-300">Appelez <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/photo</code>, <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/video</code> ou <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/logo</code>.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <h2 class="text-lg font-semibold text-white">2. Ajouter un seed (optionnel)</h2>
                <p class="mt-2 text-sm text-slate-300">Avec <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">?seed=demo</code>, le résultat reste stable pour ce seed.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <h2 class="text-lg font-semibold text-white">3. Éviter le cache navigateur</h2>
                <p class="mt-2 text-sm text-slate-300">Ajoutez <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">?r=12345</code> pour forcer un nouveau chargement visuel.</p>
            </article>
        </section>

        <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="text-xl font-semibold text-white">Exemples rapides</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="mb-2 text-sm font-medium text-slate-300">Aléatoire simple</p>
                    <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-sm text-emerald-300">GET /photo
GET /video
GET /logo</pre>
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-slate-300">Aléatoire reproductible</p>
                    <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-sm text-emerald-300">GET /photo?seed=robert
GET /video?seed=michel
GET /logo?seed=studio42</pre>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="text-xl font-semibold text-white">Et ensuite ?</h2>
            <p class="mt-3 text-slate-300">
                La prochaine étape sera un petit catalogue pour parcourir les médias directement depuis une interface dédiée.
            </p>
            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                <a href="<?= esc(site_url('catalogue')) ?>" class="rounded-lg border border-fuchsia-500/40 bg-fuchsia-500/10 px-4 py-2 text-fuchsia-200 hover:border-fuchsia-400 hover:text-fuchsia-100">Ouvrir le catalogue</a>
                <a href="<?= esc(site_url('photo')) ?>" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-slate-200 hover:border-sky-400 hover:text-sky-300">Tester /photo</a>
                <a href="<?= esc(site_url('video')) ?>" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-slate-200 hover:border-sky-400 hover:text-sky-300">Tester /video</a>
                <a href="<?= esc(site_url('logo')) ?>" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-slate-200 hover:border-sky-400 hover:text-sky-300">Tester /logo</a>
            </div>
        </section>
    </main>
</body>
</html>



