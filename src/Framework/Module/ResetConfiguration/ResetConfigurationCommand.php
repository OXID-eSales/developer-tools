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

class ResetConfigurationCommand extends Command
{
    private const EXECUTE_SUCCESS_MESSAGE = 'Project configuration was reset successfully';
    private const COMMAND_DESCRIPTION = 'Resets changes in project configuration.';
    private const COMMAND_NAME = 'oe:module:reset-configurations';

    /** @var ConfigurationResettingServiceInterface */
    private $configurationResetter;

    /** @param ConfigurationResettingServiceInterface $configurationRestorer */
    public function __construct(
        ConfigurationResettingServiceInterface $configurationRestorer
    ) {
        parent::__construct();
        $this->configurationResetter = $configurationRestorer;
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configurationResetter->reset();
        $output->writeln(sprintf('<info>%s.</info>', self::EXECUTE_SUCCESS_MESSAGE));
        return 0;
    }
}
