<?php

declare(strict_types=1);

namespace MediaGoneWild\Tests\Unit;

use MediaGoneWild\MediaRepository;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la sélection des médias dans le dépôt de fichiers.
 *
 * La suite couvre les cas limites les plus importants : absence de dossier,
 * dossier vide, sélection déterministe avec seed et sélection aléatoire sans seed.
 */
final class MediaRepositoryTest extends TestCase
{
    /**
     * Vérifie qu'un type absent renvoie null.
     *
     * Le dépôt ne doit jamais inventer de chemin quand le dossier attendu
     * n'existe pas sur le disque.
     */
    public function testPickReturnsNullWhenDirectoryDoesNotExist(): void
    {
        $repository = new MediaRepository(__DIR__ . '/fixtures-not-found');

        self::assertNull($repository->pick('photo'));
    }

    /**
     * Vérifie qu'un dossier vide renvoie null.
     *
     * Même si le type existe, l'API doit signaler l'absence de média exploitable.
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
     *
     * Cette propriété garantit la reproductibilité attendue par l'API quand
     * un client réutilise la même valeur de seed.
     */
    public function testPickWithSeedIsDeterministic(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        // Trois fichiers suffisent pour vérifier que l'index calculé reste stable.
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
     *
     * Le test ne fige pas le nom du fichier choisi, il vérifie seulement que
     * le dépôt renvoie un chemin réellement présent sur le disque.
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
     * Vérifie que l'ajout d'un fichier ne remappe pas massivement les seeds existants.
     *
     * Le comportement attendu est qu'une majorité de seeds conservent leur média,
     * ce qui limite les surprises quand le catalogue évolue.
     */
    public function testAddingFileDoesNotMassivelyRemapExistingSeeds(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $logoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'logo';
        mkdir($logoDirectory);

        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'alpha.svg', '<svg>alpha</svg>');
        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'beta.svg', '<svg>beta</svg>');
        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'gamma.svg', '<svg>gamma</svg>');

        $repository = new MediaRepository($baseDirectory);

        $before = [];
        for ($i = 0; $i < 200; $i++) {
            $seed = 'seed-' . $i;
            $before[$seed] = $repository->pick('logo', $seed);
        }

        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'delta.svg', '<svg>delta</svg>');

        $unchanged = 0;
        for ($i = 0; $i < 200; $i++) {
            $seed = 'seed-' . $i;
            $after = $repository->pick('logo', $seed);

            if ($before[$seed] === $after) {
                $unchanged++;
            }
        }

        self::assertGreaterThanOrEqual(100, $unchanged);
    }

    /**
     * Crée un répertoire temporaire unique pour les tests et le supprime à la fin.
     *
     * Chaque scénario manipule ainsi ses propres fichiers sans dépendre du contenu
     * réel du projet ni laisser de traces après l'exécution de PHPUnit.
     */
    protected function createTemporaryDirectory(): string
    {
        $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'media-gone-wild-tests-' . uniqid('', true);
        mkdir($basePath, 0777, true);

        // Compte cette préparation comme une assertion pour documenter l'intention du test.
        $this->addToAssertionCount(1);
        register_shutdown_function(static function () use ($basePath): void {
            if (!is_dir($basePath)) {
                return;
            }

            // Supprime d'abord le contenu enfant avant de retirer le dossier racine.
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



