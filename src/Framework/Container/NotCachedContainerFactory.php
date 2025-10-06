<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Container;
use Psr\Container\ContainerInterface;

/**
 * Set environment variable OXID_CONTAINER_PROVIDER=OxidEsales\DeveloperTools\Framework\Container\NotCachedContainerFactory to activate it.
 */
class NotCachedContainerFactory implements ContainerProviderInterface
{
    private static $symfonyContainer;

    public static function get(): ContainerInterface
    {
        if (self::$symfonyContainer === null) {
            $containerBuilder = (new ContainerBuilderFactory())->create();
            self::$symfonyContainer = $containerBuilder->getContainer();
            self::$symfonyContainer->compile(true);
        }

        return self::$symfonyContainer;
    }

    public static function resetContainer()
    {
        self::$symfonyContainer = null;
    }
}
