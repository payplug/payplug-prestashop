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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
            $installment = (int)Tools::getValue('installment');
            $deferred = (int)Tools::getValue('deferred');
            $activate = (int)Tools::getValue('activate');
            $args = array(
                'sandbox' => $sandbox,
                'embedded' => $embedded,
                'one_click' => $one_click,
                'installment' => $installment,
                'deferred' => $deferred,
                'activate' => $activate,
            );
        }
        $payplug->displayPopin(Tools::getValue('type'), $args);
    }
    if (Tools::getValue('submit') == 'submitPopin_pwd') {
        $payplug->submitPopinPwd($_POST['pwd']);
    }
    if (Tools::getValue('has_live_key')) {
        die(Tools::jsonEncode(['result' => $payplug->has_live_key()]));
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
    if (Tools::getValue('submit') == 'submitPopin_abort') {
        die(json_encode(array('content' => '')));
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
        die(json_encode($payplug->getAccountPermissions($api_key)));
    }
    if ((int)Tools::getValue('refund') == 1) {
        if (!$payplug->checkAmountToRefund(Tools::getValue('amount'))) {
            die(json_encode(array(
                'status' => 'error',
                'data' => $payplug->l('Incorrect amount to refund')
                //'data' => $this->getTranslator()->trans('Incorrect amount to refund', array(), 'Modules.Payplug.Admin')
            )));
        } else {
            $amount = str_replace(',', '.', Tools::getValue('amount'));
            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float)($amount / 10); // unless sometimes 17.90 become 17.89
            $amount = (int)$amount;
        }

        $id_order = Tools::getValue('id_order');
        $pay_id = Tools::getValue('pay_id');
        $inst_id = Tools::getValue('inst_id');
        $metadata = array(
            'ID Client' => (int)Tools::getValue('id_customer'),
            'reason' => 'Refunded with Prestashop'
        );
        $pay_mode = Tools::getValue('pay_mode');
        $refund = $payplug->makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
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
    if ((int)Tools::getValue('update') == 1) {
        $pay_id = Tools::getValue('pay_id');
        $payment = $this->retrievePayment($pay_id);
        $id_order = Tools::getValue('id_order');

        if ((int)$payment->is_paid == 1) {
            if ($payment->is_live == 1) {
                $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID');
            } else {
                $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST');
            }
        } elseif ((int)$payment->is_paid == 0) {
            if ($payment->is_live == 1) {
                $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR');
            } else {
                $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR_TEST');
            }
        }

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

        //$this->deletePayment($pay_id, $order->id_cart);

        die(json_encode(array(
            'message' => $this->l('Order successfully updated.'),
            'reload' => true
        )));
    }
} else {
    exit;
}
