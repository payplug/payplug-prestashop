<?php
/**
 * 2013 - 2023 Payplug SAS
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
 * @copyright 2013 - 2023 Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\actions;

class OnboardingAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function enableIntegratedAction()
    {
        $configurationClass = $this->dependencies->getPlugin()->getConfigurationClass();
        $onboarding_states = json_decode($configurationClass->getValue('onboarding_states'), true);

        if (!is_array($onboarding_states)) {
            // todo: add error log
            return false;
        }

        // embedded_mode state already register, the integrated payment has already been forced
        if (isset($onboarding_states['embedded_mode'])) {
            return true;
        }

        $onboarding_states['embedded_mode'] = $configurationClass->getValue('embedded_mode');
        $onboarding_states = json_encode($onboarding_states);

        return $configurationClass->set('onboarding_states', $onboarding_states)
            && $configurationClass->set('embedded_mode', 'integrated');
    }

    public function disableIntegratedAction()
    {
        // todo: thrue ticket PRE-1769
    }
}
