<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\unified;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;
use PayPlug\src\models\repositories\UnifiedOperationRepository;
use PayPlug\src\unified\contract\UnifiedConfigurationRepository;
use PayPlug\src\unified\contract\UnifiedHttpClient;
use PayPlug\src\unified\contract\UnifiedLock;
use PayPlug\src\unified\contract\UnifiedLogger;
use PayPlug\src\unified\contract\UnifiedOrderStateMutator;
use PayPlug\src\unified\contract\UnifiedTokenCache;
use PayplugUnifiedCore\Auth\OAuth2Client;
use PayplugUnifiedCore\Auth\TokenManager;
use PayplugUnifiedCore\Services\UnifiedApiPaymentService;

class UnifiedApiPaymentServiceFactory
{
    private const SCOPE = 'unified_api';
    private const AUDIENCE = 'https://www.payplug.com';

    public $dependencies;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    public function create(): UnifiedApiPaymentService
    {
        $configuration_repository = $this->createConfigurationRepository();
        $http_client = new UnifiedHttpClient();
        $oauth2_client = new OAuth2Client(
            $http_client,
            $this->dependencies->getPlugin()->getRoutes()->getApiUrl(),
            '',
            self::SCOPE,
            self::AUDIENCE
        );
        $token_manager = new TokenManager($this->createTokenCache(), $oauth2_client);

        return new UnifiedApiPaymentService(
            $http_client,
            $token_manager,
            $this->dependencies->getPlugin()->getRoutes()->getUnifiedApiUrl(),
            $configuration_repository->getClientId(),
            $configuration_repository->getClientSecret()
        );
    }

    public function createLogger(): UnifiedLogger
    {
        return new UnifiedLogger($this->dependencies);
    }

    public function createLock(): UnifiedLock
    {
        return new UnifiedLock($this->dependencies);
    }

    public function createTokenCache(): UnifiedTokenCache
    {
        return new UnifiedTokenCache($this->dependencies);
    }

    public function createPaymentRepository(): UnifiedOperationRepository
    {
        return new UnifiedOperationRepository($this->dependencies);
    }

    public function createOrderStateMutator(): UnifiedOrderStateMutator
    {
        return new UnifiedOrderStateMutator($this->dependencies);
    }

    public function createConfigurationRepository(): UnifiedConfigurationRepository
    {
        return new UnifiedConfigurationRepository($this->dependencies);
    }
}
