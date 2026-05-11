<?= view('partials/header', ['pageTitle' => 'Documentation API - Media Gone Wild']) ?>
<?php
/**
 * @var list<string> $allowedFitModes
 * @var list<string> $allowedExtensions
 * @var int          $defaultQuality
 */

$baseUrl = rtrim(site_url(), '/');

/**
 * Construit une URL d'exemple absolue.
 *
 * @param string               $path   Chemin relatif (ex. 'photo', 'photo.webp').
 * @param array<string,scalar> $params Paramètres de requête optionnels.
 */
$ex = static function (string $path, array $params = []) use ($baseUrl): string {
    return $baseUrl . '/' . $path . ($params ? '?' . http_build_query($params) : '');
};

$fitModeDescriptions = [
    'contain' => 'Conserve le ratio ; complète le canvas avec bgcolor (letterbox).',
    'cover'   => 'Remplit tout le canvas en recadrant le surplus.',
    'fill'    => 'Étire l\'image sans conserver le ratio (distorsion possible).',
    'scale'   => 'Redimensionne proportionnellement sans remplir nécessairement le canvas.',
];
?>
<main class="mx-auto w-full max-w-5xl px-6 py-10 pt-28 space-y-12">

    <!-- En-tête -->
    <header class="rounded-box border border-base-300 bg-base-100 px-6 py-5 shadow-sm">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-primary">Référence technique</p>
        <h1 class="text-3xl font-bold">Documentation API</h1>
        <p class="mt-2 text-sm opacity-70">
            Tous les endpoints et paramètres disponibles pour récupérer et transformer les médias.
        </p>
        <p class="mt-2 font-mono text-sm text-primary break-all"><?= esc($baseUrl) ?>/</p>
    </header>

    <!-- Endpoints -->
    <section>
        <h2 class="mb-4 text-xl font-semibold">Endpoints</h2>
        <div class="overflow-x-auto rounded-box border border-base-300 bg-base-100 shadow-sm">
            <table class="table table-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider opacity-60">
                        <th>Méthode</th>
                        <th>Chemin</th>
                        <th>Description</th>
                        <th>Transformations</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge badge-success badge-sm font-mono">GET</span></td>
                        <td class="font-mono text-xs text-primary">/photo</td>
                        <td class="text-sm">Photo aléatoire (JPEG par défaut).</td>
                        <td><span class="badge badge-outline badge-xs">oui</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success badge-sm font-mono">GET</span></td>
                        <td class="font-mono text-xs text-primary">/photo.{ext}</td>
                        <td class="text-sm">Photo aléatoire dans le format spécifié par l'extension de l'URL. L'extension prend la priorité sur le paramètre <code class="rounded bg-base-200 px-1">?extension=</code>.</td>
                        <td><span class="badge badge-outline badge-xs">oui</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success badge-sm font-mono">GET</span></td>
                        <td class="font-mono text-xs text-primary">/video</td>
                        <td class="text-sm">Vidéo aléatoire (MP4).</td>
                        <td><span class="badge badge-ghost badge-xs opacity-40">non</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success badge-sm font-mono">GET</span></td>
                        <td class="font-mono text-xs text-primary">/logo</td>
                        <td class="text-sm">Logo aléatoire (SVG).</td>
                        <td><span class="badge badge-ghost badge-xs opacity-40">non</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success badge-sm font-mono">GET</span></td>
                        <td class="font-mono text-xs text-primary">/catalogue</td>
                        <td class="text-sm">Catalogue paginé des médias disponibles.</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Réponse -->
    <section>
        <h2 class="mb-4 text-xl font-semibold">Réponse</h2>
        <div class="rounded-box border border-base-300 bg-base-100 p-5 shadow-sm text-sm space-y-2">
            <p>En cas de succès, les endpoints médias retournent directement le <strong>binaire du fichier</strong> avec les en-têtes HTTP appropriés :</p>
            <ul class="list-disc list-inside space-y-1 opacity-80">
                <li><code class="rounded bg-base-200 px-1">Content-Type</code> — type MIME du média servi (ex. <code class="rounded bg-base-200 px-1">image/jpeg</code>, <code class="rounded bg-base-200 px-1">image/webp</code>).</li>
                <li><code class="rounded bg-base-200 px-1">Content-Length</code> — taille en octets.</li>
                <li><code class="rounded bg-base-200 px-1">Cache-Control: no-store</code> — pas de mise en cache navigateur.</li>
            </ul>
            <p class="opacity-70">En cas d'erreur (média introuvable, transformation impossible), une réponse JSON est renvoyée avec le code HTTP correspondant.</p>
        </div>
    </section>

    <!-- Paramètres -->
    <section>
        <h2 class="mb-4 text-xl font-semibold">Paramètres</h2>
        <p class="mb-5 text-sm opacity-70">Tous les paramètres sont passés en <strong>query string</strong> (<code class="rounded bg-base-200 px-1">?param=valeur</code>), sauf <code class="rounded bg-base-200 px-1">extension</code> qui peut aussi être intégrée dans le chemin.</p>

        <div class="space-y-4">

            <!-- id -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">id</code>
                    <span class="badge badge-outline badge-sm">string</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel</span>
                    <span class="badge badge-info badge-sm">photo · vidéo · logo</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Identifiant stable du média. Permet de récupérer <strong>toujours le même fichier</strong> quelle que soit la date de la requête. Si absent, un média est choisi aléatoirement.</p>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['id' => '3c1f5c726bdf'])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['id' => '3c1f5c726bdf'])) ?></a>
                    </div>
                </div>
            </div>

            <!-- seed -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">seed</code>
                    <span class="badge badge-outline badge-sm">string</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel</span>
                    <span class="badge badge-info badge-sm">photo · vidéo · logo</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Valeur arbitraire pour une sélection <strong>déterministe sans connaître l'ID</strong>. Le même seed retourne toujours le même média. Ignoré si <code class="rounded bg-base-200 px-1">id</code> est fourni.</p>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['seed' => 'robert'])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['seed' => 'robert'])) ?></a>
                    </div>
                </div>
            </div>

            <!-- collection -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">collection</code>
                    <span class="badge badge-outline badge-sm">string</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel</span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Restreint la sélection aléatoire à un <strong>sous-dossier</strong> du répertoire <code class="rounded bg-base-200 px-1">media/photo/</code>. Ignoré si <code class="rounded bg-base-200 px-1">id</code> est fourni.</p>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['collection' => 'products'])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['collection' => 'products'])) ?></a>
                    </div>
                </div>
            </div>

            <!-- width -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">width</code>
                    <span class="badge badge-outline badge-sm">entier ≥ 1</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel</span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Largeur cible en pixels. Si seul <code class="rounded bg-base-200 px-1">width</code> est fourni (sans <code class="rounded bg-base-200 px-1">height</code>), l'image est redimensionnée <strong>proportionnellement</strong>.</p>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['width' => 800])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['width' => 800])) ?></a>
                    </div>
                </div>
            </div>

            <!-- height -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">height</code>
                    <span class="badge badge-outline badge-sm">entier ≥ 1</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel</span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Hauteur cible en pixels. Si seul <code class="rounded bg-base-200 px-1">height</code> est fourni (sans <code class="rounded bg-base-200 px-1">width</code>), l'image est redimensionnée <strong>proportionnellement</strong>.</p>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['height' => 400])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['height' => 400])) ?></a>
                    </div>
                </div>
            </div>

            <!-- fit -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">fit</code>
                    <span class="badge badge-outline badge-sm">enum</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel — défaut : <code>contain</code></span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Mode de redimensionnement appliqué quand <code class="rounded bg-base-200 px-1">width</code> <strong>et</strong> <code class="rounded bg-base-200 px-1">height</code> sont fournis simultanément.</p>
                    <div class="overflow-x-auto rounded border border-base-300">
                        <table class="table table-xs">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-wider opacity-50">
                                    <th>Valeur</th>
                                    <th>Comportement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allowedFitModes as $mode): ?>
                                    <tr>
                                        <td><code class="font-mono text-primary"><?= esc($mode) ?></code><?php if ($mode === 'contain'): ?> <span class="badge badge-ghost badge-xs opacity-50">défaut</span><?php endif; ?></td>
                                        <td class="opacity-80"><?= esc($fitModeDescriptions[$mode] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['width' => 1200, 'height' => 630, 'fit' => 'cover'])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['width' => 1200, 'height' => 630, 'fit' => 'cover'])) ?></a>
                    </div>
                </div>
            </div>

            <!-- extension -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">extension</code>
                    <span class="badge badge-outline badge-sm">enum</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel — défaut : format source</span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Format de sortie souhaité. Peut être intégré <strong>dans l'URL</strong> (<code class="rounded bg-base-200 px-1">/photo.webp</code>) ou passé en query string (<code class="rounded bg-base-200 px-1">?extension=webp</code>). L'extension d'URL est prioritaire.</p>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach ($allowedExtensions as $ext): ?>
                            <code class="rounded bg-base-200 px-2 py-0.5 text-xs font-mono text-primary"><?= esc($ext) ?></code>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Via chemin</p>
                            <a href="<?= esc($ex('photo.webp')) ?>" target="_blank" rel="noopener"
                               class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo.webp')) ?></a>
                        </div>
                        <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Via query string</p>
                            <a href="<?= esc($ex('photo', ['extension' => 'webp'])) ?>" target="_blank" rel="noopener"
                               class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['extension' => 'webp'])) ?></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- quality -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">quality</code>
                    <span class="badge badge-outline badge-sm">entier 1–100</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel — défaut : <?= esc((string) $defaultQuality) ?></span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Qualité de compression de <strong>1</strong> (plus compressé) à <strong>100</strong> (qualité maximale). Applicable aux formats JPEG et WebP ; ignoré pour PNG et GIF. Valeur par défaut : <code class="rounded bg-base-200 px-1"><?= esc((string) $defaultQuality) ?></code>.</p>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple</p>
                        <a href="<?= esc($ex('photo', ['quality' => 40, 'extension' => 'jpg'])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['quality' => 40, 'extension' => 'jpg'])) ?></a>
                    </div>
                </div>
            </div>

            <!-- bgcolor -->
            <div class="rounded-box border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-start gap-3 border-b border-base-300 bg-base-200/50 px-5 py-3">
                    <code class="font-mono text-base font-bold text-primary">bgcolor</code>
                    <span class="badge badge-outline badge-sm">hex 6 ou 8 chiffres · "transparent"</span>
                    <span class="badge badge-ghost badge-sm opacity-60">optionnel</span>
                    <span class="badge badge-secondary badge-sm">photo uniquement</span>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <p>Couleur de fond appliquée sous l'image (utile avec le mode <code class="rounded bg-base-200 px-1">contain</code> ou pour les PNG transparents).</p>
                    <div class="overflow-x-auto rounded border border-base-300">
                        <table class="table table-xs">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-wider opacity-50">
                                    <th>Format</th>
                                    <th>Exemple</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-outline badge-xs">hex 6</span></td>
                                    <td><code class="font-mono text-primary">ff0000</code></td>
                                    <td class="opacity-80">Rouge opaque</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-outline badge-xs">hex 8</span></td>
                                    <td><code class="font-mono text-primary">ff000080</code></td>
                                    <td class="opacity-80">Rouge à 50 % d'opacité (les 2 derniers chiffres = canal alpha en hex 00–ff)</td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-outline badge-xs">transparent</span></td>
                                    <td><code class="font-mono text-primary">transparent</code></td>
                                    <td class="opacity-80">Fond entièrement transparent (efficace uniquement en PNG)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded border border-base-300 bg-base-200 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide opacity-50 mb-1">Exemple — logo blanc sur fond noir 256×256</p>
                        <a href="<?= esc($ex('photo', ['width' => 256, 'height' => 256, 'fit' => 'contain', 'bgcolor' => '000000', 'extension' => 'png'])) ?>" target="_blank" rel="noopener"
                           class="font-mono text-xs text-primary underline break-all"><?= esc($ex('photo', ['width' => 256, 'height' => 256, 'fit' => 'contain', 'bgcolor' => '000000', 'extension' => 'png'])) ?></a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Exemples combinés -->
    <section>
        <h2 class="mb-4 text-xl font-semibold">Exemples combinés</h2>
        <div class="overflow-x-auto rounded-box border border-base-300 bg-base-100 shadow-sm">
            <table class="table table-sm">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider opacity-60">
                        <th>Cas d'usage</th>
                        <th>URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $combinedExamples = [
                        ['Photo par ID', $ex('photo', ['id' => '3c1f5c726bdf'])],
                        ['Photo aléatoire WebP 800 px de large', $ex('photo.webp', ['width' => 800])],
                        ['Vignette carrée 256×256 avec fond blanc', $ex('photo', ['width' => 256, 'height' => 256, 'fit' => 'contain', 'bgcolor' => 'ffffff', 'extension' => 'png'])],
                        ['Cover réseaux sociaux 1200×630', $ex('photo', ['width' => 1200, 'height' => 630, 'fit' => 'cover', 'extension' => 'webp', 'quality' => 90])],
                        ['Photo depuis une collection', $ex('photo', ['collection' => 'products', 'width' => 600])],
                        ['Photo déterministe via seed', $ex('photo', ['seed' => 'mon-projet', 'width' => 400])],
                        ['Vidéo aléatoire', $ex('video')],
                        ['Logo aléatoire', $ex('logo')],
                    ];
                    foreach ($combinedExamples as [$label, $url]):
                    ?>
                        <tr>
                            <td class="text-sm"><?= esc($label) ?></td>
                            <td>
                                <a href="<?= esc($url) ?>" target="_blank" rel="noopener"
                                   class="font-mono text-xs text-primary underline break-all"><?= esc($url) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
<?= view('partials/footer') ?>

