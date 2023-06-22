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

namespace PayPlug\src\actions;

class OnboardingAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description force integrated payment onboarding
     *
     * @return array
     */
    public function enableIntegratedAction()
    {
        $configurationClass = $this->dependencies->getPlugin()->getConfigurationClass();
        $onboarding_states = json_decode($configurationClass->getValue('onboarding_states'), true);

        if (!is_array($onboarding_states)) {
            return [
                'success' => false,
                'message' => '$onboarding_states does not exist',
            ];
        }

        // embedded_mode state already register, the integrated payment has already been forced
        if (isset($onboarding_states['embedded_mode'])) {
            return [
                'success' => true,
                'message' => 'integrated payment is already forced',
            ];
        }

        $onboarding_states['embedded_mode'] = $configurationClass->getValue('embedded_mode');

        $onboarding_states = json_encode($onboarding_states);

        if (!($configurationClass->set('onboarding_states', $onboarding_states)
            && $configurationClass->set('embedded_mode', 'integrated'))) {
            return [
                'success' => false,
                'message' => 'Something wrong happened! We could not force the integrated payment',
            ];
        }

        return [
            'success' => true,
            'message' => 'Integareted payment has been successfully forced',
        ];
    }

    /**
     * @description  force Rollback integrated payment onboarding
     *
     * @return array
     */
    public function disableIntegratedAction()
    {
        $configurationClass = $this->dependencies->getPlugin()->getConfigurationClass();
        $onboarding_states = json_decode($configurationClass->getValue('onboarding_states'), true);
        if (!is_array($onboarding_states)) {
            return [
                'success' => false,
                'message' => '$onboarding_states does not exist',
            ];
        }

        // embedded_mode state does not exists, we don't need more action
        if (!isset($onboarding_states['embedded_mode'])) {
            return [
                'success' => true,
                'message' => 'integrated payment has not been forced',
            ];
        }

        $onboarding_state = $onboarding_states['embedded_mode'];
        $embedded_mode = $configurationClass->getValue('embedded_mode');
        // We clean onboarding states
        unset($onboarding_states['embedded_mode']);
        $onboarding_states_to_save = (empty($onboarding_states)
            ? $configurationClass->getDefault('onboarding_states')
            : json_encode($onboarding_states));
        if (!$configurationClass->set('onboarding_states', $onboarding_states_to_save)) {
            return [
                'success' => false,
                'message' => 'Something wrong happened! We could not force rollback the integrated payment',
            ];
        }

        // If current embedded mode is different from integrated, we don't need more action
        if ('integrated' != $embedded_mode) {
            return [
                'success' => true,
                'message' => 'embedded_mode is different from integrated',
            ];
        }
        // Else we rollback to the previous configuration
        if (!$configurationClass->set('embedded_mode', $onboarding_state)) {
            return [
                'success' => false,
                'message' => 'Something went wrong! Integrated payment has not been rollback',
            ];
        }

        return [
            'success' => true,
            'message' => 'Integrated payment has been successfully rollback',
        ];
    }
}
