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
     * Vérifie que le catalogue ne jette plus d'exception quand un fichier référencé dans
     * le manifeste a été supprimé du disque.
     */
    public function testListEntriesSkipsDeletedFilesWithoutThrowing(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'keep.jpg', 'keep');
        $deletedFile = $photoDirectory . DIRECTORY_SEPARATOR . 'deleted.jpg';
        file_put_contents($deletedFile, 'deleted');

        $this->writeIdsManifest($baseDirectory, [
            'aaa111bbb222' => 'photo/keep.jpg',
            'ccc333ddd444' => 'photo/deleted.jpg',
        ]);

        // Suppression du fichier après l'écriture du manifeste.
        unlink($deletedFile);

        $repository = new MediaRepository($baseDirectory);

        // Ne doit pas lever d'exception.
        $entries = $repository->listEntries('photo');

        self::assertCount(1, $entries);
        self::assertSame('aaa111bbb222', $entries[0]['id']);
    }

    /**
     * Vérifie que findOrphanedIdRelativePaths retourne les chemins des fichiers supprimés.
     */
    public function testFindOrphanedIdRelativePathsReturnsDeletedFiles(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'keep.jpg', 'keep');
        $deletedFile = $photoDirectory . DIRECTORY_SEPARATOR . 'deleted.jpg';
        file_put_contents($deletedFile, 'deleted');

        $this->writeIdsManifest($baseDirectory, [
            'aaa111bbb222' => 'photo/keep.jpg',
            'ccc333ddd444' => 'photo/deleted.jpg',
        ]);

        unlink($deletedFile);

        $repository = new MediaRepository($baseDirectory);
        $orphaned   = $repository->findOrphanedIdRelativePaths();

        self::assertCount(1, $orphaned);
        self::assertSame('photo/deleted.jpg', $orphaned[0]);
    }

    /**
     * Vérifie que cleanupOrphanedIds supprime les entrées orphelines du manifeste.
     */
    public function testCleanupOrphanedIdsRemovesDeletedFilesFromManifest(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'keep.jpg', 'keep');
        $deletedFile = $photoDirectory . DIRECTORY_SEPARATOR . 'deleted.jpg';
        file_put_contents($deletedFile, 'deleted');

        $this->writeIdsManifest($baseDirectory, [
            'aaa111bbb222' => 'photo/keep.jpg',
            'ccc333ddd444' => 'photo/deleted.jpg',
        ]);

        unlink($deletedFile);

        $repository = new MediaRepository($baseDirectory);
        $result     = $repository->cleanupOrphanedIds();

        self::assertSame(1, $result['removed']);
        self::assertSame(1, $result['total']);
        self::assertTrue($result['changed']);

        $manifest = $this->readIdsManifest($baseDirectory);
        self::assertArrayHasKey('aaa111bbb222', $manifest);
        self::assertArrayNotHasKey('ccc333ddd444', $manifest);
    }

    /**
     * Vérifie que cleanupOrphanedIds en mode dry-run ne modifie pas le manifeste.
     */
    public function testCleanupOrphanedIdsDryRunDoesNotWriteFile(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);

        $deletedFile = $photoDirectory . DIRECTORY_SEPARATOR . 'deleted.jpg';
        file_put_contents($deletedFile, 'deleted');

        $this->writeIdsManifest($baseDirectory, [
            'aaa111bbb222' => 'photo/deleted.jpg',
        ]);

        unlink($deletedFile);

        $manifestPath = $baseDirectory . DIRECTORY_SEPARATOR . 'ids.json';
        $before = file_get_contents($manifestPath);

        $repository = new MediaRepository($baseDirectory);
        $result     = $repository->cleanupOrphanedIds(true);

        $after = file_get_contents($manifestPath);

        self::assertSame(1, $result['removed']);
        self::assertTrue($result['changed']);
        self::assertSame($before, $after);
    }

    /**
     * Vérifie que les fichiers dans des sous-dossiers d'un type de média sont bien inclus.
     *
     * Par exemple, `photo/products/foo.jpg` doit être listé et sélectionnable
     * au même titre que `photo/foo.jpg`.
     */
    public function testFilesInSubdirectoriesAreIncluded(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        $subDirectory   = $photoDirectory . DIRECTORY_SEPARATOR . 'products';
        mkdir($subDirectory, 0777, true);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg', 'root');
        file_put_contents($subDirectory   . DIRECTORY_SEPARATOR . 'sub.jpg',  'sub');

        $repository = new MediaRepository($baseDirectory);
        $entries    = $repository->listEntries('photo');

        self::assertCount(2, $entries);
        $paths = array_column($entries, 'path');
        self::assertContains($photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg', $paths);
        self::assertContains($subDirectory   . DIRECTORY_SEPARATOR . 'sub.jpg',  $paths);
    }

    /**
     * Vérifie que la clé `collection` est correctement renseignée dans les entrées.
     *
     * Les fichiers à la racine du type doivent avoir `collection = null`.
     * Les fichiers dans un sous-dossier direct doivent avoir `collection = nom_du_sous_dossier`.
     */
    public function testEntriesExposeCollectionKey(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        $subDirectory   = $photoDirectory . DIRECTORY_SEPARATOR . 'products';
        mkdir($subDirectory, 0777, true);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg', 'root');
        file_put_contents($subDirectory   . DIRECTORY_SEPARATOR . 'sub.jpg',  'sub');

        $this->writeIdsManifest($baseDirectory, [
            'aaa111bbb222' => 'photo/root.jpg',
            'ccc333ddd444' => 'photo/products/sub.jpg',
        ]);

        $repository = new MediaRepository($baseDirectory);
        $entries    = $repository->listEntries('photo');

        $byId = array_column($entries, null, 'id');

        self::assertNull($byId['aaa111bbb222']['collection']);
        self::assertSame('products', $byId['ccc333ddd444']['collection']);
    }

    /**
     * Vérifie que listCollections retourne uniquement les collections peuplées.
     */
    public function testListCollectionsReturnsPopulatedSubdirectories(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory . DIRECTORY_SEPARATOR . 'alpha', 0777, true);
        mkdir($photoDirectory . DIRECTORY_SEPARATOR . 'beta',  0777, true);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg', 'root');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'alpha' . DIRECTORY_SEPARATOR . 'a.jpg', 'a');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'beta'  . DIRECTORY_SEPARATOR . 'b.jpg', 'b');

        $repository   = new MediaRepository($baseDirectory);
        $collections  = $repository->listCollections('photo');

        self::assertSame(['alpha', 'beta'], $collections);
    }

    /**
     * Vérifie que listEntries filtre correctement les médias par collection.
     */
    public function testListEntriesFiltersbyCollection(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory . DIRECTORY_SEPARATOR . 'products', 0777, true);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg', 'root');
        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . 'p.jpg', 'p');

        $repository = new MediaRepository($baseDirectory);

        self::assertCount(1, $repository->listEntries('photo', 'products'));
        self::assertCount(2, $repository->listEntries('photo'));
        self::assertCount(0, $repository->listEntries('photo', 'inexistant'));
    }

    /**
     * Vérifie que pick avec une collection ne renvoie que des médias de cette collection.
     */
    public function testPickWithCollectionOnlyReturnsFilesFromThatCollection(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory . DIRECTORY_SEPARATOR . 'products', 0777, true);

        file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg', 'root');
        $productFile = $photoDirectory . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . 'prod.jpg';
        file_put_contents($productFile, 'prod');

        $repository = new MediaRepository($baseDirectory);

        for ($i = 0; $i < 20; $i++) {
            $picked = $repository->pick('photo', null, 'products');
            self::assertSame($productFile, $picked);
        }
    }

    /**
     * Vérifie que getCollectionByPath retourne null pour un fichier à la racine du type.
     */
    public function testGetCollectionByPathReturnsNullForRootFile(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $photoDirectory = $baseDirectory . DIRECTORY_SEPARATOR . 'photo';
        mkdir($photoDirectory);
        $filePath = $photoDirectory . DIRECTORY_SEPARATOR . 'root.jpg';
        file_put_contents($filePath, 'root');

        $repository = new MediaRepository($baseDirectory);

        self::assertNull($repository->getCollectionByPath($filePath));
    }

    /**
     * Vérifie que getCollectionByPath retourne le nom du sous-dossier direct.
     */
    public function testGetCollectionByPathReturnsSubdirectoryName(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $subDirectory  = $baseDirectory . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'products';
        mkdir($subDirectory, 0777, true);
        $filePath = $subDirectory . DIRECTORY_SEPARATOR . 'p.jpg';
        file_put_contents($filePath, 'p');

        $repository = new MediaRepository($baseDirectory);

        self::assertSame('products', $repository->getCollectionByPath($filePath));
    }

    /**
     * Vérifie que findById fonctionne pour un fichier situé dans un sous-dossier.
     */
    public function testFindByIdWorksForFileInSubdirectory(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        $subDirectory  = $baseDirectory . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'products';
        mkdir($subDirectory, 0777, true);

        $filePath = $subDirectory . DIRECTORY_SEPARATOR . 'widget.jpg';
        file_put_contents($filePath, 'widget');

        $this->writeIdsManifest($baseDirectory, [
            'abc123def456' => 'photo/products/widget.jpg',
        ]);

        $repository = new MediaRepository($baseDirectory);

        self::assertSame($filePath, $repository->findById('photo', 'abc123def456'));
    }

    /**
     * Vérifie que deux fichiers de même nom dans des sous-dossiers différents
     * reçoivent bien des IDs distincts lors de la synchronisation.
     */
    public function testSyncAssignsDistinctIdsToFilesWithSameNameInDifferentSubdirectories(): void
    {
        $baseDirectory = $this->createTemporaryDirectory();
        mkdir($baseDirectory . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'a', 0777, true);
        mkdir($baseDirectory . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'b', 0777, true);

        file_put_contents($baseDirectory . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'same.jpg', 'a');
        file_put_contents($baseDirectory . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'same.jpg', 'b');

        $repository = new MediaRepository($baseDirectory);
        $repository->syncIdsManifest();

        $manifest = $this->readIdsManifest($baseDirectory);

        self::assertCount(2, $manifest);
        self::assertContains('photo/a/same.jpg', $manifest);
        self::assertContains('photo/b/same.jpg', $manifest);
        // Les deux IDs doivent être différents
        self::assertSame(2, count(array_unique(array_keys($manifest))));
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



