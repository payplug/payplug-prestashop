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
 * Do not edit or add to this file if you wish to upgrade Payplug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_5_3_0($object)
{
    $flag = true;

    $plugin = $object->payplug_dependencies->getPlugin();
    $logger = $plugin->getLogger();
    $logger->addLog('Start upgrade script 5.3.0');

    $configuration = $plugin->getConfigurationClass();
    if (false === $configuration->getValue('apm_custom_settings')) {
        $flag = (bool) $configuration->set('apm_custom_settings', '{}');
    }

    try {
        $apm_eligibility = new \PayPlug\src\models\classes\ApmEligibility($object->payplug_dependencies);
        $logger->addLog('Upgrade 5.3.0: Scalapay amounts import ' . ($apm_eligibility->importLegacyScalapaySettings() ? 'ok' : 'ko'));
    } catch (\Exception $e) {
        $logger->addLog('Upgrade 5.3.0: Scalapay amounts import failed with code ' . (int) $e->getCode(), 'warning');
    }

    // The cached amounts and countries are kept as the last known limits; the API being
    // unreachable here must not fail the upgrade.
    try {
        $permissions = $plugin
            ->getModule()
            ->getInstanceByName($object->payplug_dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getAccount();
        $logger->addLog('Upgrade 5.3.0: payment method limits refresh ' . (empty($permissions) ? 'skipped' : 'ok'));
    } catch (\Exception $e) {
        $logger->addLog('Upgrade 5.3.0: payment method limits refresh failed with code ' . (int) $e->getCode(), 'warning');
    }

    $logger->addLog('End upgrade script 5.3.0, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
