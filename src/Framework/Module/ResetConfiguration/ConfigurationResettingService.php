<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\DeveloperTools\Framework\Module\ResetConfiguration;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ProjectConfigurationGeneratorInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;

class ConfigurationResettingService implements ConfigurationResettingServiceInterface
{
    /** @var ModuleConfigurationInstallerInterface */
    private $moduleConfigurationInstaller;
    /** @var ModulePathResolverInterface */
    private $modulePathResolver;
    /** @var ShopConfigurationDaoInterface */
    private $shopConfigurationDao;
    /** @var ProjectConfigurationGeneratorInterface */
    private $projectConfigurationGenerator;

    public function __construct(
        ModuleConfigurationInstallerInterface $moduleConfigurationInstaller,
        ModulePathResolverInterface $modulePathResolver,
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ProjectConfigurationGeneratorInterface $projectConfigurationGenerator
    ) {
        $this->moduleConfigurationInstaller = $moduleConfigurationInstaller;
        $this->modulePathResolver = $modulePathResolver;
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->projectConfigurationGenerator = $projectConfigurationGenerator;
    }

    public function reset(): void
    {
        $modulePaths = $this->collectModulePaths();
        $this->resetConfigurationStorage();
        array_walk($modulePaths, function ($path) {
            $this->moduleConfigurationInstaller->install($path, $path);
        });
    }

    private function collectModulePaths(): array
    {
        $paths = [];
        $shopId = $this->getAnyShopIdFromConfiguration();
        $moduleConfigurations = $this->getModuleConfigurationsPrototype($shopId);
        foreach ($moduleConfigurations as $configuration) {
            $paths[] = $this->modulePathResolver->getFullModulePathFromConfiguration($configuration->getId(), $shopId);
        }
        return $paths;
    }

    private function getAnyShopIdFromConfiguration(): int
    {
        $shopIds = array_keys($this->shopConfigurationDao->getAll());
        return $this->getFirstShopId($shopIds);
    }


    private function getFirstShopId(array $ids): int
    {
        return reset($ids);
    }

    private function getModuleConfigurationsPrototype(int $shopId): array
    {
        return $this->shopConfigurationDao->get($shopId)
            ->getModuleConfigurations();
    }

    private function resetConfigurationStorage(): void
    {
        $this->shopConfigurationDao->deleteAll();
        $this->projectConfigurationGenerator->generate();
    }
}
