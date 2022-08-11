<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Database\Service;

use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseConnectionException;

/**
 * Class DropDatabaseServiceInterface
 *
 * @package OxidEsales\DeveloperTools\Framework\Database\Service
 */
interface DropDatabaseServiceInterface
{
    /**
     * @param string $host
     * @param int    $port
     * @param string $username
     * @param string $password
     * @param string $name
     *
     * @throws DatabaseConnectionException
     */
    public function dropDatabase(string $host, int $port, string $username, string $password, string $name): void;
}
