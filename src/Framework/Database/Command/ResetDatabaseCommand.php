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
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
    private $databaseChecker;

    /**
     * @var DatabaseCreatorInterface
     */
    private $databaseCreator;

    /**
     * @var DatabaseInitiatorInterface
     */
    private $databaseInitiator;

    /**
     * @var DropDatabaseServiceInterface
     */
    private $dropDatabaseService;

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
            ->addOption(self::FORCE_RESET, null, InputOption::VALUE_NONE, "Don't ask for the deletion of the database, but force the operation to run.");
            $this->setDescription('Performs database reset. <error>ATTENTION: This operation should not be executed in a production environment.</error>');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws DatabaseConnectionException
     * @throws DatabaseExistsException
     * @throws InitiateDatabaseException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkRequiredCommandOptions($this->getDefinition()->getOptions(), $input);

        $output->writeln('<info>Resetting database...</info>');
        if ($this->databaseExist($input)) {
            if (!$this->forceDatabaseReset($input) && !$this->confirmAction($input, $output)) {
                $output->writeln('<info>Reset has been canceled.</info>');
                return Command::SUCCESS;
            }
            $output->writeln('<info>Dropping existing database...</info>');
            $this->dropDatabase($input);
        }
        try {
            $output->writeln('<info>Creating database...</info>');
            $this->createDatabase($input);
        } catch (DatabaseExistsException $exception) {
        }
        
        $output->writeln('<info>Initializing database...</info>');
        $this->initializeDatabase($input);

        $output->writeln('<info>Reset has been finished.</info>');

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function confirmAction(InputInterface $input, OutputInterface $output): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($this->getQuestionText($input), false);

        return $helper->ask($input, $output, $question);
    }
    
    /**
     * @param InputInterface $input
     * @return string
     */
    private function getQuestionText(InputInterface $input): string
    {
        return sprintf('Seems there is already OXID eShop installed in database %s. All data in a given database will
         be lost when executing this command. Continue executing it? [no/yes]', $input->getOption(self::DB_NAME));
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function forceDatabaseReset(InputInterface $input): bool
    {
        $value = $input->getOption(self::FORCE_RESET);
        return isset($value) && $value;
    }

    /**
     * @param InputInterface $input
     * @throws DatabaseConnectionException
     */
    private function dropDatabase(InputInterface $input): void
    {
        $this->dropDatabaseService->dropDatabase(
            $input->getOption(self::DB_HOST),
            (int) $input->getOption(self::DB_PORT),
            $input->getOption(self::DB_USER),
            $input->getOption(self::DB_PASSWORD),
            $input->getOption(self::DB_NAME));
    }

    /**
     * @param InputInterface $input
     * @throws DatabaseExistsException
     * @throws DatabaseConnectionException
     */
    private function createDatabase(InputInterface $input): void
    {
        $this->databaseCreator->createDatabase(
            $input->getOption(self::DB_HOST),
            (int) $input->getOption(self::DB_PORT),
            $input->getOption(self::DB_USER),
            $input->getOption(self::DB_PASSWORD),
            $input->getOption(self::DB_NAME));
    }

    /**
     * @param InputInterface $input
     * @throws InitiateDatabaseException
     */
    private function initializeDatabase(InputInterface $input): void
    {
        $this->databaseInitiator->initiateDatabase(
            $input->getOption(self::DB_HOST),
            (int) $input->getOption(self::DB_PORT),
            $input->getOption(self::DB_USER),
            $input->getOption(self::DB_PASSWORD),
            $input->getOption(self::DB_NAME));
        $this->generateViews();
    }

    /**
     * @param InputInterface $input
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
