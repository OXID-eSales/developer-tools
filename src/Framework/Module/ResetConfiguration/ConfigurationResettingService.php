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
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use Symfony\Component\Filesystem\Path;

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
     * @var ContextInterface
     */
    private $context;

    public function __construct(
        ModuleConfigurationInstallerInterface $moduleConfigurationInstaller,
        ModulePathResolverInterface $modulePathResolver,
        ShopConfigurationDaoInterface $shopConfigurationDao,
        ProjectConfigurationGeneratorInterface $projectConfigurationGenerator,
        ContextInterface $context
    ) {
        $this->moduleConfigurationInstaller = $moduleConfigurationInstaller;
        $this->modulePathResolver = $modulePathResolver;
        $this->shopConfigurationDao = $shopConfigurationDao;
        $this->projectConfigurationGenerator = $projectConfigurationGenerator;
        $this->context = $context;
    }

    public function reset(): void
    {
        $shopId = $this->getAnyShopIdFromConfiguration();
        $moduleConfigurations = $this->getModuleConfigurationsPrototype($shopId);
        $fullPaths = $this->getAllModulesFullPathFromConfiguration($moduleConfigurations, $shopId);

        $this->resetConfigurationStorage();
        foreach ($moduleConfigurations as $moduleConfiguration) {
            $this->moduleConfigurationInstaller->install(
                $fullPaths[$moduleConfiguration->getId()]
            );
        }
    }

    private function getAllModulesFullPathFromConfiguration(array $moduleConfigurations, int $shopId): array
    {
        $fullPaths = [];
        foreach ($moduleConfigurations as $moduleConfiguration) {
            $fullPaths[$moduleConfiguration->getId()] =
                $this->modulePathResolver->getFullModulePathFromConfiguration(
                    $moduleConfiguration->getId(),
                    $shopId
                );
        }

        return $fullPaths;
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
