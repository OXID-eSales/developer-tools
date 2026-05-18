<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Module\ResetConfiguration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetConfigurationCommand extends Command
{
    private const EXECUTE_SUCCESS_MESSAGE = 'Project configuration was reset successfully';
    private const COMMAND_DESCRIPTION = 'Removes and re-installs project configuration.';
    private const COMMAND_NAME = 'oe:module:reset-configurations';

    public function __construct(
        private readonly ConfigurationResettingServiceInterface $configurationResetter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->configurationResetter->reset();
        $io->success(self::EXECUTE_SUCCESS_MESSAGE);
        return Command::SUCCESS;
    }
}
