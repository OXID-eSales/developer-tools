<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\DeveloperTools\Framework\Module\ResetConfiguration;

interface ConfigurationResettingServiceInterface
{
    public function reset(): void;
}
