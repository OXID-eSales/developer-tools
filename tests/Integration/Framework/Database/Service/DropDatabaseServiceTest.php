<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Database\Service;

use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseService;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCreator;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseConnectionException;
use OxidEsales\Facts\Config\ConfigFile;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

class DropDatabaseServiceTest extends TestCase
{
    /** @var array */
    private $params = [];

    public function setUp(): void
    {
        $this->params = $this->getDatabaseConnectionInfo();
        $this->params['dbName'] = 'oxid_drop_db_test';

        parent::setUp();
    }

    public function testDropDatabase(): void
    {
        (new DatabaseCreator())->createDatabase(
            $this->params['dbHost'],
            $this->params['dbPort'],
            $this->params['dbUser'],
            $this->params['dbPwd'],
            $this->params['dbName']
        );
        $this->assertNotFalse($this->checkDatabaseConnection());
        (new DropDatabaseService())->dropDatabase(
            $this->params['dbHost'],
            $this->params['dbPort'],
            $this->params['dbUser'],
            $this->params['dbPwd'],
            $this->params['dbName']
        );
        $this->assertFalse($this->checkDatabaseConnection());
    }

    public function testDropDatabaseWithIncorrectCredentials(): void
    {
        $this->expectException(DatabaseConnectionException::class);
        (new DropDatabaseService())->dropDatabase(
            $this->params['dbHost'],
            $this->params['dbPort'],
            '',
            '',
            $this->params['dbName']
        );
    }

    public function testDropNotExistingDatabase(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage("Can't drop database 'oxid_drop_db_test'; database doesn't exist");
        (new DropDatabaseService())->dropDatabase(
            $this->params['dbHost'],
            $this->params['dbPort'],
            $this->params['dbUser'],
            $this->params['dbPwd'],
            $this->params['dbName']
        );
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            $dbConnection = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s', $this->params['dbHost'], $this->params['dbPort'], $this->params['dbName']),
                $this->params['dbUser'],
                $this->params['dbPwd'],
                [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
            );
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            return false;
        }
        return true;
    }

    private function getDatabaseConnectionInfo(): array
    {
        $configFile = new ConfigFile();

        return [
            'dbHost' => $configFile->getVar('dbHost'),
            'dbPort' => (int) $configFile->getVar('dbPort'),
            'dbUser' => $configFile->getVar('dbUser'),
            'dbPwd'  => $configFile->getVar('dbPwd')
        ];
    }
}
