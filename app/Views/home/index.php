<?= view('partials/header', ['pageTitle' => 'Media Gone Wild - Accueil']) ?>
<?= view('home/partials/bgcolor-styles') ?>
<?php
/**
 * Décrit les démonstrations affichées sur la page d'accueil.
 *
 * @var list<array{title: string, description: string, params: array<string, string>}>
 */
$demoPhotoIds = [
    'abe7c64822d6',
    '3c1f5c726bdf',
];

$defaultDemoPhotoId = '3c1f5c726bdf';

$features = [
    [
        'title' => 'Photo par ID',
        'description' => 'La base pour valider rapidement qu\'un ID précis retourne bien le même média.',
        'params' => [],
    ],
    [
        'title' => 'Largeur uniquement',
        'description' => 'Redimensionne simplement l\'image en fixant la largeur.',
        'params' => ['width' => '640'],
    ],
    [
        'title' => 'Recadrage social cover',
        'description' => 'Prépare un format 1200×630 pour des partages en remplissant tout le cadre.',
        'params' => [
            'width' => '1200',
            'height' => '630',
            'fit' => 'cover',
        ],
    ],
    [
        'title' => 'Export WebP',
        'description' => 'Change simplement le format de sortie sans ajouter d\'autre transformation.',
        'params' => ['extension' => 'webp'],
    ],
];

/**
 * Paramètres de base utilisés par le picker interactif de bgcolor.
 *
 * @var array<string, string>
 */
$bgcolorPickerBaseParams = [
    'width' => '256',
    'height' => '256',
    'fit' => 'contain',
    'extension' => 'png',
];
?>
    <main class="mx-auto w-full max-w-7xl px-6 py-10 pt-28">
        <?= view('home/partials/intro') ?>
        <?= view('home/partials/demo-section', [
            'features' => $features,
            'demoPhotoIds' => $demoPhotoIds,
            'defaultDemoPhotoId' => $defaultDemoPhotoId,
        ]) ?>
        <?= view('home/partials/bgcolor-picker', [
            'bgcolorPickerBaseParams' => $bgcolorPickerBaseParams,
            'defaultDemoPhotoId' => $defaultDemoPhotoId,
        ]) ?>
        <?= view('home/partials/cta-links') ?>
        <?= view('home/partials/photo-params') ?>
    </main>
    <?= view('home/partials/photo-transform-modal', ['defaultDemoPhotoId' => $defaultDemoPhotoId]) ?>
    <div id="global-copy-toast" class="toast toast-top toast-end hidden z-[60]">
        <div class="alert alert-success py-2 px-3 text-sm">
            <span>copié</span>
        </div>
    </div>
    <?= view('home/partials/home-script', [
        'defaultDemoPhotoId' => $defaultDemoPhotoId,
        'bgcolorPickerBaseParams' => $bgcolorPickerBaseParams,
    ]) ?>
<?= view('partials/footer') ?>



