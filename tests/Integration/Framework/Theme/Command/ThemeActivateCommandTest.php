<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Theme\Command;

use ArgumentCountError;
use OxidEsales\DeveloperTools\Framework\Theme\Command\ThemeActivateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

final class ThemeActivateCommandTest extends TestCase
{
    public function testThemeActivationOnSuccess(): void
    {
        $themeId = 'twig';
        $arguments = ['theme-id' => $themeId];

        $themeActivateCommand = new ThemeActivateCommand();
        $commandTester = new CommandTester($themeActivateCommand);

        $exitCode = $commandTester->execute($arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testThemeAlreadyActivated(): void
    {
        $themeId = 'twig';
        $arguments = ['theme-id' => $themeId];

        $themeActivateCommand = new ThemeActivateCommand();
        $commandTester = new CommandTester($themeActivateCommand);

        $commandTester->execute($arguments);
        $exitCode = $commandTester->execute($arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(sprintf('Theme - "%s" is already active.', $themeId), $commandTester->getDisplay());
    }

    public function testNonExistingThemeActivation(): void
    {
        $themeId = 'sime-theme-id';
        $arguments = ['theme-id' => $themeId];

        $themeActivateCommand = new ThemeActivateCommand();
        $commandTester = new CommandTester($themeActivateCommand);

        $exitCode = $commandTester->execute($arguments);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString(sprintf('Theme - "%s" not found.', $themeId), $commandTester->getDisplay());
    }

    public function testThemeActivationWithoutArguments(): void
    {
        $themeActivateCommand = new ThemeActivateCommand();
        $commandTester = new CommandTester($themeActivateCommand);

        $this->expectException(ArgumentCountError::class);

        $exitCode = $commandTester->execute();
    }

    public function testThemeActivationWithWrongArgument(): void
    {
        $themeId = 'sime-theme-id';
        $arguments = ['themeid' => $themeId];

        $themeActivateCommand = new ThemeActivateCommand();
        $commandTester = new CommandTester($themeActivateCommand);

        $this->expectException(InvalidArgumentException::class);

        $exitCode = $commandTester->execute($arguments);
    }
}