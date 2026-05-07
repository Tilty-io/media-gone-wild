<?php

declare(strict_types=1);

namespace MediaGoneWild;

/**
 * Résout le type de média demandé à partir du chemin d'URL reçu.
 */
final class Router
{
    /**
     * Liste des endpoints publics autorisés par l'API.
     *
     * @var list<string>
     */
    private const ALLOWED_MEDIA_TYPES = ['photo', 'video', 'logo'];

    /**
     * Extrait le type de média à servir depuis le chemin demandé.
     *
     * @param string $uriPath Le chemin d'URL normalisé ou brut reçu par l'application.
     *
     * @return string|null Le type de média reconnu, ou null si aucun endpoint ne correspond.
     */
    public function resolveMediaType(string $uriPath): ?string
    {
        // Supprime les slashs parasites afin de comparer uniquement le segment utile.
        $path = trim($uriPath, '/');

        if ($path === '') {
            return null;
        }

        // Refuse tout chemin qui ne correspond pas exactement à un endpoint prévu.
        if (!in_array($path, self::ALLOWED_MEDIA_TYPES, true)) {
            return null;
        }

        return $path;
    }
}

