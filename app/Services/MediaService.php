<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\MediaFile;
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
     * Liste les médias disponibles pour un type donné.
     *
     * @param string $mediaType Le type de média à récupérer.
     * @param int $limit Le nombre maximum d'éléments à renvoyer.
     *
     * @return list<MediaFile> Les médias disponibles, limités au volume demandé.
     */
    public function listMedia(string $mediaType, int $limit = 24): array
    {
        $paths = $this->mediaRepository->list($mediaType);

        if ($limit > 0) {
            $paths = array_slice($paths, 0, $limit);
        }

        return array_map(
            fn (string $path): MediaFile => $this->createMediaFile($path),
            $paths,
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

        return new MediaFile(
            $mediaPath,
            $safeFileName,
            $this->resolveMimeType($mediaPath),
            $fileSize,
            $isReadable,
        );
    }
}

