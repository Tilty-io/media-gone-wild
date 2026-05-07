<?php

declare(strict_types=1);

namespace MediaGoneWild\Tests\Unit;

use App\DTO\MediaTransformOptions;
use App\Services\MediaService;
use MediaGoneWild\MediaRepository;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la préparation des métadonnées HTTP autour des médias sélectionnés.
 */
final class MediaServiceTest extends TestCase
{
    /**
     * Vérifie qu'un média connu produit une description complète exploitable par le contrôleur.
     */
    public function testPickMediaReturnsDescriptorForKnownExtension(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'sample.jpg', 'jpeg-data');
        $this->writeIdsManifest($baseDirectory, [
            'abc123def456' => 'photo/sample.jpg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media = $service->pickMedia('photo');

        self::assertNotNull($media);
        self::assertSame('abc123def456', $media->getId());
        self::assertSame('sample.jpg', $media->getFileName());
        self::assertSame('image/jpeg', $media->getMimeType());
        self::assertSame(strlen('jpeg-data'), $media->getFileSize());
        self::assertTrue($media->isReadable());
    }

    /**
     * Vérifie qu'un seed identique conserve le même chemin résolu.
     */
    public function testPickMediaKeepsDeterministicSelectionWithSeed(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $logoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'logo';
        mkdir($logoDirectory);

        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'a.svg', '<svg>a</svg>');
        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'b.svg', '<svg>b</svg>');
        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'c.svg', '<svg>c</svg>');
        $this->writeIdsManifest($baseDirectory, [
            'aaaaaaaaaaaa' => 'logo/a.svg',
            'bbbbbbbbbbbb' => 'logo/b.svg',
            'cccccccccccc' => 'logo/c.svg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));

        $first = $service->pickMedia('logo', 'robert');
        $second = $service->pickMedia('logo', 'robert');

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertSame($first->getPath(), $second->getPath());
    }

    /**
     * Vérifie qu'un média peut être résolu exactement via son identifiant stable.
     */
    public function testGetMediaByIdReturnsDescriptorForKnownMedia(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $videoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'video';
        mkdir($videoDirectory);

        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'clip.mp4', 'video-data');
        $this->writeIdsManifest($baseDirectory, [
            'feedfacecafe' => 'video/clip.mp4',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media = $service->getMediaById('video', 'feedfacecafe');

        self::assertNotNull($media);
        self::assertSame('feedfacecafe', $media->getId());
        self::assertSame('clip.mp4', $media->getFileName());
    }

    /**
     * Vérifie qu'un ID d'un autre type ne retourne pas de média par erreur.
     */
    public function testGetMediaByIdReturnsNullForWrongType(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        mkdir($baseDirectory . DIRECTORY_SEPARATOR . 'photo');
        $logoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'logo';
        mkdir($logoDirectory);

        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'badge.svg', '<svg>badge</svg>');
        $this->writeIdsManifest($baseDirectory, [
            '123abc456def' => 'logo/badge.svg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));

        self::assertNull($service->getMediaById('photo', '123abc456def'));
    }

    /**
     * Vérifie que le service peut synchroniser les IDs manquants via le dépôt.
     */
    public function testSyncIdsManifestAddsMissingIds(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'known.jpg', 'known');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'missing.jpg', 'missing');
        $this->writeIdsManifest($baseDirectory, [
            'abc123def456' => 'photo/known.jpg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $result = $service->syncIdsManifest();

        self::assertSame(1, $result['added']);
        self::assertSame(2, $result['total']);
        self::assertTrue($result['changed']);

        $media = $service->getMediaById('photo', 'abc123def456');
        self::assertNotNull($media);
        self::assertSame('known.jpg', $media->getFileName());
    }

    /**
     * Vérifie qu'un type absent ne retourne aucun média.
     */
    public function testPickMediaReturnsNullWhenDirectoryDoesNotExist(): void
    {
        $service = new MediaService(new MediaRepository(__DIR__ . '/fixtures-not-found'));

        self::assertNull($service->pickMedia('video'));
    }

    // ─── Tests de transformation ─────────────────────────────────────────────

    /**
     * Vérifie qu'une transformation simple (largeur uniquement) produit un fichier de cache lisible.
     */
    public function testTransformMediaCreatesReadableCachedFileForWidthOnly(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('Extension GD requise pour les tests de transformation.');
        }

        $baseDirectory = $this->createTemporaryDirectory();
        $photoDir      = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDir);

        $this->createTestJpeg($photoDir . DIRECTORY_SEPARATOR . 'sample.jpg', 20, 10);
        $this->writeIdsManifest($baseDirectory, [
            'abc123def456' => 'photo/sample.jpg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media   = $service->pickMedia('photo');
        self::assertNotNull($media);

        $opts = MediaTransformOptions::fromQueryParams(['width' => '10']);
        self::assertTrue($opts->hasOptions());

        $transformed = $service->transformMedia($media, $opts);
        self::assertTrue($transformed->isReadable());
        self::assertStringEndsWith('.jpg', $transformed->getFileName());
        self::assertFileExists($transformed->getPath());
    }

    /**
     * Vérifie qu'un deuxième appel avec les mêmes options retourne le fichier mis en cache
     * sans recalcul (même chemin que le premier appel).
     */
    public function testTransformMediaReturnsCachedFileOnSecondCall(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('Extension GD requise pour les tests de transformation.');
        }

        $baseDirectory = $this->createTemporaryDirectory();
        $photoDir      = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDir);

        $this->createTestJpeg($photoDir . DIRECTORY_SEPARATOR . 'cache_test.jpg', 30, 15);
        $this->writeIdsManifest($baseDirectory, [
            'cachetest1234' => 'photo/cache_test.jpg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media   = $service->pickMedia('photo');
        self::assertNotNull($media);

        $opts   = MediaTransformOptions::fromQueryParams(['width' => '8', 'quality' => '60']);
        $first  = $service->transformMedia($media, $opts);
        $second = $service->transformMedia($media, $opts);

        self::assertSame($first->getPath(), $second->getPath(), 'Le deuxième appel doit utiliser le cache.');
    }

    /**
     * Vérifie que `jpeg` et `jpg` produisent la même clé de cache canonique.
     */
    public function testTransformOptionsNormalizeJpegAliasToJpg(): void
    {
        $jpg = MediaTransformOptions::fromQueryParams(['extension' => 'jpg']);
        $jpeg = MediaTransformOptions::fromQueryParams(['extension' => 'jpeg']);

        self::assertSame('jpg', $jpg->getNormalizedExtension());
        self::assertSame('jpg', $jpeg->getNormalizedExtension());
        self::assertSame($jpg->toCacheKey('same-id'), $jpeg->toCacheKey('same-id'));
    }

    /**
     * Vérifie qu'aucune transformation n'est appliquée quand aucune option n'est fournie.
     */
    public function testTransformOptionsHasOptionsReturnsFalseWithNoParams(): void
    {
        $opts = MediaTransformOptions::fromQueryParams([]);
        self::assertFalse($opts->hasOptions());
    }

    /**
     * Vérifie le calcul de l'alpha pour les différentes formes de bgcolor.
     */
    public function testTransformOptionsBgcolorAlphaCalculation(): void
    {
        // 6 chars hex → opaque
        $opts6 = MediaTransformOptions::fromQueryParams(['bgcolor' => 'ffffff']);
        self::assertSame(255, $opts6->getAlpha());
        self::assertFalse($opts6->hasTransparency());

        // 8 chars avec alpha=0 → transparent
        $opts8zero = MediaTransformOptions::fromQueryParams(['bgcolor' => 'ffffff00']);
        self::assertSame(0, $opts8zero->getAlpha());
        self::assertTrue($opts8zero->hasTransparency());

        // 8 chars avec alpha=128 → semi-transparent
        $opts8semi = MediaTransformOptions::fromQueryParams(['bgcolor' => 'ffffff80']);
        self::assertSame(0x80, $opts8semi->getAlpha());
        self::assertTrue($opts8semi->hasTransparency());

        // mot-clé transparent
        $optsTransparent = MediaTransformOptions::fromQueryParams(['bgcolor' => 'transparent']);
        self::assertSame(0, $optsTransparent->getAlpha());
        self::assertTrue($optsTransparent->hasTransparency());
    }

    /**
     * Vérifie que la clé de cache change quand les options changent.
     */
    public function testTransformOptionsCacheKeyChangesWithDifferentOptions(): void
    {
        $opts1 = MediaTransformOptions::fromQueryParams(['width' => '100']);
        $opts2 = MediaTransformOptions::fromQueryParams(['width' => '200']);
        $opts3 = MediaTransformOptions::fromQueryParams(['width' => '100', 'quality' => '50']);

        self::assertNotSame($opts1->toCacheKey('abc'), $opts2->toCacheKey('abc'));
        self::assertNotSame($opts1->toCacheKey('abc'), $opts3->toCacheKey('abc'));
        self::assertSame($opts1->toCacheKey('abc'), $opts1->toCacheKey('abc'));
    }

    /**
     * Vérifie que la clé de cache change quand l'ID source change.
     */
    public function testTransformOptionsCacheKeyChangesWithDifferentMediaId(): void
    {
        $opts = MediaTransformOptions::fromQueryParams(['width' => '100']);

        self::assertNotSame($opts->toCacheKey('id_alpha'), $opts->toCacheKey('id_beta'));
    }

    /**
     * Vérifie que les valeurs invalides sont silencieusement ignorées lors du parsing.
     */
    public function testTransformOptionsFromQueryParamsIgnoresInvalidValues(): void
    {
        $opts = MediaTransformOptions::fromQueryParams([
            'width'     => '-5',
            'height'    => 'abc',
            'fit'       => 'étirer',
            'extension' => 'bmp',
            'quality'   => '999',
            'bgcolor'   => 'ZZZZZZ',
        ]);

        self::assertFalse($opts->hasOptions());
        self::assertNull($opts->getWidth());
        self::assertNull($opts->getHeight());
        self::assertNull($opts->getFit());
        self::assertNull($opts->getExtension());
        self::assertSame(MediaTransformOptions::DEFAULT_QUALITY, $opts->getQuality());
        self::assertNull($opts->getBgcolor());
    }

    /**
     * Vérifie que `fit=crop` n'est plus accepté et reste ignoré.
     */
    public function testTransformOptionsIgnoresDeprecatedCropFit(): void
    {
        $opts = MediaTransformOptions::fromQueryParams([
            'fit' => 'crop',
        ]);

        self::assertNull($opts->getFit());
        self::assertFalse($opts->hasOptions());
    }

    /**
     * Vérifie que l'extension de sortie est normalisée correctement.
     */
    public function testTransformOptionsExtensionIsNormalized(): void
    {
        $opts = MediaTransformOptions::fromQueryParams(['extension' => 'WEBP']);
        self::assertSame('webp', $opts->getExtension());

        $opts2 = MediaTransformOptions::fromQueryParams(['extension' => 'PNG']);
        self::assertSame('png', $opts2->getExtension());
    }

    /**
     * Crée un répertoire temporaire unique pour les tests et le supprime à la fin.
     */
    private function createTemporaryDirectory(): string
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'media-gone-wild-service-tests-' . uniqid('', true);
        mkdir($basePath, 0777, true);

        register_shutdown_function(static function () use ($basePath): void {
            if (! is_dir($basePath)) {
                return;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } else {
                    unlink($item->getPathname());
                }
            }

            rmdir($basePath);
        });

        return $basePath;
    }

    /**
     * Écrit un manifeste minimal d'IDs pour les scénarios de test du service.
     *
     * @param array<string, string> $manifest Les associations `id => chemin relatif` à écrire.
     */
    private function writeIdsManifest(string $baseDirectory, array $manifest): void
    {
        file_put_contents(
            $baseDirectory . DIRECTORY_SEPARATOR . 'ids.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        );
    }

    /**
     * Crée un petit JPEG valide pour les scénarios de transformation d'image.
     *
     * @param string $targetPath Chemin du fichier JPEG à écrire.
     * @param int $width Largeur de l'image de test.
     * @param int $height Hauteur de l'image de test.
     */
    private function createTestJpeg(string $targetPath, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            self::fail('Impossible de créer une image GD pour le test.');
        }

        $background = imagecolorallocate($image, 120, 45, 200);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        imagejpeg($image, $targetPath, 90);
        imagedestroy($image);
    }
}

