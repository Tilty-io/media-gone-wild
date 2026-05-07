<?php

declare(strict_types=1);

namespace MediaGoneWild;

final class Router
{
    private const ALLOWED_MEDIA_TYPES = ['photo', 'video', 'logo'];

    public function resolveMediaType(string $uriPath): ?string
    {
        $path = trim($uriPath, '/');

        if ($path === '') {
            return null;
        }

        if (!in_array($path, self::ALLOWED_MEDIA_TYPES, true)) {
            return null;
        }

        return $path;
    }
}

