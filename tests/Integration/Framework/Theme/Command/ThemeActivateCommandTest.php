<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Theme\Command;

use ArgumentCountError;
use OxidEsales\DeveloperTools\Framework\Theme\Command\ThemeActivateCommand;
use OxidEsales\EshopCommunity\Internal\Framework\Templating\Cache\TemplateCacheServiceInterface;
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

        $templateCacheClearServiceMock = $this->createPartialMock(TemplateCacheServiceInterface::class, ['invalidateTemplateCache']);
        $templateCacheClearServiceMock->expects($this->atLeastOnce())->method('invalidateTemplateCache');

        $themeActivateCommand = $this->getSut($templateCacheClearServiceMock);
        $commandTester = new CommandTester($themeActivateCommand);

        $exitCode = $commandTester->execute($arguments);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testThemeAlreadyActivated(): void
    {
        $themeId = 'twig';
        $arguments = ['theme-id' => $themeId];

        $themeActivateCommand = $this->getSut();
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

        $themeActivateCommand = $this->getSut();
        $commandTester = new CommandTester($themeActivateCommand);

        $exitCode = $commandTester->execute($arguments);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertStringContainsString(sprintf('Theme - "%s" not found.', $themeId), $commandTester->getDisplay());
    }

    public function testThemeActivationWithoutArguments(): void
    {
        $themeActivateCommand = $this->getSut();
        $commandTester = new CommandTester($themeActivateCommand);

        $this->expectException(ArgumentCountError::class);

        $commandTester->execute();
    }

    public function testThemeActivationWithWrongArgument(): void
    {
        $themeId = 'sime-theme-id';
        $arguments = ['themeid' => $themeId];

        $themeActivateCommand = $this->getSut();
        $commandTester = new CommandTester($themeActivateCommand);

        $this->expectException(InvalidArgumentException::class);

        $commandTester->execute($arguments);
    }

    protected function getSut(?TemplateCacheServiceInterface $templateCacheServiceInterfaceMock = null)
    {
        return new ThemeActivateCommand(
            templateCacheService: $templateCacheServiceInterfaceMock ?? $this->createStub(TemplateCacheServiceInterface::class)
        );
    }
}
