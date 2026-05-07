<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\MediaFile;
use App\DTO\MediaTransformOptions;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use MediaGoneWild\MediaRepository;

/**
 * Centralise la sélection d'un média et la préparation de ses métadonnées HTTP.
 */
final class MediaService
{
    /**
     * Mapping MIME prioritaire par extension pour éviter les détections ambiguës.
     *
     * @var array<string, string>
     */
    private const MIME_TYPE_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'mp4' => 'video/mp4',
    ];

    /**
     * Initialise le service avec un dépôt de médias existant.
     */
    public function __construct(
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    /**
     * Sélectionne un média pour un type donné et prépare ses informations de réponse.
     *
     * @param string $mediaType Le type de média à récupérer.
     * @param string|null $seed Le seed optionnel utilisé pour une sélection déterministe.
     *
     * @return MediaFile|null La description du média choisi, ou null si aucun fichier n'est disponible.
     */
    public function pickMedia(string $mediaType, ?string $seed = null): ?MediaFile
    {
        $normalizedSeed = $this->normalizeSeed($seed);
        $mediaPath = $this->mediaRepository->pick($mediaType, $normalizedSeed);

        return $mediaPath !== null ? $this->createMediaFile($mediaPath) : null;
    }

    /**
     * Récupère un média précis à partir de son identifiant stable.
     *
     * @param string $mediaType Le type de média à inspecter.
     * @param string $id L'identifiant stable du média.
     *
     * @return MediaFile|null Le média demandé, ou null s'il est introuvable.
     */
    public function getMediaById(string $mediaType, string $id): ?MediaFile
    {
        $mediaPath = $this->mediaRepository->findById($mediaType, $id);

        return $mediaPath !== null ? $this->createMediaFile($mediaPath) : null;
    }

    /**
     * Synchronise les IDs manquants pour le catalogue en développement.
     *
     * @return array{synced: bool, added: int, missing: int} Le bilan de synchronisation.
     */
    public function syncIdsForCatalogueIfNeeded(): array
    {
        $missingBefore = $this->mediaRepository->countMissingIds();

        if (! $this->isDevelopmentEnvironment() || $missingBefore === 0) {
            return [
                'synced' => false,
                'added' => 0,
                'missing' => $missingBefore,
            ];
        }

        $result = $this->mediaRepository->syncIdsManifest();

        return [
            'synced' => true,
            'added' => $result['added'],
            'missing' => $this->mediaRepository->countMissingIds(),
        ];
    }

    /**
     * Retourne le nombre de médias encore sans ID stable dans le manifeste.
     */
    public function countMissingIds(): int
    {
        return $this->mediaRepository->countMissingIds();
    }

    /**
     * Lance une synchronisation du manifeste des IDs médias.
     *
     * @param bool $dryRun Si true, simule sans écrire sur disque.
     *
     * @return array{added: int, total: int, changed: bool} Le résultat de la synchronisation.
     */
    public function syncIdsManifest(bool $dryRun = false): array
    {
        return $this->mediaRepository->syncIdsManifest($dryRun);
    }

    /**
     * Liste les médias disponibles pour un type donné.
     *
     * @param string $mediaType Le type de média à récupérer.
     * @param int $limit Le nombre maximum d'éléments à renvoyer.
     *
     * @return list<MediaFile> Les médias disponibles, limités au volume demandé.
     */
    public function listMedia(string $mediaType, int $limit = 24): array
    {
        $entries = $this->mediaRepository->listEntries($mediaType);

        if ($limit > 0) {
            $entries = array_slice($entries, 0, $limit);
        }

        return array_map(
            fn (array $entry): MediaFile => $this->createMediaFile($entry['path']),
            $entries,
        );
    }

    /**
     * Récupère un média précis à partir de son nom de fichier.
     *
     * @param string $mediaType Le type de média à inspecter.
     * @param string $fileName Le nom de fichier demandé.
     *
     * @return MediaFile|null Le média demandé, ou null s'il est introuvable.
     */
    public function getMediaByFileName(string $mediaType, string $fileName): ?MediaFile
    {
        $mediaPath = $this->mediaRepository->findByFileName($mediaType, $fileName);

        return $mediaPath !== null ? $this->createMediaFile($mediaPath) : null;
    }

    /**
     * Normalise le seed reçu depuis la requête en supprimant les chaînes vides.
     */
    private function normalizeSeed(?string $seed): ?string
    {
        if ($seed === null) {
            return null;
        }

        $seed = trim($seed);

        return $seed !== '' ? $seed : null;
    }

    /**
     * Détermine le type MIME d'un média à partir de son extension ou de fileinfo.
     */
    private function resolveMimeType(string $mediaPath): string
    {
        $extension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
        $mimeType = self::MIME_TYPE_BY_EXTENSION[$extension] ?? null;

        if ($mimeType !== null) {
            return $mimeType;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMimeType = $finfo->file($mediaPath);

        return is_string($detectedMimeType) && $detectedMimeType !== ''
            ? $detectedMimeType
            : 'application/octet-stream';
    }

    /**
     * Construit le DTO média à partir d'un chemin fichier.
     */
    private function createMediaFile(string $mediaPath): MediaFile
    {
        $isReadable = is_readable($mediaPath);
        $fileSize = $isReadable ? (filesize($mediaPath) ?: 0) : 0;
        $safeFileName = str_replace('"', '', basename($mediaPath));
        $mediaId = $this->mediaRepository->getIdByPath($mediaPath);

        if ($mediaId === null) {
            throw new \LogicException('Chaque média doit disposer d\'un identifiant stable avant d\'être servi.');
        }

        return new MediaFile(
            $mediaPath,
            $mediaId,
            $safeFileName,
            $this->resolveMimeType($mediaPath),
            $fileSize,
            $isReadable,
        );
    }

    /**
     * Applique les transformations d'image demandées et retourne un nouveau MediaFile pointant vers
     * le fichier résultant, mis en cache dans `writable/cache/transformed/`.
     *
     * Si le fichier transformé existe déjà en cache pour cette combinaison d'ID et d'options,
     * il est renvoyé directement sans traitement.
     *
     * Les transformations ne sont applicables qu'aux images raster.
     * Cette méthode ne doit pas être appelée pour des vidéos ou des SVG.
     *
     * @param MediaFile              $media Le média source à transformer.
     * @param MediaTransformOptions  $opts  Les options de transformation validées.
     *
     * @return MediaFile Le MediaFile pointant vers l'image transformée (depuis le cache ou fraîchement générée).
     */
    public function transformMedia(MediaFile $media, MediaTransformOptions $opts): MediaFile
    {
        $sourceExt = strtolower(pathinfo($media->getPath(), PATHINFO_EXTENSION));
        $outputExt = $opts->getNormalizedExtension() ?? ($sourceExt === 'jpeg' ? 'jpg' : $sourceExt);

        $cacheKey  = $opts->toCacheKey($media->getId());
        $cacheDir  = $this->resolveTransformedCacheDirectory();
        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.' . $outputExt;

        // -- Cache hit : on sert directement le fichier existant --
        if (is_readable($cachePath)) {
            return $this->createTransformedMediaFile($cachePath, $media->getFileName(), $outputExt);
        }

        // -- Prépare le répertoire de cache si nécessaire --
        if (! is_dir($cacheDir)) {
            if (! mkdir($cacheDir, 0755, true) && ! is_dir($cacheDir)) {
                throw new \RuntimeException('Impossible de créer le répertoire de cache des images transformées.');
            }
        }

        if (! is_writable($cacheDir)) {
            throw new \RuntimeException('Le répertoire de cache des images transformées n\'est pas inscriptible.');
        }

        // -- Chargement de l'image source via Intervention Image v4 --
        $manager = new ImageManager(new GdDriver());
        $image   = $manager->decodePath($media->getPath());

        $width   = $opts->getWidth();
        $height  = $opts->getHeight();
        $bgcolor = $opts->getBgcolorForIntervention();
        $fit     = $opts->getFit();

        // -- Redimensionnement selon le mode de fit --
        if ($width !== null || $height !== null) {
            $fit = $fit ?? 'contain';

            if ($width !== null && $height !== null) {
                // Les deux dimensions sont fournies : on applique le mode de fit demandé.
                match ($fit) {
                    'cover'         => $image->cover($width, $height),
                    'fill'          => $image->resize($width, $height),
                    'scale'         => $image->scale($width, $height),
                    default         => $image->contain($width, $height, $bgcolor),
                };
            } else {
                // Une seule dimension fournie : redimensionnement proportionnel.
                $image->scale($width, $height);
            }
        }

        // -- Application de la couleur de fond sur les zones transparentes --
        // Pour `contain`, le bgcolor est déjà appliqué par le modifier.
        // Pour les autres modes, on l'applique ici sur les éventuelles zones transparentes.
        if ($bgcolor !== null && $fit !== 'contain') {
            $image->fillTransparentAreas($bgcolor);
        }

        // -- Encodage vers le format de sortie avec la qualité souhaitée --
        $quality = $opts->getQuality();
        $encoded = match ($outputExt) {
            'jpg'  => $image->encode(new JpegEncoder($quality)),
            'webp' => $image->encode(new WebpEncoder($quality)),
            'gif'  => $image->encode(new GifEncoder()),
            'png'  => $image->encode(new PngEncoder()),
            default => $image->encode(new JpegEncoder($quality)),
        };

        if (file_put_contents($cachePath, (string) $encoded) === false) {
            throw new \RuntimeException('Impossible d\'écrire l\'image transformée dans le cache.');
        }

        return $this->createTransformedMediaFile($cachePath, $media->getFileName(), $outputExt);
    }

    /**
     * Construit un MediaFile pointant vers une image transformée en cache.
     *
     * @param string $cachePath  Chemin absolu du fichier transformé.
     * @param string $sourceFileName Nom de fichier original du média source.
     * @param string $outputExt  Extension du fichier de sortie.
     */
    private function createTransformedMediaFile(
        string $cachePath,
        string $sourceFileName,
        string $outputExt,
    ): MediaFile {
        $isReadable = is_readable($cachePath);
        $fileSize   = $isReadable ? (filesize($cachePath) ?: 0) : 0;

        // Construit un nom de fichier propre avec la bonne extension de sortie.
        $baseName      = pathinfo($sourceFileName, PATHINFO_FILENAME);
        $safeFileName  = str_replace('"', '', $baseName) . '.' . $outputExt;
        $mimeType      = $this->resolveMimeType($cachePath);

        // Réutilise l'ID du média source depuis le chemin du cache (non disponible ici).
        // Le MediaFile transformé n'a pas besoin d'un ID stable — on utilise la clé de cache.
        $cacheId = pathinfo($cachePath, PATHINFO_FILENAME);

        return new MediaFile(
            $cachePath,
            $cacheId,
            $safeFileName,
            $mimeType,
            $fileSize,
            $isReadable,
        );
    }

    /**
     * Retourne le répertoire absolu utilisé pour le cache des images transformées.
     *
     * En application complète, `WRITEPATH` est utilisé. En test unitaire isolé,
     * un fallback vers `ROOTPATH/writable` puis vers le `writable` du projet est prévu.
     */
    private function resolveTransformedCacheDirectory(): string
    {
        if (defined('WRITEPATH')) {
            return rtrim((string) WRITEPATH, DIRECTORY_SEPARATOR . '/\\')
                . DIRECTORY_SEPARATOR
                . 'cache'
                . DIRECTORY_SEPARATOR
                . 'transformed';
        }

        if (defined('ROOTPATH')) {
            return rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR . '/\\')
                . DIRECTORY_SEPARATOR
                . 'writable'
                . DIRECTORY_SEPARATOR
                . 'cache'
                . DIRECTORY_SEPARATOR
                . 'transformed';
        }

        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'writable'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'transformed';
    }

    /**
     * Indique si l'application tourne en environnement développement.
     */
    private function isDevelopmentEnvironment(): bool
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
    }
}

