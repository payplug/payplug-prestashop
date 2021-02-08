<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugPaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        require_once(_PS_ROOT_DIR_.'/config/config.inc.php');

        /** Call init.php to initialize context */
        require_once(_PS_MODULE_DIR_ . '../init.php');

        /** Call to payplug-php API */
        require_once(_PS_MODULE_DIR_ . '/payplug/classes/PayplugBackward.php');
        require_once(_PS_MODULE_DIR_ . '/payplug/payplug.php');

        $payplug = Module::getInstanceByName('payplug');
        $payplug->initializeApi();

        $context = Context::getContext();

        $id_payplug_card = Tools::getValue('pc', 'new_card');

        $type = Tools::getValue('type', null);
        $io = Tools::getValue('io', null);
        $is_oney = null;
        if ((isset($type)) && ($type == 'oney')) {
            if (isset($io)) {
                $is_oney = 'x'.$io.'_with_fees';
            }
        }
        $options = [
            'id_card' => $id_payplug_card,
            'is_oney' => $is_oney,
            '_ajax' => 1
        ];

        $payment_data = $payplug->preparePayment($options);
        $payment_data_16 = Tools::jsonDecode($payment_data, true);

        $page = $payplug->getConfiguration('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $error_url = $context->link->getPageLink($page, true, $context->language->id, ['error' => 1, 'step' => 3]);

        // Invalid payment then return error
        if (($payment_data['result'] && (isset($payment_data['return_url']) && $payment_data['return_url']))) {
            Payplug::redirectForVersion($payment_data['return_url']);
        }
        if (($payment_data_16['result'] && (isset($payment_data_16['return_url']) && $payment_data_16['return_url']))) {
            Payplug::redirectForVersion($payment_data_16['return_url']);
        } elseif (!$payment_data['result']) {
            if (isset($payment_data['response']) && $payment_data['response']) {
                $payplug->setPaymentErrorsCookie([$payment_data['response']]);
            }
            Payplug::redirectForVersion($error_url);
        } elseif (!$payment_data_16['result']) {
            if (isset($payment_data_16['response']) && $payment_data_16['response']) {
                $payplug->setPaymentErrorsCookie([$payment_data_16['response']]);
            }
            Payplug::redirectForVersion($error_url);
        }

        if ((isset($payment_data['response'])) || (isset($payment_data_16['response']))) {
            die($payment_data['response']);
        }
    }
}
