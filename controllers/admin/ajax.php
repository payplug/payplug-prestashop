<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PayPlug\classes\RefundClass;

class PayplugAjaxModuleAdminController extends ModuleAdminController
{
}

require_once(_PS_ROOT_DIR_.'/config/config.inc.php');
include_once(_PS_MODULE_DIR_.'payplug/classes/AdminClass.php');
include_once(_PS_MODULE_DIR_.'payplug/classes/PayPlugClass.php');

$payplug = new PayPlugClass();
$adminClass = new AdminClass();
$cartClass = new CartClass();
$refundClass = new RefundClass($payplug);
$paymentClass = new PaymentClass();
$logger = $payplug->getPlugin()->logger();

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
        $logger->addLog('Popin OK', 'notice');
        $args = null;
        if (Tools::getValue('type') == 'confirm') {
            $sandbox = (int)Tools::getValue('sandbox');
            $embedded = (string)Tools::getValue('embedded');
            $one_click = (int)Tools::getValue('one_click');
            $installment = (int)Tools::getValue('installment');
            $bancontact = (int)Tools::getValue('bancontact');
            $deferred = (int)Tools::getValue('deferred');
            $activate = (int)Tools::getValue('activate');
            $args = [
                'sandbox' => $sandbox,
                'embedded' => $embedded,
                'one_click' => $one_click,
                'bancontact' => $bancontact,
                'installment' => $installment,
                'deferred' => $deferred,
                'activate' => $activate,
            ];
        }
        $payplug->displayPopin(Tools::getValue('type'), $args);
    }
    if (Tools::getValue('submit') == 'submitPopin_pwd') {
        /*
         * We have to have $_POST on PrestaShop 1.6 and 1.7,
         * otherwise Tools::getValue() transforms the password,
         * and in particular escapes backslashes,
         * so the password is no longer the one entered by the user
         */
        $adminClass->submitPopinPwd($_POST['pwd']);
    }
    if (Tools::getValue('has_live_key')) {
        die(Tools::jsonEncode(['result' => \PayPlug\classes\ApiClass::hasLiveKey()]));
    }
    if (Tools::getValue('submit') == 'submitPopin_confirm') {
        die(json_encode(['content' => 'confirm_ok']));
    }
    if (Tools::getValue('submit') == 'submitPopin_confirm_a') {
        die(json_encode(['content' => 'confirm_ok_activate']));
    }
    if (Tools::getValue('submit') == 'submitPopin_deactivate') {
        die(json_encode(['content' => 'confirm_ok_deactivate']));
    }
    if (Tools::getValue('submit') == 'submitPopin_abort') {
        die(json_encode(['content' => '']));
    }
    if ((int)Tools::getValue('check') == 1) {
        $content = $payplug->configClass->getCheckFieldset();
        die(json_encode(['content' => $content]));
    }
    if ((int)Tools::getValue('log') == 1) {
        $content = $adminClass->getLogin();
        die(json_encode(['content' => $content]));
    }
    if ((int)Tools::getValue('checkPremium') == 1) {
        $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
        die(json_encode(\PayPlug\classes\ApiClass::getAccountPermissions($api_key)));
    }
    if ((int)Tools::getValue('refund') == 1) {
        $logger->addLog('[Ajax] Start refund', 'notice');
        $amount = str_replace(',', '.', Tools::getValue('amount'));
        if (!$payplug->checkAmountToRefund($amount)) {
            die(json_encode([
                'status' => 'error',
                'data' => $payplug->l('Incorrect amount to refund')
            ]));
        } elseif ($payplug->checkAmountToRefund($amount) && ($amount < 0.10)) {
            die(json_encode([
                'status' => 'error',
                'data' => $payplug->l('The amount to be refunded must be at least 0.10 €')
            ]));
        } else {
            $amount = str_replace(',', '.', Tools::getValue('amount'));
            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float)($amount / 10); // unless sometimes 17.90 become 17.89
            $amount = (int)$amount;
        }

        $id_order = Tools::getValue('id_order');
        $pay_id = Tools::getValue('pay_id');
        $inst_id = Tools::getValue('inst_id');
        $metadata = [
            'ID Client' => (int)Tools::getValue('id_customer'),
            'reason' => 'Refunded with Prestashop'
        ];
        $pay_mode = Tools::getValue('pay_mode');
        $refund = RefundClass::makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
        if ($refund == 'error') {
            $logger->addLog('Cannot refund that amount.', 'notice');
            $logger->addLog(
                '$pay_id : '.$pay_id.
                ' - $amount : '.$amount.
                ' - $metadata : '.json_encode($metadata).
                ' - $pay_mode : '.$pay_mode.
                ' - $inst_id : '.$inst_id,
                'debug'
            );
            die(json_encode([
                'status' => 'error',
                'data' => $payplug->l('Cannot refund that amount.')
            ]));
        } else {
            $payment = $paymentClass->retrievePayment($pay_id);
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
                    if (!$cartClass->createLockFromCartId($order->id_cart)) {
                        die(json_encode([
                            'status' => 'error',
                            'data' => $payplug->l('An error has occurred')
                        ]));
                    }

                    $current_state = (int)$payplug->orderClass->getCurrentOrderState($order->id);
                    $logger->addLog('Current order state: ' . $current_state, 'notice');
                    if ($current_state != 0 && $current_state != $new_state) {
                        $history = new OrderHistory();
                        $history->id_order = (int)$order->id;
                        $history->changeIdOrderState($new_state, (int)$order->id);
                        $history->addWithemail();
                        $logger->addLog('Change order state to ' . $new_state, 'notice');
                    }

                    if (!$cartClass->deleteLockFromCartId($order->id_cart)) {
                        $logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $logger->addLog('Lock deleted.', 'notice');
                    }
                }
                $reload = true;
            }

            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available = ($payment->amount - $payment->amount_refunded) / 100;

            $data = $refundClass->getRefundData(
                $amount_refunded_payplug,
                $amount_available
            );
            $logger->addLog('Amount successfully refunded.', 'notice');
            die(json_encode([
                'status' => 'ok',
                'data' => $data,
                'message' => $payplug->l('Amount successfully refunded.'),
                'reload' => $reload
            ]));
        }
    }
    if ((int)Tools::getValue('popinRefund') == 1) {
        $popin = $payplug->displayPopin('refund');
        die(json_encode(['content' => $popin]));
    }
    if ((int)Tools::getValue('update') == 1) {
        $pay_id = Tools::getValue('pay_id');
        $payment = $paymentClass->retrievePayment($pay_id);
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

        die(json_encode([
            'message' => $payplug->l('Order successfully updated.'),
            'reload' => true
        ]));
    }
} else {
    exit;
}
