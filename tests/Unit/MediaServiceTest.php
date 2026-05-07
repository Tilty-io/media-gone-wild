<?php

declare(strict_types=1);

namespace MediaGoneWild\Tests\Unit;

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

        $service = new MediaService(new MediaRepository($baseDirectory));
        $media = $service->pickMedia('photo');

        self::assertNotNull($media);
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

        $service = new MediaService(new MediaRepository($baseDirectory));

        $first = $service->pickMedia('logo', 'robert');
        $second = $service->pickMedia('logo', 'robert');

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertSame($first->getPath(), $second->getPath());
    }

    /**
     * Vérifie qu'un type absent ne retourne aucun média.
     */
    public function testPickMediaReturnsNullWhenDirectoryDoesNotExist(): void
    {
        $service = new MediaService(new MediaRepository(__DIR__ . '/fixtures-not-found'));

        self::assertNull($service->pickMedia('video'));
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
}

