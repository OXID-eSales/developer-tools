<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Module\ResetConfiguration;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidEsales\Facts\Facts;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class ResetConfigurationCommandTest extends TestCase
{
    private string $moduleId = 'some-module';
    private string $settingName = 'some-setting';
    private string $defaultValueFromMetadata = 'some-default-value';
    private int $shopId = 1;
    private string $path;
    private Filesystem $fileSystem;

    public function testResetConfiguration(): void
    {
        exec($this->path . "/bin/oe-console oe:module:reset-configurations");
        $shopConfiguration = $this->getContainer()->get(ShopConfigurationDaoBridgeInterface::class);
        $moduleConfig = $shopConfiguration->get()->getModuleConfigurations()[$this->moduleId];
        $this->assertSame($this->moduleId, $moduleConfig->getId());

        $setting = $moduleConfig->getModuleSetting($this->settingName);
        $value = $setting->getValue();
        $this->assertSame($this->defaultValueFromMetadata, $value);
    }

    protected function setUp(): void
    {
        $this->installModule();
        $this->path = (new Facts())->getShopRootPath();
        $this->fileSystem = new Filesystem();
        $this->createConfigBackup();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->restoreConfigBackup();
        $this->getContainer()->get(ModuleInstallerInterface::class)
            ->uninstall(
                new OxidEshopPackage(Path::join(__DIR__, '/Fixtures', 'TestModule'))
            );
        parent::tearDown();
    }

    private function installModule(): void
    {
        $this->getContainer()->get(ModuleInstallerInterface::class)
            ->install(
                new OxidEshopPackage(Path::join(__DIR__, '/Fixtures', 'TestModule'))
            );

        $this->getContainer()->get(ModuleActivationBridgeInterface::class)
            ->activate($this->moduleId, $this->shopId);
    }

    private function getContainer(): ContainerInterface
    {
        return ContainerFactory::getInstance()->getContainer();
    }

    private function createConfigBackup(): void
    {
        $this->fileSystem->mirror(
            Path::join($this->path, "var/configuration"),
            Path::join($this->path, "var/configuration_bkp")
        );
    }

    private function restoreConfigBackup(): void
    {
        if (file_exists(Path::join($this->path, "var/configuration"))) {
            $this->fileSystem->remove(Path::join($this->path, "var/configuration"));
        }

        $this->fileSystem->rename(
            Path::join($this->path, "/var/configuration_bkp"),
            Path::join($this->path, "/var/configuration")
        );
    }
}
