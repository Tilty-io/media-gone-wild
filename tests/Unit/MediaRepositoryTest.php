<?php

declare(strict_types=1);

namespace MediaGoneWild\Tests\Unit;

use MediaGoneWild\MediaRepository;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la sélection des médias dans le dépôt de fichiers.
 */
final class MediaRepositoryTest extends TestCase
{
    /**
     * Vérifie qu'un type absent renvoie null.
     */
    public function testPickReturnsNullWhenDirectoryDoesNotExist(): void
    {
        $repository = new MediaRepository(__DIR__ . '/fixtures-not-found');

        self::assertNull($repository->pick('photo'));
    }

    /**
     * Vérifie qu'un dossier vide renvoie null.
     */
    public function testPickReturnsNullWhenDirectoryIsEmpty(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        mkdir($baseDirectory . DIRECTORY_SEPARATOR . 'photo');

        $repository = new MediaRepository($baseDirectory);

        self::assertNull($repository->pick('photo'));
    }

    /**
     * Vérifie qu'un seed identique renvoie toujours le même fichier.
     */
    public function testPickWithSeedIsDeterministic(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'a.jpg', 'a');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'b.jpg', 'b');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'c.jpg', 'c');

        $repository = new MediaRepository($baseDirectory);

        $first = $repository->pick('photo', 'robert');
        $second = $repository->pick('photo', 'robert');

        self::assertNotNull($first);
        self::assertSame($first, $second);
    }

    /**
     * Vérifie qu'une sélection sans seed retourne bien un fichier existant.
     */
    public function testPickWithoutSeedReturnsAnExistingFile(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $videoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'video';
        mkdir($videoDirectory);

        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'one.mp4', '1');
        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'two.mp4', '2');

        $repository = new MediaRepository($baseDirectory);
        $picked = $repository->pick('video');

        self::assertNotNull($picked);
        self::assertFileExists($picked);
    }

    /**
     * Crée un répertoire temporaire unique pour les tests et le supprime à la fin.
     */
    protected function createTemporaryDirectory(): string
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'media-gone-wild-tests-' . uniqid('', true);
        mkdir($basePath, 0777, true);

        $this->addToAssertionCount(1);
        register_shutdown_function(static function () use ($basePath): void {
            if (!is_dir($basePath)) {
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

