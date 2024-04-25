<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Database\Service;

use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseConnectionException;
use PDO;
use Throwable;

class DropDatabaseService implements DropDatabaseServiceInterface
{
    /**
     * @inheritDoc
     */
    public function dropDatabase(string $host, int $port, string $username, string $password, string $name): void
    {
        $this->getDatabaseConnection($host, $port, $username, $password)
            ->exec('DROP DATABASE ' . $name . ';');
    }

    private function getDatabaseConnection(string $host, int $port, string $username, string $password): PDO
    {
        try {
            $dbConnection = new PDO(
                sprintf('mysql:host=%s;port=%s', $host, $port),
                $username,
                $password,
                [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
            );
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            throw new DatabaseConnectionException(
                'Failed: Unable to connect to database',
                $exception->getCode(),
                $exception
            );
        }
        return $dbConnection;
    }
}
