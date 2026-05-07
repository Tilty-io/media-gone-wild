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
     * Vérifie qu'un identifiant stable résout exactement le média attendu.
     */
    public function testFindByIdReturnsMatchingFileForKnownMedia(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        $filePath = $photoDirectory . DIRECTORY_SEPARATOR . 'sample.jpg';
        file_put_contents($filePath, 'sample');
        $this->writeIdsManifest($baseDirectory, [
            'abc123def456' => 'photo/sample.jpg',
        ]);

        $repository = new MediaRepository($baseDirectory);

        self::assertSame($filePath, $repository->findById('photo', 'abc123def456'));
    }

    /**
     * Vérifie qu'un ID connu mais d'un autre type ne fuite jamais vers le mauvais endpoint.
     */
    public function testFindByIdReturnsNullForWrongType(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        mkdir($baseDirectory . DIRECTORY_SEPARATOR . 'photo');
        $logoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'logo';
        mkdir($logoDirectory);

        file_put_contents($logoDirectory . DIRECTORY_SEPARATOR . 'brand.svg', '<svg>brand</svg>');
        $this->writeIdsManifest($baseDirectory, [
            'def456abc123' => 'logo/brand.svg',
        ]);

        $repository = new MediaRepository($baseDirectory);

        self::assertNull($repository->findById('photo', 'def456abc123'));
    }

    /**
     * Vérifie que la liste enrichie expose les IDs définis dans le manifeste.
     */
    public function testListEntriesReturnsIdsDefinedInManifest(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $videoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'video';
        mkdir($videoDirectory);

        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'alpha.mp4', 'alpha');
        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'beta.mp4', 'beta');
        $this->writeIdsManifest($baseDirectory, [
            '111aaa222bbb' => 'video/alpha.mp4',
            '333ccc444ddd' => 'video/beta.mp4',
        ]);

        $repository = new MediaRepository($baseDirectory);
        $entries = $repository->listEntries('video');

        self::assertCount(2, $entries);
        self::assertSame('111aaa222bbb', $entries[0]['id']);
        self::assertSame('333ccc444ddd', $entries[1]['id']);
    }

    /**
     * Vérifie que la synchronisation ajoute les IDs manquants sans modifier ceux déjà présents.
     */
    public function testSyncIdsManifestAddsMissingMediaWithoutChangingExistingIds(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'a.jpg', 'a');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'b.jpg', 'b');
        $this->writeIdsManifest($baseDirectory, [
            'abc123def456' => 'photo/a.jpg',
        ]);

        $repository = new MediaRepository($baseDirectory);
        $result = $repository->syncIdsManifest();

        self::assertSame(1, $result['added']);
        self::assertSame(2, $result['total']);
        self::assertTrue($result['changed']);

        $manifest = $this->readIdsManifest($baseDirectory);
        self::assertSame('photo/a.jpg', $manifest['abc123def456']);

        $addedEntry = array_filter(
            $manifest,
            static fn (string $path, string $id): bool => $id !== 'abc123def456' && $path === 'photo/b.jpg',
            ARRAY_FILTER_USE_BOTH,
        );

        self::assertCount(1, $addedEntry);
        self::assertMatchesRegularExpression('/^[a-z0-9]{12,}$/', (string) array_key_first($addedEntry));
    }

    /**
     * Vérifie que le mode dry-run n'écrit pas le manifeste sur disque.
     */
    public function testSyncIdsManifestDryRunDoesNotWriteFile(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $videoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'video';
        mkdir($videoDirectory);

        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'x.mp4', 'x');
        file_put_contents($videoDirectory . DIRECTORY_SEPARATOR . 'y.mp4', 'y');
        $this->writeIdsManifest($baseDirectory, [
            '111aaa222bbb' => 'video/x.mp4',
        ]);

        $manifestPath = $baseDirectory . DIRECTORY_SEPARATOR . 'ids.json';
        $before = file_get_contents($manifestPath);

        $repository = new MediaRepository($baseDirectory);
        $result = $repository->syncIdsManifest(true);

        $after = file_get_contents($manifestPath);

        self::assertSame(1, $result['added']);
        self::assertTrue($result['changed']);
        self::assertSame($before, $after);
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

    /**
     * Écrit un manifeste minimal d'IDs pour les scénarios qui exigent des correspondances exactes.
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
     * Lit le manifeste JSON et retourne les associations `id => chemin relatif`.
     *
     * @return array<string, string>
     */
    private function readIdsManifest(string $baseDirectory): array
    {
        $content = file_get_contents($baseDirectory . DIRECTORY_SEPARATOR . 'ids.json');

        if (! is_string($content)) {
            self::fail('Impossible de lire ids.json pendant le test.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            self::fail('ids.json doit contenir un objet JSON valide pendant le test.');
        }

        /** @var array<string, string> $decoded */
        return $decoded;
    }
}



