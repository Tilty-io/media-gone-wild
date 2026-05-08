<?php
/** @var list<array{title: string, description: string, url: string, previewType: string, previewUrl: string}> $features */
?>
<section class="mb-8">
    <h2 class="text-xl font-semibold">Démonstrations</h2>
    <p class="mt-1 text-sm opacity-70">Même photo, un paramètre à la fois, pour voir clairement ce que chaque option change.</p>

    <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($features as $feature): ?>
            <?= view('home/partials/demo-card', ['feature' => $feature]) ?>
        <?php endforeach; ?>
    </div>
</section>

