<?php

declare(strict_types=1);

namespace MediaGoneWild;

/**
 * Recherche et sélectionne un média dans l'arborescence locale.
 */
final class MediaRepository
{
    /**
     * Longueur minimale d'un identifiant média lisible et stable.
     */
    private const MINIMUM_MEDIA_ID_LENGTH = 12;

    /**
     * Répertoire racine qui contient les sous-dossiers de médias.
     */
    private string $baseDirectory;

    /**
     * Chemin du manifeste versionné qui associe un ID à chaque média.
     */
    private string $idManifestPath;

    /**
     * Index mémoire des médias par type.
     *
     * @var array<string, list<array{id: string, path: string}>>|null
     */
    private ?array $entriesByType = null;

    /**
     * Index mémoire des chemins relatifs par ID.
     *
     * @var array<string, string>|null
     */
    private ?array $relativePathById = null;

    /**
     * Index mémoire des IDs par chemin relatif.
     *
     * @var array<string, string>|null
     */
    private ?array $idByRelativePath = null;

    /**
     * Initialise le dépôt avec son répertoire racine.
     *
     * @param string $baseDirectory Le chemin du dossier qui contient `photo`, `video` et `logo`.
     * @param string|null $idManifestPath Le chemin du manifeste d'IDs, ou null pour utiliser `media/ids.json`.
     */
    public function __construct(string $baseDirectory, ?string $idManifestPath = null)
    {
        $this->baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR);
        $this->idManifestPath = $idManifestPath ?? $this->baseDirectory . DIRECTORY_SEPARATOR . 'ids.json';
    }

    /**
     * Sélectionne un fichier média pour le type demandé.
     *
     * Quand un seed est fourni, la sélection est déterministe afin de toujours renvoyer
     * le même fichier pour la même valeur. Sans seed, le fichier est choisi aléatoirement.
     *
     * @param string $mediaType Le type de média à chercher.
     * @param string|null $seed Le seed optionnel utilisé pour une sélection reproductible.
     *
     * @return string|null Le chemin complet du fichier choisi, ou null si aucun fichier n'est disponible.
     */
    public function pick(string $mediaType, ?string $seed = null): ?string
    {
        $files = $this->list($mediaType);

        if ($files === []) {
            return null;
        }

        if ($seed !== null && $seed !== '') {
            return $this->pickDeterministicFile($files, $seed);
        }

        // Sans seed, choisit simplement un fichier aléatoire parmi ceux disponibles.
        $index = random_int(0, count($files) - 1);
        return $files[$index];
    }

    /**
     * Retourne la liste des fichiers disponibles pour un type de média.
     *
     * @param string $mediaType Le type de média à lister.
     *
     * @return list<string> Les chemins absolus des fichiers trouvés, triés par nom.
     */
    public function list(string $mediaType): array
    {
        return array_map(
            static fn (array $entry): string => $entry['path'],
            $this->listEntries($mediaType),
        );
    }

    /**
     * Retourne la liste des médias disponibles pour un type avec leur identifiant stable.
     *
     * @param string $mediaType Le type de média à lister.
     *
     * @return list<array{id: string, path: string}> Les médias disponibles pour le type demandé.
     */
    public function listEntries(string $mediaType): array
    {
        $this->loadMediaIndexes();

        return $this->entriesByType[$mediaType] ?? [];
    }

    /**
     * Résout un média exact par nom de fichier dans un type donné.
     *
     * @param string $mediaType Le type de média à inspecter.
     * @param string $fileName Le nom de fichier demandé par le client.
     *
     * @return string|null Le chemin absolu du fichier, ou null s'il est introuvable.
     */
    public function findByFileName(string $mediaType, string $fileName): ?string
    {
        $normalizedFileName = basename(trim($fileName));

        if ($normalizedFileName === '' || $normalizedFileName !== $fileName) {
            return null;
        }

        foreach ($this->listEntries($mediaType) as $entry) {
            if (basename($entry['path']) === $normalizedFileName) {
                return $entry['path'];
            }
        }

        return null;
    }

    /**
     * Résout un média exact par identifiant stable dans un type donné.
     *
     * @param string $mediaType Le type de média à inspecter.
     * @param string $id L'identifiant stable du média.
     *
     * @return string|null Le chemin absolu du fichier, ou null si l'ID est inconnu ou ne correspond pas au type.
     */
    public function findById(string $mediaType, string $id): ?string
    {
        $normalizedId = strtolower(trim($id));

        if (! $this->isValidMediaId($normalizedId)) {
            return null;
        }

        $this->loadMediaIndexes();

        $relativePath = $this->relativePathById[$normalizedId] ?? null;

        if ($relativePath === null || ! str_starts_with($relativePath, $mediaType . '/')) {
            return null;
        }

        return $this->baseDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * Retourne l'identifiant stable d'un média à partir de son chemin absolu.
     *
     * @param string $path Le chemin absolu du média.
     *
     * @return string|null L'identifiant stable du média, ou null s'il n'est pas indexé.
     */
    public function getIdByPath(string $path): ?string
    {
        $this->loadMediaIndexes();

        $relativePath = $this->relativePathFromAbsolutePath($path);

        if ($relativePath === null) {
            return null;
        }

        return $this->idByRelativePath[$relativePath] ?? null;
    }

    /**
     * Retourne les chemins relatifs des médias présents sur disque mais absents du manifeste.
     *
     * @return list<string> Les médias sans identifiant stable.
     */
    public function findMissingIdRelativePaths(): array
    {
        $relativePathById = $this->loadManifestIdMap();
        $mappedRelativePaths = array_fill_keys(array_values($relativePathById), true);
        $missingRelativePaths = [];

        foreach ($this->listRelativeMediaPaths() as $relativePath) {
            if (! isset($mappedRelativePaths[$relativePath])) {
                $missingRelativePaths[] = $relativePath;
            }
        }

        return $missingRelativePaths;
    }

    /**
     * Indique si au moins un média n'a pas encore d'ID stable dans le manifeste.
     */
    public function hasMissingIds(): bool
    {
        return $this->countMissingIds() > 0;
    }

    /**
     * Retourne le nombre de médias présents sur disque sans ID stable.
     */
    public function countMissingIds(): int
    {
        return count($this->findMissingIdRelativePaths());
    }

    /**
     * Complète le manifeste `ids.json` en ajoutant un ID pour chaque média manquant.
     *
     * Les IDs existants restent inchangés pour garantir la stabilité dans le temps.
     *
     * @param bool $dryRun Si true, simule la synchronisation sans écrire le fichier.
     *
     * @return array{added: int, total: int, changed: bool} Le bilan de synchronisation.
     */
    public function syncIdsManifest(bool $dryRun = false): array
    {
        $relativePathById = $this->loadManifestIdMap();
        $missingRelativePaths = $this->findMissingIdRelativePaths();

        if ($missingRelativePaths === []) {
            return [
                'added' => 0,
                'total' => count($relativePathById),
                'changed' => false,
            ];
        }

        $usedIds = array_fill_keys(array_keys($relativePathById), true);

        foreach ($missingRelativePaths as $relativePath) {
            $newId = $this->generateDeterministicMediaId($relativePath, $usedIds);
            $relativePathById[$newId] = $relativePath;
            $usedIds[$newId] = true;
        }

        if (! $dryRun) {
            $this->persistManifestIdMap($relativePathById);
            $this->resetIndexes();
        }

        return [
            'added' => count($missingRelativePaths),
            'total' => count($relativePathById),
            'changed' => true,
        ];
    }

    /**
     * Sélectionne un fichier déterministe avec un hachage rendezvous.
     *
     * Cette stratégie évite de remapper massivement les seeds quand un nouveau fichier
     * est ajouté : seul un sous-ensemble des seeds bascule vers ce nouveau fichier.
     *
     * @param list<string> $files Les chemins complets disponibles pour le type demandé.
     * @param string $seed Le seed fourni par le client.
     *
     * @return string Le chemin complet du fichier choisi pour ce seed.
     */
    private function pickDeterministicFile(array $files, string $seed): string
    {
        $pickedFile = $files[0];
        $bestScore = hash('sha256', $seed . '|' . basename($pickedFile));

        foreach ($files as $file) {
            $score = hash('sha256', $seed . '|' . basename($file));

            if (strcmp($score, $bestScore) > 0) {
                $bestScore = $score;
                $pickedFile = $file;
            }
        }

        return $pickedFile;
    }

    /**
     * Charge les index mémoire utilisés pour lister et résoudre les médias.
     */
    private function loadMediaIndexes(): void
    {
        if ($this->entriesByType !== null && $this->relativePathById !== null && $this->idByRelativePath !== null) {
            return;
        }

        $entries = is_file($this->idManifestPath)
            ? $this->loadEntriesFromManifest()
            : $this->buildEntriesFromFilesystem();

        $entriesByType = [];
        $relativePathById = [];
        $idByRelativePath = [];

        foreach ($entries as $entry) {
            $relativePath = $entry['relativePath'];
            $path = $this->baseDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $mediaType = strtok($relativePath, '/');

            if (! is_string($mediaType) || $mediaType === '') {
                throw new \LogicException('Chaque média indexé doit appartenir à un type valide.');
            }

            $entriesByType[$mediaType][] = [
                'id' => $entry['id'],
                'path' => $path,
            ];
            $relativePathById[$entry['id']] = $relativePath;
            $idByRelativePath[$relativePath] = $entry['id'];
        }

        foreach ($entriesByType as &$mediaTypeEntries) {
            usort(
                $mediaTypeEntries,
                static fn (array $left, array $right): int => strcmp($left['path'], $right['path']),
            );
        }
        unset($mediaTypeEntries);

        $this->entriesByType = $entriesByType;
        $this->relativePathById = $relativePathById;
        $this->idByRelativePath = $idByRelativePath;
    }

    /**
     * Charge les associations `id => chemin relatif` présentes dans le manifeste.
     *
     * @return array<string, string> Le mapping stable des médias indexés.
     */
    private function loadManifestIdMap(): array
    {
        if (! is_file($this->idManifestPath)) {
            return [];
        }

        $manifestContent = file_get_contents($this->idManifestPath);

        if (! is_string($manifestContent) || $manifestContent === '') {
            throw new \LogicException('Le manifeste des IDs médias est vide ou illisible.');
        }

        $manifestContent = preg_replace('/^\xEF\xBB\xBF/', '', $manifestContent);
        $decodedManifest = json_decode($manifestContent, true);

        if (! is_array($decodedManifest)) {
            throw new \LogicException('Le manifeste des IDs médias doit contenir un objet JSON valide.');
        }

        $relativePathById = [];
        $seenRelativePaths = [];

        foreach ($decodedManifest as $id => $relativePath) {
            if (! is_string($id) || ! $this->isValidMediaId($id)) {
                throw new \LogicException('Chaque ID média du manifeste doit être alphanumérique en minuscules.');
            }

            if (! is_string($relativePath)) {
                throw new \LogicException('Chaque entrée du manifeste doit pointer vers un chemin relatif texte.');
            }

            $normalizedRelativePath = $this->normalizeRelativePath($relativePath);

            if ($normalizedRelativePath === null) {
                throw new \LogicException('Le manifeste contient un chemin média invalide.');
            }

            if (isset($seenRelativePaths[$normalizedRelativePath])) {
                throw new \LogicException('Chaque média doit être associé à un seul ID unique.');
            }

            $relativePathById[$id] = $normalizedRelativePath;
            $seenRelativePaths[$normalizedRelativePath] = true;
        }

        return $relativePathById;
    }

    /**
     * Charge les identifiants depuis le manifeste versionné du projet.
     *
     * @return list<array{id: string, relativePath: string}> Les médias définis dans le manifeste.
     */
    private function loadEntriesFromManifest(): array
    {
        $relativePathById = $this->loadManifestIdMap();

        $entries = [];

        foreach ($relativePathById as $id => $relativePath) {
            $absolutePath = $this->baseDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (! is_file($absolutePath)) {
                throw new \LogicException('Le manifeste référence un média introuvable sur le disque : ' . $relativePath);
            }

            $entries[] = [
                'id' => $id,
                'relativePath' => $relativePath,
            ];
        }

        usort(
            $entries,
            static fn (array $left, array $right): int => strcmp($left['relativePath'], $right['relativePath']),
        );

        return $entries;
    }

    /**
     * Retourne la liste des médias présents sur disque, triés par chemin relatif.
     *
     * @return list<string> Les chemins relatifs des médias présents sur disque.
     */
    private function listRelativeMediaPaths(): array
    {
        $directories = glob($this->baseDirectory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_STRING);

        $relativePaths = [];

        foreach ($directories as $directory) {
            $mediaType = basename($directory);
            $files = glob($directory . DIRECTORY_SEPARATOR . '*') ?: [];
            $files = array_values(array_filter($files, 'is_file'));
            sort($files, SORT_STRING);

            foreach ($files as $filePath) {
                $relativePaths[] = $mediaType . '/' . basename($filePath);
            }
        }

        return $relativePaths;
    }

    /**
     * Construit un index déterministe en mémoire quand aucun manifeste n'est présent.
     *
     * @return list<array{id: string, relativePath: string}> Les médias trouvés sur le disque avec un ID de secours.
     */
    private function buildEntriesFromFilesystem(): array
    {
        $entries = [];
        $usedIds = [];

        foreach ($this->listRelativeMediaPaths() as $relativePath) {
            $id = $this->generateDeterministicMediaId($relativePath, $usedIds);
            $usedIds[$id] = true;

            $entries[] = [
                'id' => $id,
                'relativePath' => $relativePath,
            ];
        }

        return $entries;
    }

    /**
     * Enregistre le manifeste des IDs en JSON stable et lisible.
     *
     * @param array<string, string> $relativePathById Le mapping final `id => chemin relatif`.
     */
    private function persistManifestIdMap(array $relativePathById): void
    {
        ksort($relativePathById, SORT_STRING);

        $json = json_encode($relativePathById, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($json)) {
            throw new \LogicException('Impossible de sérialiser le manifeste des IDs médias.');
        }

        file_put_contents($this->idManifestPath, $json . PHP_EOL);
    }

    /**
     * Réinitialise les index mémoire après une écriture du manifeste.
     */
    private function resetIndexes(): void
    {
        $this->entriesByType = null;
        $this->relativePathById = null;
        $this->idByRelativePath = null;
    }

    /**
     * Génère un identifiant déterministe de secours à partir du chemin relatif du média.
     *
     * @param string $relativePath Le chemin relatif du média dans le dossier `media`.
     * @param array<string, bool> $usedIds Les IDs déjà attribués durant la génération courante.
     */
    private function generateDeterministicMediaId(string $relativePath, array $usedIds): string
    {
        $hash = hash('sha1', $relativePath);

        for ($length = self::MINIMUM_MEDIA_ID_LENGTH; $length <= strlen($hash); $length += 2) {
            $candidate = substr($hash, 0, $length);

            if (! isset($usedIds[$candidate])) {
                return $candidate;
            }
        }

        throw new \LogicException('Impossible de générer un identifiant média unique pour ' . $relativePath);
    }

    /**
     * Vérifie qu'un identifiant respecte le format attendu.
     */
    private function isValidMediaId(string $id): bool
    {
        return $id !== '' && preg_match('/^[a-z0-9]+$/', $id) === 1;
    }

    /**
     * Normalise un chemin relatif de média issu du manifeste.
     */
    private function normalizeRelativePath(string $relativePath): ?string
    {
        $normalizedPath = trim(str_replace('\\', '/', $relativePath), '/');

        if (
            $normalizedPath === ''
            || str_contains($normalizedPath, '../')
            || str_starts_with($normalizedPath, '../')
            || str_contains($normalizedPath, '/./')
        ) {
            return null;
        }

        return $normalizedPath;
    }

    /**
     * Convertit un chemin absolu en chemin relatif dans le dossier `media`.
     */
    private function relativePathFromAbsolutePath(string $path): ?string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedBaseDirectory = str_replace('\\', '/', $this->baseDirectory);
        $basePrefix = rtrim($normalizedBaseDirectory, '/') . '/';

        if (! str_starts_with($normalizedPath, $basePrefix)) {
            return null;
        }

        return substr($normalizedPath, strlen($basePrefix));
    }
}
