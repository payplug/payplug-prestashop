<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugPaymentModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        require_once(dirname(__FILE__) . './../../../../config/config.inc.php');

        /** Call init.php to initialize context */
        require_once(_PS_MODULE_DIR_ . '../init.php');

        /** Call to payplug-php API */
        require_once(_PS_MODULE_DIR_ . '/payplug/classes/PayplugBackward.php');
        require_once(_PS_MODULE_DIR_ . '/payplug/payplug.php');
        require_once(_PS_MODULE_DIR_ . '/payplug/lib/init.php');

        $payplug = Module::getInstanceByName('payplug');
        $payplug->initializeApi();

        $context = Context::getContext();
        $cart = $context->cart;

        $id_payplug_card = Tools::getValue('pc', null);


        $payment_data = $payplug->preparePayment($id_payplug_card);
        //$payment_data = Tools::jsonDecode($payment, true);

        $page = $payplug->getConfiguration('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
        $error_url = $context->link->getPageLink($page, true, $context->language->id, array('error' => 1, 'step' => 3));


        // Invalid payment then return error
        if ($payment_data['result'] && isset($payment_data['return_url']) && $payment_data['return_url']) {
            Payplug::redirectForVersion($payment_data['return_url']);
        } elseif(!$payment_data['result']) {
            if(isset($payment_data['response']) && $payment_data['response']) {
                $payplug->setPaymentErrorsCookie(array($payment_data['response']));
            }
            Payplug::redirectForVersion($error_url);
        }

        die($payment_data['response']);
    }
}
