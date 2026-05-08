<?= view('partials/header', ['pageTitle' => 'Media Gone Wild - Accueil']) ?>
<?= view('home/partials/bgcolor-styles') ?>
<?php
/**
 * Décrit les démonstrations affichées sur la page d'accueil.
 *
 * @var list<array{title: string, description: string, url: string, previewType: string, previewUrl: string}>
 */
$features = [
    [
        'title' => 'Photo par ID',
        'description' => 'Une base fixe pour comparer chaque transformation sur la même image.',
        'url' => site_url('photo') . '?' . http_build_query(['id' => 'abe7c64822d6']),
        'previewType' => 'image',
        'previewUrl' => site_url('photo') . '?' . http_build_query(['id' => 'abe7c64822d6']),
    ],
    [
        'title' => 'Largeur uniquement',
        'description' => 'Redimensionne simplement l\'image en fixant la largeur.',
        'url' => site_url('photo') . '?' . http_build_query(['id' => 'abe7c64822d6', 'width' => '640']),
        'previewType' => 'image',
        'previewUrl' => site_url('photo') . '?' . http_build_query(['id' => 'abe7c64822d6', 'width' => '640']),
    ],
    [
        'title' => 'Carré en contain',
        'description' => 'Force un cadre carré tout en conservant les proportions.',
        'url' => site_url('photo') . '?' . http_build_query([
            'id' => 'abe7c64822d6',
            'width' => '256',
            'height' => '256',
            'fit' => 'contain',
        ]),
        'previewType' => 'image',
        'previewUrl' => site_url('photo') . '?' . http_build_query([
            'id' => 'abe7c64822d6',
            'width' => '256',
            'height' => '256',
            'fit' => 'contain',
        ]),
    ],
    [
        'title' => 'Recadrage cover',
        'description' => 'Remplit tout le cadre en recadrant l\'image si nécessaire.',
        'url' => site_url('photo') . '?' . http_build_query([
            'id' => 'abe7c64822d6',
            'width' => '320',
            'height' => '180',
            'fit' => 'cover',
        ]),
        'previewType' => 'image',
        'previewUrl' => site_url('photo') . '?' . http_build_query([
            'id' => 'abe7c64822d6',
            'width' => '320',
            'height' => '180',
            'fit' => 'cover',
        ]),
    ],
    [
        'title' => 'Export WebP',
        'description' => 'Change simplement le format de sortie sans ajouter d\'autre transformation.',
        'url' => site_url('photo') . '?' . http_build_query(['id' => 'abe7c64822d6', 'extension' => 'webp']),
        'previewType' => 'image',
        'previewUrl' => site_url('photo') . '?' . http_build_query(['id' => 'abe7c64822d6', 'extension' => 'webp']),
    ],
];

/**
 * Paramètres de base utilisés par le picker interactif de bgcolor.
 *
 * @var array<string, string>
 */
$bgcolorPickerBaseParams = [
    'id' => 'abe7c64822d6',
    'width' => '256',
    'height' => '256',
    'fit' => 'contain',
    'extension' => 'png',
];
?>
    <main class="mx-auto w-full max-w-7xl px-6 py-10 pt-28">
        <?= view('home/partials/intro') ?>
        <?= view('home/partials/demo-section', ['features' => $features]) ?>
        <?= view('home/partials/bgcolor-picker', ['bgcolorPickerBaseParams' => $bgcolorPickerBaseParams]) ?>
        <?= view('home/partials/photo-params') ?>
        <?= view('home/partials/cta-links') ?>
    </main>
    <?= view('home/partials/bgcolor-script', ['bgcolorPickerBaseParams' => $bgcolorPickerBaseParams]) ?>
<?= view('partials/footer') ?>



