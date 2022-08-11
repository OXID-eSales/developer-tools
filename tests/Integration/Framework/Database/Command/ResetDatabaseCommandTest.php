<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Database\Command;

use InvalidArgumentException;
use OxidEsales\DeveloperTools\Framework\Database\Command\ResetDatabaseCommand;
use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseServiceInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseExistsAndNotEmptyException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseExistsException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCheckerInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCreatorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseInitiatorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ResetDatabaseCommandTest extends TestCase
{
    use ProphecyTrait;

    private const HOST = 'some-host';
    private const PORT = 123;
    private const DB = 'some-db';
    private const DB_USER = 'some-db-user';
    private const DB_PASS = 'some-db-pass';

    private $arguments = [
        '--db-host' => self::HOST,
        '--db-port' => self::PORT,
        '--db-name' => self::DB,
        '--db-user' => self::DB_USER,
        '--db-password' => self::DB_PASS
    ];

    public function testExecuteWithMissingArgs(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $databaseSetupCommand = new ResetDatabaseCommand(
            $this->getDatabaseCheckerMock(),
            $this->getDatabaseCreatorMock(),
            $this->getDatabaseInstallerMock(),
            $this->getDropDatabaseServiceMock()
        );
        $commandTester = new CommandTester($databaseSetupCommand);
        $commandTester->execute([]);
    }

    public function testExecuteWithExistingDatabaseAndWithForceParameter(): void
    {
        $databaseSetupCommand = new ResetDatabaseCommand(
            $this->getDatabaseCheckerWithExceptionMock(),
            $this->getDatabaseCreatorMock(),
            $this->getDatabaseInstallerMock(),
            $this->getDropDatabaseServiceMock()
        );
        $commandTester = new CommandTester($databaseSetupCommand);

        $arguments = $this->arguments;
        $arguments['--force'] = true;
        $exitCode = $commandTester->execute($arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Reset has been finished.', $commandTester->getDisplay());
    }

    public function testExecuteOnEmptyDatabase(): void
    {
        $databaseSetupCommand = new ResetDatabaseCommand(
            $this->getDatabaseCheckerMock(),
            $this->getDatabaseCreatorMock(),
            $this->getDatabaseInstallerMock(),
            $this->getDropDatabaseServiceMock()
        );
        $commandTester = new CommandTester($databaseSetupCommand);

        $exitCode = $commandTester->execute($this->arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Reset has been finished.', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingDatabaseAndConfirmedAction(): void
    {
        $commandTester = new CommandTester($this->getCommandWithInteraction());
        $commandTester->setInputs(['yes']);

        $exitCode = $commandTester->execute($this->arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Reset has been finished', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingDatabaseAndRejectedAction(): void
    {
        $commandTester = new CommandTester($this->getCommandWithInteraction());
        $commandTester->setInputs(['no']);
        $exitCode = $commandTester->execute($this->arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Reset has been canceled.', $commandTester->getDisplay());
    }

    public function testExecuteWithEmptyExistingDatabase(): void
    {
        $databaseSetupCommand = new ResetDatabaseCommand(
            $this->getDatabaseCheckerMock(),
            $this->getDatabaseCreatorWithExceptionMock(),
            $this->getDatabaseInstallerMock(),
            $this->getDropDatabaseServiceMock()
        );
        $commandTester = new CommandTester($databaseSetupCommand);
        $exitCode = $commandTester->execute($this->arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Reset has been finished.', $commandTester->getDisplay());
    }

    private function getCommandWithInteraction(): Command
    {
        $databaseSetupCommand = new ResetDatabaseCommand(
            $this->getDatabaseCheckerWithExceptionMock(),
            $this->getDatabaseCreatorMock(),
            $this->getDatabaseInstallerMock(),
            $this->getDropDatabaseServiceMock()
        );
        $databaseSetupCommand->setName('oe:database:reset');

        $application = new Application();
        $application->add($databaseSetupCommand);
        return $application->find('oe:database:reset');
    }

    private function getDatabaseCheckerWithExceptionMock(): DatabaseCheckerInterface
    {
        $databaseChecker = $this->prophesize(DatabaseCheckerInterface::class);
        $databaseChecker->canCreateDatabase(
            self::HOST,
            self::PORT,
            self::DB_USER,
            self::DB_PASS,
            self::DB
        )
            ->willThrow(DatabaseExistsAndNotEmptyException::class);
        return $databaseChecker->reveal();
    }

    private function getDatabaseCheckerMock(): DatabaseCheckerInterface
    {
        return $this->prophesize(DatabaseCheckerInterface::class)->reveal();
    }

    private function getDatabaseCreatorMock(): DatabaseCreatorInterface
    {
        return $this->prophesize(DatabaseCreatorInterface::class)->reveal();
    }

    private function getDatabaseCreatorWithExceptionMock(): DatabaseCreatorInterface
    {
        $databaseCreator = $this->prophesize(DatabaseCreatorInterface::class);
        $databaseCreator->createDatabase(
            self::HOST,
            self::PORT,
            self::DB_USER,
            self::DB_PASS,
            self::DB
        )
            ->willThrow(DatabaseExistsException::class);
        return $databaseCreator->reveal();
    }

    private function getDatabaseInstallerMock(): DatabaseInitiatorInterface
    {
        return $this->prophesize(DatabaseInitiatorInterface::class)->reveal();
    }

    private function getDropDatabaseServiceMock(): DropDatabaseServiceInterface
    {
        return $this->prophesize(DropDatabaseServiceInterface::class)->reveal();
    }
}
