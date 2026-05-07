<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Représente un média sélectionné et prêt à être renvoyé dans une réponse HTTP.
 */
final readonly class MediaFile
{
    /**
     * Construit la description complète d'un média résolu par l'application.
     *
     * @param string $path Le chemin absolu du fichier sur le disque.
     * @param string $fileName Le nom de fichier sûr à exposer au navigateur.
     * @param string $mimeType Le type MIME calculé pour la réponse HTTP.
     * @param int $fileSize La taille du fichier en octets.
     * @param bool $readable Indique si le fichier est lisible par PHP.
     */
    public function __construct(
        private string $path,
        private string $fileName,
        private string $mimeType,
        private int $fileSize,
        private bool $readable,
    ) {
    }

    /**
     * Retourne le chemin absolu du fichier média.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retourne le nom de fichier à exposer dans l'en-tête Content-Disposition.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Retourne le type MIME calculé pour ce média.
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Retourne la taille du média en octets.
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * Indique si le média peut être lu par PHP.
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }
}

