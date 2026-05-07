<?php

declare(strict_types=1);

namespace MediaGoneWild\Tests\Unit;

use MediaGoneWild\Router;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la résolution des endpoints autorisés par le routeur.
 *
 * Ces tests sécurisent le contrat public le plus simple de l'API :
 * un chemin valide doit renvoyer exactement le type de média attendu,
 * tandis qu'un chemin inconnu doit être rejeté proprement.
 */
final class RouterTest extends TestCase
{
    /**
     * Vérifie qu'un endpoint valide est correctement résolu.
     *
     * Le routeur doit accepter des variantes courantes comme la présence
     * ou l'absence de slash en début ou en fin de chemin.
     */
    public function testResolveMediaTypeReturnsExpectedTypeForValidPath(): void
    {
        $router = new Router();

        self::assertSame('photo', $router->resolveMediaType('/photo'));
        self::assertSame('video', $router->resolveMediaType('video'));
        self::assertSame('logo', $router->resolveMediaType('/logo/'));
    }

    /**
     * Vérifie qu'un endpoint invalide renvoie null.
     *
     * Cela évite qu'un chemin partiel ou arbitraire soit interprété
     * comme un endpoint légitime par erreur.
     */
    public function testResolveMediaTypeReturnsNullForInvalidPath(): void
    {
        $router = new Router();

        self::assertNull($router->resolveMediaType('/'));
        self::assertNull($router->resolveMediaType('/unknown'));
        self::assertNull($router->resolveMediaType('/photo/test'));
    }
}


