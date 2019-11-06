<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\DeveloperTools\Framework\Module\ResetConfiguration;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ModuleConfigurationInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service\ProjectConfigurationGeneratorInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Path\ModulePathResolverInterface;

/**
 * Class ConfigurationResettingService
 * @package OxidEsales\DeveloperTools\Framework\Module\Install\Service
 */
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

    /**
     * ConfigurationResettingService constructor.
     * @param ModuleConfigurationInstallerInterface $moduleConfigurationInstaller
     * @param ModulePathResolverInterface $modulePathResolver
     * @param ShopConfigurationDaoInterface $shopConfigurationDao
     * @param ProjectConfigurationGeneratorInterface $projectConfigurationGenerator
     */
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

    /**
     * @inheritDoc
     */
    public function reset(): void
    {
        $modulePaths = $this->collectModulePaths();
        $this->resetConfigurationStorage();
        array_walk($modulePaths, function ($path) {
            $this->moduleConfigurationInstaller->install($path, $path);
        });
    }

    /**
     * @return array
     */
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

    /**
     * @return int
     */
    private function getAnyShopIdFromConfiguration(): int
    {
        $shopIds = array_keys($this->shopConfigurationDao->getAll());
        return $this->getFirstShopId($shopIds);
    }

    /**
     * @param int[] $ids
     * @return mixed
     */
    private function getFirstShopId(array $ids): int
    {
        return reset($ids);
    }

    /**
     * Result is used to re-install modules for ALL shops
     * @param int $shopId
     * @return ModuleConfiguration[]
     */
    private function getModuleConfigurationsPrototype(int $shopId): array
    {
        return $this->shopConfigurationDao->get($shopId)
            ->getModuleConfigurations();
    }

    /**
     * Delete and re-create empty configuration files
     */
    private function resetConfigurationStorage(): void
    {
        $this->shopConfigurationDao->deleteAll();
        $this->projectConfigurationGenerator->generate();
    }
}
