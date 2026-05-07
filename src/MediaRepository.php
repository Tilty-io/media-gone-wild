<?php

declare(strict_types=1);

namespace MediaGoneWild;

/**
 * Recherche et sélectionne un média dans l'arborescence locale.
 */
final class MediaRepository
{
    /**
     * Répertoire racine qui contient les sous-dossiers de médias.
     */
    private string $baseDirectory;

    /**
     * Initialise le dépôt avec son répertoire racine.
     *
     * @param string $baseDirectory Le chemin du dossier qui contient `photo`, `video` et `logo`.
     */
    public function __construct(string $baseDirectory)
    {
        $this->baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR);
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
        $directory = $this->baseDirectory . DIRECTORY_SEPARATOR . $mediaType;

        // Arrête immédiatement si le dossier attendu n'existe pas.
        if (!is_dir($directory)) {
            return [];
        }

        // Ne conserve que les fichiers réels pour éviter de retourner un sous-dossier.
        $files = glob($directory . DIRECTORY_SEPARATOR . '*') ?: [];
        $files = array_values(array_filter($files, 'is_file'));
        sort($files, SORT_STRING);

        return $files;
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

        foreach ($this->list($mediaType) as $filePath) {
            if (basename($filePath) === $normalizedFileName) {
                return $filePath;
            }
        }

        return null;
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
}
