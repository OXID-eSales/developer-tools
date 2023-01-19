<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Tests\Integration\Framework\Module\ResetConfiguration;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Dao\ShopConfigurationSettingDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ModuleConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\DataObject\OxidEshopPackage;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleInstallerInterface;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Console\ConsoleTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Webmozart\PathUtil\Path;

final class ResetConfigurationCommandTest extends TestCase
{
    use ContainerTrait;
    use ConsoleTrait;

    /** @var string  */
    private $moduleId = 'some-module';
    /** @var int  */
    private $shopId = 1;

    public function setUp(): void
    {
        parent::setUp();
        $this->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->rollBackTransaction();
        parent::tearDown();
    }

    public function testResetWithConfigModificationWillReturnInitialValue(): void
    {
        $settingName = 'some-setting';
        $defaultValueFromMetadata = 'some-default-value';

        $this->installTestModule();
        $configurationDao = $this->get(ModuleConfigurationDaoInterface::class);
        $configuration = $configurationDao->get($this->moduleId, $this->shopId);
        $setting = $configuration->getModuleSetting($settingName);
        $setting->setValue('new-value');
        $configurationDao->save($configuration, $this->shopId);

        $this->execute(
            $this->getApplication(),
            $this->get('oxid_esales.console.commands_provider.services_commands_provider'),
            new ArrayInput(['command' => 'oe:module:reset-configurations'])
        );
        $configuration = $configurationDao->get($this->moduleId, $this->shopId);
        $setting = $configuration->getModuleSetting($settingName);
        $value = $setting->getValue();

        $this->assertSame($defaultValueFromMetadata, $value);
    }

    private function installTestModule(): void
    {
        $this->get(ModuleInstallerInterface::class)
            ->install(
                new OxidEshopPackage(Path::join(__DIR__ . '/Fixtures', 'TestModule'))
            );
    }

    private function cleanupTestData(): void
    {
        $this
            ->get(ModuleInstallerInterface::class)
            ->uninstall(
                new OxidEshopPackage(
                    Path::join(__DIR__ . '/Fixtures', 'TestModule')
                )
            );

        $activeModules = new ShopConfigurationSetting();
        $activeModules
            ->setName(ShopConfigurationSetting::ACTIVE_MODULES)
            ->setValue([])
            ->setShopId(1)
            ->setType(ShopSettingType::ASSOCIATIVE_ARRAY);
        $this->get(ShopConfigurationSettingDaoInterface::class)->save($activeModules);
    }

    protected function getApplication(): Application
    {
        $application = $this->get('oxid_esales.console.symfony.component.console.application');
        $application->setAutoExit(false);
        return $application;
    }
    private function beginTransaction()
    {
        DatabaseProvider::getDb()->startTransaction();
    }

    private function rollBackTransaction()
    {
        if (DatabaseProvider::getDb()->isTransactionActive()) {
            DatabaseProvider::getDb()->rollbackTransaction();
        }
    }

}
