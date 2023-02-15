<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Theme\Command;

use OxidEsales\Eshop\Core\Cache\DynamicContent\ContentCache as EshopContentCache;
use OxidEsales\Eshop\Core\Exception\StandardException as EshopStandardException;
use OxidEsales\Eshop\Core\Theme as EshopTheme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command activates theme by theme id.
 */
class ThemeActivateCommand extends Command
{
    private const MESSAGE_THEME_IS_ACTIVE = 'Theme - "%s" is already active.';
    private const MESSAGE_THEME_ACTIVATED = 'Theme - "%s" was activated.';
    private const MESSAGE_THEME_NOT_FOUND = 'Theme - "%s" not found.';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Activates a theme.')
            ->addArgument('theme-id', InputArgument::REQUIRED, 'Theme ID')
            ->setHelp('Command activates theme by defined theme ID.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $themeId = $input->getArgument('theme-id');

        $theme = oxNew(EshopTheme::class);
        if (!$theme->load($themeId)) {
            $output->writeLn('<error>' . sprintf(self::MESSAGE_THEME_NOT_FOUND, $themeId) . '</error>');

            return Command::INVALID;
        }

        if ($theme->getActiveThemeId() == $themeId) {
            $output->writeln('<comment>' . sprintf(self::MESSAGE_THEME_IS_ACTIVE, $themeId) . '</comment>');

            return Command::SUCCESS;
        }

        try {
            $theme->activate();

            $cache = oxNew(EshopContentCache::class);
            $cache->reset(false);

            $output->writeLn('<info>' . sprintf(self::MESSAGE_THEME_ACTIVATED, $themeId) . '</info>');
        } catch (EshopStandardException $exception) {
            $output->writeLn('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
