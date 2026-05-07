<?= view('partials/header', ['pageTitle' => 'Media Gone Wild - Accueil']) ?>
    <main class="mx-auto w-full max-w-5xl px-6 py-10 pt-28">
        <header class="mb-10 rounded-2xl border border-slate-800 bg-slate-900/70 p-8">
            <p class="mb-3 inline-flex rounded-full border border-fuchsia-400/30 bg-fuchsia-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-fuchsia-300">
                API médias aléatoires
            </p>
            <h1 class="text-3xl font-bold leading-tight text-white md:text-4xl">Media Gone Wild</h1>
            <p class="mt-4 max-w-3xl text-slate-300">
                Cette API retourne des photos, des vidéos et des logos de façon aléatoire. Vous pouvez l'utiliser telle quelle,
                figer le résultat avec un seed ou transformer les photos à la volée avec des paramètres comme
                <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">width</code>,
                <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">height</code>,
                <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">fit</code>,
                <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">extension</code>,
                <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">quality</code>
                et <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">bgcolor</code>.
            </p>
        </header>

        <section class="mb-8 grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <h2 class="text-lg font-semibold text-white">1. Choisir un endpoint</h2>
                <p class="mt-2 text-sm text-slate-300">Appelez <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/photo</code>, <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/video</code> ou <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/logo</code>.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <h2 class="text-lg font-semibold text-white">2. Cibler un média exact</h2>
                <p class="mt-2 text-sm text-slate-300">Avec <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">?id=abc123def456</code>, vous récupérez toujours exactement le même média.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <h2 class="text-lg font-semibold text-white">3. Utiliser un seed reproductible</h2>
                <p class="mt-2 text-sm text-slate-300">Avec <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">?seed=demo</code>, le résultat reste stable tant que la collection ne change pas.</p>
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
                    <p class="mb-2 text-sm font-medium text-slate-300">Exact, reproductible ou transformé</p>
                    <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-sm text-emerald-300">GET /photo?seed=robert
GET /photo?id=abc123def456
GET /photo?width=800&amp;height=600&amp;fit=cover
GET /photo?width=256&amp;height=256&amp;fit=contain&amp;bgcolor=ffffff00&amp;extension=png
GET /video?seed=michel
GET /logo?seed=studio42</pre>
                </div>
            </div>
        </section>

        <section class="mb-8 rounded-2xl border border-slate-800 bg-slate-900 p-6">
            <h2 class="text-xl font-semibold text-white">Paramètres de transformation photo</h2>
            <p class="mt-3 text-slate-300">
                Les paramètres ci-dessous s'appliquent uniquement à <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/photo</code>.
                Sur <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/video</code> et
                <code class="rounded bg-slate-800 px-1 py-0.5 text-sky-300">/logo</code>, ils sont ignorés.
            </p>

            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-800">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-950 text-slate-200">
                        <tr>
                            <th class="px-3 py-2">Paramètre</th>
                            <th class="px-3 py-2">Rôle</th>
                            <th class="px-3 py-2">Valeurs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        <tr>
                            <td class="px-3 py-2 font-mono text-sky-300">width</td>
                            <td class="px-3 py-2">Largeur cible en pixels</td>
                            <td class="px-3 py-2">Entier ≥ 1</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono text-sky-300">height</td>
                            <td class="px-3 py-2">Hauteur cible en pixels</td>
                            <td class="px-3 py-2">Entier ≥ 1</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono text-sky-300">fit</td>
                            <td class="px-3 py-2">Mode de redimensionnement</td>
                            <td class="px-3 py-2">contain, cover, fill, scale</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono text-sky-300">extension</td>
                            <td class="px-3 py-2">Format de sortie</td>
                            <td class="px-3 py-2">jpg, png, webp, gif</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono text-sky-300">quality</td>
                            <td class="px-3 py-2">Qualité de compression</td>
                            <td class="px-3 py-2">1 à 100 (85 par défaut)</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono text-sky-300">bgcolor</td>
                            <td class="px-3 py-2">Couleur de fond (notamment avec contain)</td>
                            <td class="px-3 py-2">hex 6/8 chars ou transparent</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <article class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-200">Comprendre les modes fit</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-300">
                        <li><strong>contain</strong> : conserve les proportions et ajoute des marges si nécessaire.</li>
                        <li><strong>cover</strong> : remplit toute la zone, avec recadrage centré si nécessaire.</li>
                        <li><strong>fill</strong> : force largeur + hauteur, même si l'image est déformée.</li>
                        <li><strong>scale</strong> : redimensionne proportionnellement, sans forcer le remplissage.</li>
                    </ul>
                    <p class="mt-3 text-xs text-slate-400">
                        Pour recadrer, utilise <code class="rounded bg-slate-800 px-1 py-0.5 text-emerald-300">fit=cover</code>.
                    </p>
                </article>

                <article class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-200">Transparence et bgcolor</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-300">
                        <li><code class="rounded bg-slate-800 px-1 py-0.5 text-emerald-300">transparent</code> = <code class="rounded bg-slate-800 px-1 py-0.5 text-emerald-300">00000000</code>.</li>
                        <li><code class="rounded bg-slate-800 px-1 py-0.5 text-emerald-300">ffffff00</code> = transparent.</li>
                        <li><code class="rounded bg-slate-800 px-1 py-0.5 text-emerald-300">ffffff80</code> = semi-transparent.</li>
                        <li>Pour préserver la transparence, préfère <strong>png</strong> ou <strong>webp</strong>.</li>
                    </ul>
                </article>
            </div>

            <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950 p-4">
                <p class="mb-2 text-sm font-medium text-slate-300">Exemples conseillés</p>
                <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-sm text-emerald-300">GET /photo?id=abc123def456&amp;width=1200
GET /photo?id=abc123def456&amp;width=1200&amp;height=630&amp;fit=cover&amp;extension=webp&amp;quality=90
GET /photo?id=abc123def456&amp;width=256&amp;height=256&amp;fit=contain&amp;bgcolor=ffffff00&amp;extension=png</pre>
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
<?= view('partials/footer') ?>



