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
     * Vérifie que la version de pipeline est intégrée à la clé de cache.
     */
    public function testTransformMediaCacheKeyIncludesPipelineVersion(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('Extension GD requise pour les tests de transformation.');
        }

        $baseDirectory = $this->createTemporaryDirectory();
        $photoDir      = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDir);

        $this->createTestJpeg($photoDir . DIRECTORY_SEPARATOR . 'versioned_cache.jpg', 24, 24);
        $this->writeIdsManifest($baseDirectory, [
            'facefaceface' => 'photo/versioned_cache.jpg',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media   = $service->getMediaById('photo', 'facefaceface');
        self::assertNotNull($media);

        $opts = MediaTransformOptions::fromQueryParams([
            'width' => '12',
            'quality' => '70',
        ]);

        $transformed = $service->transformMedia($media, $opts);

        $reflection = new \ReflectionClass(MediaService::class);
        $cacheVersion = (string) $reflection->getConstant('TRANSFORM_CACHE_VERSION');
        $expectedBaseName = hash('sha256', $cacheVersion . '|' . $opts->toCacheKey($media->getId()));

        // Vérifie que le nom de fichier correspond au hash attendu.
        self::assertSame($expectedBaseName, pathinfo($transformed->getPath(), PATHINFO_FILENAME));

        // Vérifie que le chemin inclut le sous-dossier de version et le sous-dossier d'ID.
        $normalizedPath = str_replace('\\', '/', $transformed->getPath());
        self::assertStringContainsString('/' . $cacheVersion . '/', $normalizedPath);
        self::assertStringContainsString('/' . $media->getId() . '/', $normalizedPath);
    }

    /**
     * Vérifie que `bgcolor` sert de fond de composition même en `fit=contain`,
     * y compris derrière les zones transparentes de l'image source.
     */
    public function testTransformMediaContainComposesOnBgcolorBackground(): void
    {
        if (! extension_loaded('gd')) {
            self::markTestSkipped('Extension GD requise pour les tests de transformation.');
        }

        $baseDirectory = $this->createTemporaryDirectory();
        $photoDir      = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDir);

        $this->createTestTransparentPng($photoDir . DIRECTORY_SEPARATOR . 'alpha.png', 100, 100);
        $this->writeIdsManifest($baseDirectory, [
            'abc123abc123' => 'photo/alpha.png',
        ]);

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media   = $service->getMediaById('photo', 'abc123abc123');
        self::assertNotNull($media);

        $opts = MediaTransformOptions::fromQueryParams([
            'width' => '300',
            'height' => '200',
            'fit' => 'contain',
            'extension' => 'png',
            'bgcolor' => 'ff000099',
        ]);

        $transformed = $service->transformMedia($media, $opts);
        self::assertTrue($transformed->isReadable());
        self::assertFileExists($transformed->getPath());

        $output = imagecreatefrompng($transformed->getPath());
        if ($output === false) {
            self::fail('Impossible de lire le PNG transformé pour vérifier les pixels.');
        }

        // Zone letterbox latérale (x<50) : doit être colorée par le bgcolor.
        $letterbox = $this->readPixelRgba($output, 10, 100);
        self::assertGreaterThan($letterbox['g'], $letterbox['r']);
        self::assertGreaterThan($letterbox['b'], $letterbox['r']);
        self::assertGreaterThan(0, $letterbox['a']);

        // Zone transparente interne de la source : doit aussi être colorée par le bgcolor.
        $innerTransparent = $this->readPixelRgba($output, 70, 20);
        self::assertGreaterThan($innerTransparent['g'], $innerTransparent['r']);
        self::assertGreaterThan($innerTransparent['b'], $innerTransparent['r']);
        self::assertGreaterThan(0, $innerTransparent['a']);

        // Zone opaque rouge au centre : l'objet source reste visible au-dessus du fond.
        $center = $this->readPixelRgba($output, 150, 100);
        self::assertGreaterThan($center['g'], $center['r']);
        self::assertGreaterThan($center['b'], $center['r']);
        self::assertSame(0, $center['a']);

        imagedestroy($output);
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

    /**
     * Crée un PNG transparent avec un carré opaque au centre pour tester la composition alpha.
     */
    private function createTestTransparentPng(string $targetPath, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            self::fail('Impossible de créer une image PNG de test.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, $width, $height, $transparent);

        $opaqueRed = imagecolorallocatealpha($image, 255, 0, 0, 0);
        $paddingX = max(1, (int) floor($width * 0.35));
        $paddingY = max(1, (int) floor($height * 0.35));
        imagefilledrectangle(
            $image,
            $paddingX,
            $paddingY,
            $width - $paddingX,
            $height - $paddingY,
            $opaqueRed,
        );

        imagepng($image, $targetPath);
        imagedestroy($image);
    }

    /**
     * Lit un pixel RGBA (format GD) et retourne ses canaux séparés.
     *
     * @param resource $image
     *
     * @return array{r: int, g: int, b: int, a: int}
     */
    private function readPixelRgba($image, int $x, int $y): array
    {
        $rgba = imagecolorat($image, $x, $y);

        return [
            'r' => ($rgba >> 16) & 0xFF,
            'g' => ($rgba >> 8) & 0xFF,
            'b' => $rgba & 0xFF,
            'a' => ($rgba & 0x7F000000) >> 24,
        ];
    }
}

