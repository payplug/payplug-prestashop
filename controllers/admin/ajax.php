<?php
/**
 * 2013 - 2017 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2017 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugAjaxModuleAdminController extends ModuleAdminController
{
    /**
     * @see FrontController::initContent()
     */
/*
    public function initContent()
    {

    }
*/
}

require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_.'../init.php');
include_once(_PS_MODULE_DIR_.'payplug/payplug.php');

$payplug = new Payplug();

if (Tools::getValue('_ajax') == 1) {
    if ((int)Tools::getValue('en') == 1 && (int)Configuration::get('PAYPLUG_SHOW') == 0) {
        Configuration::updateValue('PAYPLUG_SHOW', 1);
        $payplug->enable();
        die(true);
    }
    if (Tools::getIsset('en')
        && (int)Tools::getValue('en') == 0
        && (int)Configuration::get('PAYPLUG_SHOW') == 1
    ) {
        Configuration::updateValue('PAYPLUG_SHOW', 0);
        die(true);
    }
    if (Tools::getIsset('db')) {
        if (Tools::getValue('db') == 'on') {
            Configuration::updateValue('PAYPLUG_DEBUG_MODE', 1);
        } elseif (Tools::getValue('db') == 'off') {
            Configuration::updateValue('PAYPLUG_DEBUG_MODE', 0);
        }
        die(true);
    }
    if ((int)Tools::getValue('popin') == 1) {
        $args = null;
        if (Tools::getValue('type') == 'confirm') {
            $sandbox = (int)Tools::getValue('sandbox');
            $embedded = (int)Tools::getValue('embedded');
            $one_click = (int)Tools::getValue('one_click');
            $activate = (int)Tools::getValue('activate');
            $args = array(
                'sandbox' => $sandbox,
                'embedded' => $embedded,
                'one_click' => $one_click,
                'activate' => $activate,
            );
        }
        $payplug->displayPopin(Tools::getValue('type'), $args);
    }
    if (Tools::getValue('submit') == 'submitPopin_pwd') {
        $payplug->submitPopinPwd(Tools::getValue('pwd'));
    }
    if (Tools::getValue('submit') == 'submitPopin_confirm') {
        die(json_encode(array('content' => 'confirm_ok')));
    }
    if (Tools::getValue('submit') == 'submitPopin_confirm_a') {
        die(json_encode(array('content' => 'confirm_ok_activate')));
    }
    if (Tools::getValue('submit') == 'submitPopin_desactivate') {
        die(json_encode(array('content' => 'confirm_ok_desactivate')));
    }
    if ((int)Tools::getValue('check') == 1) {
        $content = $payplug->getCheckFieldset();
        die(json_encode(array('content' => $content)));
    }
    if ((int)Tools::getValue('log') == 1) {
        $content = $payplug->getLogin();
        die(json_encode(array('content' => $content)));
    }
    if ((int)Tools::getValue('checkPremium') == 1) {
        $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
        if ($payplug->checkPremium($api_key)) {
            die(true);
        } else {
            die(false);
        }
    }
    if ((int)Tools::getValue('refund') == 1) {
        if (!$payplug->checkAmountToRefund(Tools::getValue('amount'))) {
            die(json_encode(array(
                'status' => 'error',
                'data' => $payplug->l('Incorrect amount to refund')
                //'data' => $this->getTranslator()->trans('Incorrect amount to refund', array(), 'Modules.Payplug.Admin')
            )));
        } else {
            $amount = Tools::getValue('amount');
            $amount = str_replace(',', '.', $amount);
            $amount = $amount * 100;
        }

        $id_order = Tools::getValue('id_order');
        $pay_id = Tools::getValue('pay_id');
        $metadata = array(
            'ID Client' => (int)Tools::getValue('id_customer'),
            'reason' => 'Refunded with Prestashop'
        );
        $refund = $payplug->makeRefund($pay_id, $amount, $metadata);
        if ($refund == 'error') {
            die(json_encode(array(
                'status' => 'error',
                'data' => $payplug->l('Cannot refund that amount.')
                //'data' => $this->getTranslator()->trans('Cannot refund that amount.', array(), 'Modules.Payplug.Admin')
            )));
        } else {
            $payment = $payplug->retrievePayment($pay_id);
            $new_state = 7;
            if ((int)Tools::getValue('id_state') != 0) {
                $new_state = (int)Tools::getValue('id_state');
            } elseif ($payment->is_refunded == 1) {
                if ($payment->is_live == 1) {
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                } else {
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                }
            }

            $reload = false;
            if ((int)Tools::getValue('id_state') != 0 || $payment->is_refunded == 1) {
                $order = new Order((int)$id_order);
                if (Validate::isLoadedObject($order)) {
                    $current_state = (int)$order->getCurrentState();
                    if ($current_state != 0 && $current_state != $new_state) {
                        $history = new OrderHistory();
                        $history->id_order = (int)$order->id;
                        $history->changeIdOrderState($new_state, (int)$order->id);
                        $history->addWithemail();
                    }
                }
                $reload = true;
            }

            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available = ($payment->amount - $payment->amount_refunded) / 100;

            $data = $payplug->getRefundData(
                $amount_refunded_payplug,
                $amount_available
            );
            die(json_encode(array(
                'status' => 'ok',
                'data' => $data,
                'message' => $payplug->l('Amount successfully refunded.'),
                //'message' => $this->getTranslator()->trans('Amount successfully refunded.', array(), 'Modules.Payplug.Admin'),
                'reload' => $reload
            )));
        }
    }
    if ((int)Tools::getValue('popinRefund') == 1) {
        $popin = $payplug->displayPopin('refund');
        die(json_encode(array('content' => $popin)));
    }
} else {
    exit;
}
