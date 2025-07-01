<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Database\Command;

use OxidEsales\DeveloperTools\Framework\Database\Service\DropDatabaseServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Setup\Database\DatabaseNotEmptyException;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\ShopDbManagerInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\DBAL\Exception\ConnectionException;

class ResetDatabaseCommand extends Command
{
    private const FORCE_RESET = 'force';
    private const CONFIRM_QUESTION = <<<'EOF'
<question>Seems there is already OXID eShop installed in database `%s`.
All data in a given database will be lost when executing this command!
Continue executing it? [No/yes]: </question>
EOF;
    private const COMMAND_DESCRIPTION = <<<'EOF'
Performs database reset. <error>ATTENTION: This operation should not be executed in a production environment.</error>
EOF;
    private const FORCE_OPTION_DESCRIPTION =
        "Don't ask for the deletion of the database, but force the operation to run.";
    private DatabaseConfiguration $dbConfig;

    public function __construct(
        private readonly BasicContextInterface $basicContext,
        private readonly SetupDbConnectionValidatorInterface $setupDbConnectionValidator,
        private readonly ShopDbManagerInterface $shopDbManager,
        private readonly DropDatabaseServiceInterface $dropDatabaseService,
        private readonly SetupDbConnectionFactoryInterface $databaseConnectionFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::FORCE_RESET, null, InputOption::VALUE_NONE, self::FORCE_OPTION_DESCRIPTION);
        $this->setDescription(self::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);
        $this->dbConfig = new DatabaseConfiguration($this->basicContext->getDatabaseUrl());
        try {
            $this->setupDbConnectionValidator->validate($this->dbConfig);
            $output->writeln('<info>Database does not exist...</info>');
        } catch (DatabaseNotEmptyException) {
            if (!$this->proceedToDropDatabase($input, $output)) {
                $output->writeln('<info>Reset has been canceled.</info>');

                return Command::SUCCESS;
            }
        }

        if ($this->databaseExists($this->dbConfig)) {
            $output->writeln('<info>Dropping existing database...</info>');
            $this->dropDatabaseService->dropDatabase($this->dbConfig);
        }

        $output->writeln('<info>Creating database...</info>');
        $this->shopDbManager->create($this->dbConfig);
        $output->writeln('<info>Reset has been finished in ' . round(microtime(true) - $start) . 's</info>');

        return Command::SUCCESS;
    }

    private function proceedToDropDatabase(InputInterface $input, OutputInterface $output): bool
    {
        return $input->getOption(self::FORCE_RESET) ||
            $this->getHelper('question')
                ->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion(sprintf(self::CONFIRM_QUESTION, $this->dbConfig->getName()), false)
                );
    }

    private function databaseExists(DatabaseConfiguration $databaseConfiguration): bool
    {
        try {
            $this->databaseConnectionFactory->getDatabaseConnection($databaseConfiguration);

            return true;
        } catch (ConnectionException) {
            return false;
        }
    }
}
