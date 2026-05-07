<?php

declare(strict_types=1);

namespace MediaGoneWild\Tests\Unit;

use MediaGoneWild\Router;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la résolution des endpoints autorisés par le routeur.
 */
final class RouterTest extends TestCase
{
    /**
     * Vérifie qu'un endpoint valide est correctement résolu.
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
     */
    public function testResolveMediaTypeReturnsNullForInvalidPath(): void
    {
        $router = new Router();

        self::assertNull($router->resolveMediaType('/'));
        self::assertNull($router->resolveMediaType('/unknown'));
        self::assertNull($router->resolveMediaType('/photo/test'));
    }
}

