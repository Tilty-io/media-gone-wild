<?php
/** @var string|null $pageTitle */
$pageTitle = $pageTitle ?? 'Media Gone Wild';
$logoUrl = site_url('logo') . '?' . http_build_query(['r' => (string) microtime(true)]);
?>
<!doctype html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <script>
        (function () {
            var themeKey = 'mgw-theme';
            var storedTheme = localStorage.getItem(themeKey);
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var theme = (storedTheme === 'dark' || storedTheme === 'light')
                ? storedTheme
                : (prefersDark ? 'dark' : 'light');

            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem(themeKey, theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="min-h-screen bg-base-200 text-base-content">
    <header class="navbar fixed top-0 left-0 right-0 z-50 border-b border-base-300 bg-base-100/95 px-4 shadow-sm backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between">
            <a href="<?= esc(site_url()) ?>" class="btn btn-ghost gap-3 px-2 text-base">
                <img src="<?= esc($logoUrl) ?>" alt="Logo aléatoire" class="h-8 w-8 rounded object-contain">
                <span>Media Gone Wild</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="<?= esc(site_url()) ?>" class="btn btn-sm btn-ghost">Accueil</a>
                <a href="<?= esc(site_url('catalogue')) ?>" class="btn btn-sm btn-ghost">Catalogue</a>
                <label class="label cursor-pointer gap-2 rounded-box border border-base-300 px-3 py-2">
                    <span id="theme-toggle-label" class="label-text text-sm">Light</span>
                    <input id="theme-toggle" type="checkbox" class="toggle toggle-sm">
                </label>
            </div>
        </div>
    </header>


