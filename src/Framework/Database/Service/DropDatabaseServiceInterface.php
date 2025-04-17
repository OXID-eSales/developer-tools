<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\DeveloperTools\Framework\Database\Service;

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;

interface DropDatabaseServiceInterface
{
    /**
     * @throws DatabaseConnectionException
     */
    public function dropDatabase(DatabaseConfiguration $databaseConfiguration): void;
}
