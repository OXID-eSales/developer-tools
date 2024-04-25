<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Database\Command;

use OxidEsales\DatabaseViewsGenerator\ViewsGenerator;
use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseConnectionException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseExistsAndNotEmptyException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\DatabaseExistsException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Exception\InitiateDatabaseException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCheckerInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseCreatorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\Service\DatabaseInitiatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ResetDatabaseCommand extends Command
{
    private const FORCE_RESET = 'force';
    private DatabaseConfiguration $dbConfig;

    public function __construct(
        private readonly DatabaseCheckerInterface $databaseChecker,
        private readonly DatabaseCreatorInterface $databaseCreator,
        private readonly DatabaseInitiatorInterface $databaseInitiator,
        private readonly DropDatabaseServiceInterface $dropDatabaseService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::FORCE_RESET,
                null,
                InputOption::VALUE_NONE,
                "Don't ask for the deletion of the database, but force the operation to run."
            );
        $this->setDescription(
            'Performs database reset. '
            . '<error>ATTENTION: This operation should not be executed in a production environment.</error>'
        );
    }

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseExistsException
     * @throws InitiateDatabaseException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty(getenv('OXID_DB_URL'))) {
            $output->writeln('<error>Configuration error!</error>');
            $output->writeln('<comment>Please check your DB connection configuration and try again.</comment>');
            return Command::FAILURE;
        }
        $this->dbConfig = new DatabaseConfiguration((string)getenv('OXID_DB_URL'));
        $output->writeln('<info>Resetting database...</info>');
        $start = microtime(true);

        if ($this->databaseExist()) {
            if (!$this->databaseResetWasForced($input) && !$this->actionWasConfirmed($input, $output)) {
                $output->writeln('<info>Reset has been canceled.</info>');
                return Command::SUCCESS;
            }
            $output->writeln('<info>Dropping existing database...</info>');
            $this->dropDatabase();
        }
        try {
            $output->writeln('<info>Creating database...</info>');
            $this->createDatabase();
        } catch (DatabaseExistsException) {
        }

        $output->writeln('<info>Initializing database...</info>');
        $this->initializeDatabase();

        $output->writeln('<info>Reset has been finished in ' . (microtime(true) - $start) . '</info>');

        return Command::SUCCESS;
    }

    private function actionWasConfirmed(InputInterface $input, OutputInterface $output): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    '<question>Seems there is already OXID eShop installed in database '
                    . "`{$this->dbConfig->getName()}`. "
                    . 'All data in a given database will be lost when executing this command! '
                    . 'Continue executing it? [No/yes]: </question>',
                    false
                )
            );
    }

    private function databaseResetWasForced(InputInterface $input): bool
    {
        return $input->getOption(self::FORCE_RESET);
    }

    /**
     * @throws DatabaseConnectionException
     */
    private function dropDatabase(): void
    {
        $this->dropDatabaseService->dropDatabase(
            $this->dbConfig->getHost(),
            $this->dbConfig->getPort(),
            $this->dbConfig->getUser(),
            $this->dbConfig->getPass(),
            $this->dbConfig->getName(),
        );
    }

    /**
     * @throws DatabaseExistsException
     * @throws DatabaseConnectionException
     */
    private function createDatabase(): void
    {
        $this->databaseCreator->createDatabase(
            $this->dbConfig->getHost(),
            $this->dbConfig->getPort(),
            $this->dbConfig->getUser(),
            $this->dbConfig->getPass(),
            $this->dbConfig->getName(),
        );
    }

    /**
     * @throws InitiateDatabaseException
     */
    private function initializeDatabase(): void
    {
        $this->databaseInitiator->initiateDatabase(
            $this->dbConfig->getHost(),
            $this->dbConfig->getPort(),
            $this->dbConfig->getUser(),
            $this->dbConfig->getPass(),
            $this->dbConfig->getName(),
        );

        (new ViewsGenerator())->generate();
    }

    private function databaseExist(): bool
    {
        try {
            $this->databaseChecker->canCreateDatabase(
                $this->dbConfig->getHost(),
                $this->dbConfig->getPort(),
                $this->dbConfig->getUser(),
                $this->dbConfig->getPass(),
                $this->dbConfig->getName(),
            );
        } catch (DatabaseExistsAndNotEmptyException) {
            return true;
        }
        return false;
    }
}
