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
    $flag = true;

    $plugin = $object->payplug_dependencies->getPlugin();
    $logger = $plugin->getLogger();
    $logger->addLog('Start upgrade script 5.2.2');

    // The live partial refund state used to be read from PS_CHECKOUT_STATE_PARTIALLY_REFUNDED, which belongs
    // to PrestaShop Checkout and may point to any state (even a merchant's shipping one). Give it back its own state.
    $configuration = $plugin->getConfigurationClass();
    $id_order_state = (int) $configuration->getValue('order_state_partial_refund');
    $order_state = new OrderState($id_order_state);

    if ($id_order_state && $order_state->module_name !== $object->name) {
        $logger->addLog('Upgrade 5.2.2 - Live partial refund state ' . $id_order_state
            . ' belongs to module "' . $order_state->module_name . '", replace it with a Payplug one');

        // Drop the type Payplug set on this foreign state: "refund" through PS_CHECKOUT_STATE_PARTIALLY_REFUNDED,
        // "partial_refund" through runUpgradeModule() which retypes the configured state before this script.
        // A native state gets its own type back from installTypeAction() below.
        $state_type = $plugin->getStateRepository()->getBy('id_order_state', $id_order_state);
        if (!empty($state_type) && in_array($state_type['type'], ['refund', 'partial_refund'], true)) {
            $flag = (bool) $plugin->getStateRepository()->deleteBy('id_order_state', $id_order_state);
        }

        // Reuse an existing "Partially refunded [PayPlug]" state if any, create it otherwise
        $flag = $flag && Configuration::deleteByName($configuration->getName('order_state_partial_refund'));
        $flag = $flag && $plugin->getOrderState()->create(
            'partial_refund',
            $configuration->order_states['partial_refund'],
            false
        );
        $plugin->getOrderStateAction()->installTypeAction();

        $logger->addLog('Upgrade 5.2.2 - Live partial refund state is now '
            . (int) $configuration->getValue('order_state_partial_refund'));
    }

    $logger->addLog('End upgrade script 5.2.2, result: ' . ($flag ? 'ok' : 'ko'));

    return $flag;
}
