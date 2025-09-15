<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Unit\Framework\Database\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use OxidEsales\DeveloperTools\Framework\Database\Command\ResetDatabaseCommand;
use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\InvalidDatabaseConfigurationException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\DatabaseNotEmptyException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\ShopDbManagerInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use OxidEsales\EshopCommunity\Tests\Unit\Internal\BasicContextStub;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ResetDatabaseCommandTest extends TestCase
{
    use ProphecyTrait;

    private BasicContextInterface $basicContext;
    private SetupDbConnectionValidatorInterface | ObjectProphecy $setupDbConnectionValidator;
    private SetupDbValidatorInterface | ObjectProphecy $setupDbValidator;
    private ShopDbManagerInterface | ObjectProphecy $shopDbManager;
    private DropDatabaseServiceInterface | ObjectProphecy $dropDatabaseService;
    private DatabaseConfiguration | TypeToken $dbConfigStub;
    private ObjectProphecy | SetupDbConnectionFactoryInterface $databaseConnectionFactory;

    public function testExecuteWithInvalidDbConfig(): void
    {
        $commandTester = new CommandTester($this->createCommand());
        $this->basicContext->setDatabaseUrl('');

        try {
            $commandTester->execute([]);
        } catch (InvalidDatabaseConfigurationException) {
            $this->dropDatabaseService->dropDatabase($this->dbConfigStub)->shouldNotHaveBeenCalled();
        }
    }

    public function testExecuteWithNonExistingDb(): void
    {
        $commandTester = new CommandTester($this->createCommand());
        $commandTester->setInputs(['no']);

        $this->databaseConnectionFactory
            ->getDatabaseConnection(new TypeToken(DatabaseConfiguration::class))
            ->willThrow(ConnectionException::class);

        $exitCode = $commandTester->execute([]);

        $this->dropDatabaseService->dropDatabase($this->dbConfigStub)->shouldNotHaveBeenCalled();
        $this->shopDbManager->create($this->dbConfigStub)->shouldHaveBeenCalledOnce();
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithExistingDbAndAnswerNo(): void
    {
        $commandTester = new CommandTester($this->createCommand());
        $commandTester->setInputs(['no']);

        $this->setupDbConnectionValidator
            ->validate(new TypeToken(DatabaseConfiguration::class))
            ->willThrow(DatabaseNotEmptyException::class);

        $exitCode = $commandTester->execute([]);

        $this->dropDatabaseService->dropDatabase($this->dbConfigStub)->shouldNotHaveBeenCalled();
        $this->shopDbManager->create($this->dbConfigStub)->shouldNotHaveBeenCalled();
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithExistingDbAndAnswerYes(): void
    {
        $commandTester = new CommandTester($this->createCommand());
        $commandTester->setInputs(['yes']);

        $this->setupDbConnectionValidator
            ->validate(new TypeToken(DatabaseConfiguration::class))
            ->willThrow(DatabaseNotEmptyException::class);

        $exitCode = $commandTester->execute([]);

        $this->dropDatabaseService->dropDatabase($this->dbConfigStub)->shouldHaveBeenCalledOnce();
        $this->shopDbManager->create($this->dbConfigStub)->shouldHaveBeenCalledOnce();
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithExistingDbAndOptionForce(): void
    {
        $commandTester = new CommandTester($this->createCommand());

        $this->setupDbConnectionValidator
            ->validate(new TypeToken(DatabaseConfiguration::class))
            ->willThrow(DatabaseNotEmptyException::class);

        $exitCode = $commandTester->execute(['--force' => true]);

        $this->dropDatabaseService->dropDatabase($this->dbConfigStub)->shouldHaveBeenCalledOnce();
        $this->shopDbManager->create($this->dbConfigStub)->shouldHaveBeenCalledOnce();
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    private function createCommand(): Command
    {
        $this->prepareMocks();

        $command = new ResetDatabaseCommand(
            $this->basicContext,
            $this->setupDbConnectionValidator->reveal(),
            $this->setupDbValidator->reveal(),
            $this->shopDbManager->reveal(),
            $this->dropDatabaseService->reveal(),
            $this->databaseConnectionFactory->reveal()
        );
        $command->setName('oe:database:reset');
        $application = new Application();
        $application->add($command);

        return $application->find('oe:database:reset');
    }

    private function prepareMocks(): void
    {
        $this->basicContext = new BasicContextStub();
        $this->setupDbConnectionValidator = $this->prophesize(SetupDbConnectionValidatorInterface::class);
        $this->setupDbValidator = $this->prophesize(SetupDbValidatorInterface::class);
        $this->shopDbManager = $this->prophesize(ShopDbManagerInterface::class);
        $this->dropDatabaseService = $this->prophesize(DropDatabaseServiceInterface::class);
        $this->dbConfigStub = new TypeToken(DatabaseConfiguration::class);

        $connection = $this->prophesize(Connection::class);

        $databaseConnectionFactory = $this->prophesize(SetupDbConnectionFactoryInterface::class);
        $databaseConnectionFactory
            ->getDatabaseConnection(new TypeToken(DatabaseConfiguration::class))
            ->willReturn($connection->reveal());

        $this->databaseConnectionFactory = $databaseConnectionFactory;
    }
}
