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

class CheckDatabaseCommand extends Command
{
    use NamedArgumentsTrait;

    private const DB_HOST = 'db-host';
    private const DB_PORT = 'db-port';
    private const DB_NAME = 'db-name';
    private const DB_USER = 'db-user';
    private const DB_PASSWORD = 'db-password';

    /**
     * @var DatabaseCheckerInterface
     */
    private DatabaseCheckerInterface $databaseChecker;

    public function __construct(
        DatabaseCheckerInterface $databaseChecker
    ) {
        parent::__construct();

        $this->databaseChecker = $databaseChecker;
    }

    protected function configure()
    {
        $this
            ->addOption(self::DB_HOST, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_PORT, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_NAME, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_USER, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::DB_PASSWORD, null, InputOption::VALUE_REQUIRED);

        $this->setDescription('Performs database check.');

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

        $output->writeln('<info>Checking database...</info>');
        if ($this->databaseExist($input)) {
            $output->writeln('<info>Database exists.</info>');
        } else {
            $output->writeln('<info>Database does not exist.</info>');
        }

        $output->writeln('<info>Check has been finished.</info>');

        return Command::SUCCESS;
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
}
