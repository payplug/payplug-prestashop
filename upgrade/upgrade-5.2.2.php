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

function upgrade_module_5_2_2($object)
{
    $plugin = $object->payplug_dependencies->getPlugin();
    $logger = $plugin->getLogger();
    $logger->addLog('Start upgrade script 5.2.2');

    // The live partial refund state used to be read from PS_CHECKOUT_STATE_PARTIALLY_REFUNDED, which belongs
    // to PrestaShop Checkout and may point to any state (even a merchant's shipping one). Give it back its own state.
    $flag = $plugin->getOrderStateAction()->repairPartialRefundStateAction();

    $logger->addLog('End upgrade script 5.2.2, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
