<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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

/**
 * @description
 * Treat onboarding
 */
class PayplugOnboardingModuleFrontController extends ModuleFrontController
{
    private $dependencies;
    private $onboardingAction;
    private $permissions;
    private $permissions_to_check = [
        'integratedPayment',
    ];

    public function postProcess()
    {
        $this->initialize();

        foreach ($this->permissions_to_check as $permission) {
            $method = 'process' . $this->toolsAdapter->tool('ucfirst', $permission);
            $this->{$method}();
        }

        echo 'Onboarding checked';
        exit;
    }

    private function initialize()
    {
        $this->dependencies = new \PayPlug\classes\DependenciesClass();
        $this->onboardingAction = $this->dependencies->getPlugin()->getOnboardingAction();
        $this->permissions = $this->dependencies->apiClass->getAccountPermissions();
        $this->toolsAdapter = $this->dependencies->getPlugin()->getTools();
    }

    private function processIntegratedPayment()
    {
        if (!isset($this->permissions['can_use_integrated_payments'])) {
            // todo: add logs
            return false;
        }

        if ($this->permissions['can_use_integrated_payments']) {
            return $this->onboardingAction->enableIntegratedAction();
        }

        return $this->onboardingAction->disableIntegratedAction();
    }
}
