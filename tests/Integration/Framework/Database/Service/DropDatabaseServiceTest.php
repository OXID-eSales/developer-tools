<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Database\Service;

use Doctrine\DBAL\Exception\ConnectionException;
use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\DatabaseTrait;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

final class DropDatabaseServiceTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTrait;

    public function tearDown(): void
    {
        parent::tearDown();
        $this->setupShopDatabase();
    }

    #[DoesNotPerformAssertions]
    public function testDropDatabase(): void
    {
        $databaseConfiguration = new DatabaseConfiguration(getenv('OXID_DB_URL'));
        $this->get(SetupDbConnectionFactoryInterface::class)->getDatabaseConnection($databaseConfiguration);

        $this->get(DropDatabaseServiceInterface::class)->dropDatabase($databaseConfiguration);

        try {
            $this->get(SetupDbConnectionFactoryInterface::class)->getDatabaseConnection($databaseConfiguration);
        } catch (ConnectionException) {
            return;
        }
        $this->fail('Database was not removed');
    }
}
