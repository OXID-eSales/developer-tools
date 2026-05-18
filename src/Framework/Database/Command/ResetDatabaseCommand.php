<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Database\Command;

use OxidEsales\DatabaseViewsGenerator\ViewsGenerator;
use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Console\Command\NamedArgumentsTrait;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseExistsAndNotEmptyException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseExistsException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseConnectionException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\InitiateDatabaseException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCheckerInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCreatorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseInitiatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetDatabaseCommand extends Command
{
    use NamedArgumentsTrait;

    private const DB_HOST = 'db-host';
    private const DB_PORT = 'db-port';
    private const DB_NAME = 'db-name';
    private const DB_USER = 'db-user';
    private const DB_PASSWORD = 'db-password';
    private const FORCE_RESET = 'force';

    /**
     * @var DatabaseCheckerInterface
     */
    private DatabaseCheckerInterface $databaseChecker;

    /**
     * @var DatabaseCreatorInterface
     */
    private DatabaseCreatorInterface $databaseCreator;

    /**
     * @var DatabaseInitiatorInterface
     */
    private DatabaseInitiatorInterface $databaseInitiator;

    /**
     * @var DropDatabaseServiceInterface
     */
    private DropDatabaseServiceInterface $dropDatabaseService;

    public function __construct(
        DatabaseCheckerInterface $databaseChecker,
        DatabaseCreatorInterface $databaseCreator,
        DatabaseInitiatorInterface $databaseInitiator,
        DropDatabaseServiceInterface $dropDatabaseService
    ) {
        parent::__construct();

        $this->databaseChecker = $databaseChecker;
        $this->databaseCreator = $databaseCreator;
        $this->databaseInitiator = $databaseInitiator;
        $this->dropDatabaseService = $dropDatabaseService;
    }

    protected function configure()
    {
        $this
            ->addOption(self::DB_HOST, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_PORT, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_NAME, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_USER, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_PASSWORD, null, InputOption::VALUE_REQUIRED)
            ->addOption(
                self::FORCE_RESET,
                null,
                InputOption::VALUE_NONE,
                "Don't ask for the deletion of the database, but force the operation to run."
            );
        $this->setDescription(
            'Performs database reset. <error>ATTENTION: This operation should not be executed'
            . ' in a production environment.</error>'
        );

        $this->setRequiredOptions([
            self::DB_HOST,
            self::DB_PORT,
            self::DB_NAME,
            self::DB_USER,
            self::DB_PASSWORD,
        ]);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseExistsException
     * @throws InitiateDatabaseException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkRequiredCommandOptions($this->getDefinition()->getOptions(), $input);

        $io = new SymfonyStyle($input, $output);
        $io->info('Resetting database...');
        $start = microtime(true);

        if ($this->databaseExist($input)) {
            if (!$this->forceDatabaseReset($input) && !$this->confirmAction($input, $io)) {
                $io->note('Reset has been canceled.');
                return Command::SUCCESS;
            }
            $io->info('Dropping existing database...');
            $this->dropDatabase($input);
        }
        try {
            $io->info('Creating database...');
            $this->createDatabase($input);
        } catch (DatabaseExistsException $exception) {
        }

        $io->info('Initializing database...');
        $this->initializeDatabase($input);

        $io->success('Reset has been finished in ' . (microtime(true) - $start));

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param SymfonyStyle   $io
     *
     * @return bool
     */
    private function confirmAction(InputInterface $input, SymfonyStyle $io): bool
    {
        return $io->confirm(
            sprintf(
                'Seems there is already OXID eShop installed in database %s.'
                . ' All data in a given database will be lost when executing this command. Continue?',
                $input->getOption(self::DB_NAME)
            ),
            false
        );
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    private function forceDatabaseReset(InputInterface $input): bool
    {
        $value = $input->getOption(self::FORCE_RESET);
        return isset($value) && $value;
    }

    /**
     * @param InputInterface $input
     *
     * @throws DatabaseConnectionException
     */
    private function dropDatabase(InputInterface $input): void
    {
        $this->dropDatabaseService->dropDatabase(
            $input->getOption(self::DB_HOST),
            (int)$input->getOption(self::DB_PORT),
            $input->getOption(self::DB_USER),
            $input->getOption(self::DB_PASSWORD),
            $input->getOption(self::DB_NAME)
        );
    }

    /**
     * @param InputInterface $input
     *
     * @throws DatabaseExistsException
     * @throws DatabaseConnectionException
     */
    private function createDatabase(InputInterface $input): void
    {
        $this->databaseCreator->createDatabase(
            $input->getOption(self::DB_HOST),
            (int)$input->getOption(self::DB_PORT),
            $input->getOption(self::DB_USER),
            $input->getOption(self::DB_PASSWORD),
            $input->getOption(self::DB_NAME)
        );
    }

    /**
     * @param InputInterface $input
     *
     * @throws InitiateDatabaseException
     */
    private function initializeDatabase(InputInterface $input): void
    {
        $this->databaseInitiator->initiateDatabase(
            $input->getOption(self::DB_HOST),
            (int)$input->getOption(self::DB_PORT),
            $input->getOption(self::DB_USER),
            $input->getOption(self::DB_PASSWORD),
            $input->getOption(self::DB_NAME)
        );
        $this->generateViews();
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    private function databaseExist(InputInterface $input): bool
    {
        try {
            $this->databaseChecker->canCreateDatabase(
                $input->getOption(self::DB_HOST),
                (int)$input->getOption(self::DB_PORT),
                $input->getOption(self::DB_USER),
                $input->getOption(self::DB_PASSWORD),
                $input->getOption(self::DB_NAME)
            );
        } catch (DatabaseExistsAndNotEmptyException $exception) {
            return true;
        }
        return false;
    }

    private function generateViews(): void
    {
        (new ViewsGenerator())->generate();
    }
}
