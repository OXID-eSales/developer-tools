<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Database\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionFactoryInterface;

readonly class DropDatabaseService implements DropDatabaseServiceInterface
{
    public function __construct(
        private SetupDbConnectionFactoryInterface $setupDbConnectionFactory,
    ) {
    }

    public function dropDatabase(DatabaseConfiguration $databaseConfiguration): void
    {
        $connection = $this->setupDbConnectionFactory->getServerConnection($databaseConfiguration);
        $connection->executeStatement(
            sprintf('DROP DATABASE `%s`;', $databaseConfiguration->getName())
        );
        $connection->close();
    }
}
