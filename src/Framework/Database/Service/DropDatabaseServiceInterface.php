<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\DeveloperTools\Framework\Database\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseConnectionException;

interface DropDatabaseServiceInterface
{
    /**
     * @throws DatabaseConnectionException
     */
    public function dropDatabase(DatabaseConfiguration $databaseConfiguration): void;
}
