<?php

use PayPlug\classes\RefundClass;

/**
 * 2013 - 2021 PayPlug SAS.
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
 *  @copyright 2013 - 2021 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
class PayplugAjaxModuleAdminController extends ModuleAdminController
{
}

require_once _PS_ROOT_DIR_.'/config/config.inc.php';

include_once _PS_MODULE_DIR_.'payplug/classes/AdminClass.php';

include_once _PS_MODULE_DIR_.'payplug/classes/PayPlugClass.php';

$payplug = new PayPlugClass();
$adminClass = new AdminClass();
$refundClass = new RefundClass($payplug);
$logger = $payplug->getPlugin()->logger();

if (1 == Tools::getValue('_ajax')) {
    if (1 == (int) Tools::getValue('en') && 0 == (int) Configuration::get('PAYPLUG_SHOW')) {
        Configuration::updateValue('PAYPLUG_SHOW', 1);
        $payplug->enable();

        exit(true);
    }
    if (Tools::getIsset('en')
        && 0 == (int) Tools::getValue('en')
        && 1 == (int) Configuration::get('PAYPLUG_SHOW')
    ) {
        Configuration::updateValue('PAYPLUG_SHOW', 0);

        exit(true);
    }
    if (Tools::getIsset('db')) {
        if ('on' == Tools::getValue('db')) {
            Configuration::updateValue('PAYPLUG_DEBUG_MODE', 1);
        } elseif ('off' == Tools::getValue('db')) {
            Configuration::updateValue('PAYPLUG_DEBUG_MODE', 0);
        }

        exit(true);
    }
    if (1 == (int) Tools::getValue('popin')) {
        $logger->addLog('Popin OK', 'notice');
        $args = null;
        if ('confirm' == Tools::getValue('type')) {
            $sandbox = (int) Tools::getValue('sandbox');
            $embedded = (int) Tools::getValue('embedded');
            $one_click = (int) Tools::getValue('one_click');
            $installment = (int) Tools::getValue('installment');
            $deferred = (int) Tools::getValue('deferred');
            $activate = (int) Tools::getValue('activate');
            $args = [
                'sandbox' => $sandbox,
                'embedded' => $embedded,
                'one_click' => $one_click,
                'installment' => $installment,
                'deferred' => $deferred,
                'activate' => $activate,
            ];
        }
        $payplug->displayPopin(Tools::getValue('type'), $args);
    }
    if ('submitPopin_pwd' == Tools::getValue('submit')) {
        /*
         * We have to have $_POST on PrestaShop 1.6 and 1.7,
         * otherwise Tools::getValue() transforms the password,
         * and in particular escapes backslashes,
         * so the password is no longer the one entered by the user
         */
        $adminClass->submitPopinPwd($_POST['pwd']);
    }
    if (Tools::getValue('has_live_key')) {
        exit(Tools::jsonEncode(['result' => \PayPlug\classes\ApiClass::hasLiveKey()]));
    }
    if ('submitPopin_confirm' == Tools::getValue('submit')) {
        exit(json_encode(['content' => 'confirm_ok']));
    }
    if ('submitPopin_confirm_a' == Tools::getValue('submit')) {
        exit(json_encode(['content' => 'confirm_ok_activate']));
    }
    if ('submitPopin_deactivate' == Tools::getValue('submit')) {
        exit(json_encode(['content' => 'confirm_ok_deactivate']));
    }
    if ('submitPopin_abort' == Tools::getValue('submit')) {
        exit(json_encode(['content' => '']));
    }
    if (1 == (int) Tools::getValue('check')) {
        $content = $payplug->configClass->getCheckFieldset();

        exit(json_encode(['content' => $content]));
    }
    if (1 == (int) Tools::getValue('log')) {
        $content = $adminClass->getLogin();

        exit(json_encode(['content' => $content]));
    }
    if (1 == (int) Tools::getValue('checkPremium')) {
        $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');

        exit(json_encode(\PayPlug\classes\ApiClass::getAccountPermissions($api_key)));
    }
    if (1 == (int) Tools::getValue('refund')) {
        $logger->addLog('[Ajax] Start refund', 'notice');
        $amount = Tools::getValue('amount');
        if (!$payplug->checkAmountToRefund($amount)) {
            exit(json_encode([
                'status' => 'error',
                'data' => $payplug->l('Incorrect amount to refund'),
            ]));
        }
        if ($payplug->checkAmountToRefund($amount) && ($amount < 0.10)) {
            exit(json_encode([
                'status' => 'error',
                'data' => $payplug->l('The amount to be refunded must be at least 0.10 €'),
            ]));
        }
        $amount = str_replace(',', '.', Tools::getValue('amount'));
        $amount = (float) ($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float) ($amount / 10); // unless sometimes 17.90 become 17.89
            $amount = (int) $amount;

        $id_order = Tools::getValue('id_order');
        $pay_id = Tools::getValue('pay_id');
        $inst_id = Tools::getValue('inst_id');
        $metadata = [
            'ID Client' => (int) Tools::getValue('id_customer'),
            'reason' => 'Refunded with Prestashop',
        ];
        $pay_mode = Tools::getValue('pay_mode');
        $refund = RefundClass::makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
        if ('error' == $refund) {
            $logger->addLog('Cannot refund that amount.', 'notice');
            $logger->addLog(
                '$pay_id : '.$pay_id.
                ' - $amount : '.$amount.
                ' - $metadata : '.json_encode($metadata).
                ' - $pay_mode : '.$pay_mode.
                ' - $inst_id : '.$inst_id,
                'debug'
            );

            exit(json_encode([
                'status' => 'error',
                'data' => $payplug->l('Cannot refund that amount.'),
            ]));
        }
        $payment = $payplug->retrievePayment($pay_id);
        $new_state = 7;
        if (0 != (int) Tools::getValue('id_state')) {
            $new_state = (int) Tools::getValue('id_state');
        } elseif (1 == $payment->is_refunded) {
            if (1 == $payment->is_live) {
                $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
            } else {
                $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
            }
        }

        $reload = false;
        if (0 != (int) Tools::getValue('id_state') || 1 == $payment->is_refunded) {
            $order = new Order((int) $id_order);
            if (Validate::isLoadedObject($order)) {
                if (!$payplug->createLockFromCartId($order->id_cart)) {
                    exit(json_encode([
                        'status' => 'error',
                        'data' => $payplug->l('An error has occurred'),
                    ]));
                }

                $current_state = (int) $payplug->orderClass->getCurrentOrderState($order->id);
                $logger->addLog('Current order state: '.$current_state, 'notice');
                if (0 != $current_state && $current_state != $new_state) {
                    $history = new OrderHistory();
                    $history->id_order = (int) $order->id;
                    $history->changeIdOrderState($new_state, (int) $order->id);
                    $history->addWithemail();
                    $logger->addLog('Change order state to '.$new_state, 'notice');
                }

                if (!$payplug->deleteLockFromCartId($order->id_cart)) {
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

        exit(json_encode([
            'status' => 'ok',
            'data' => $data,
            'message' => $payplug->l('Amount successfully refunded.'),
            'reload' => $reload,
        ]));
    }
    if (1 == (int) Tools::getValue('popinRefund')) {
        $popin = $payplug->displayPopin('refund');

        exit(json_encode(['content' => $popin]));
    }
    if (1 == (int) Tools::getValue('update')) {
        $pay_id = Tools::getValue('pay_id');
        $payment = $payplug->retrievePayment($pay_id);
        $id_order = Tools::getValue('id_order');

        if (1 == (int) $payment->is_paid) {
            if (1 == $payment->is_live) {
                $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_PAID');
            } else {
                $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST');
            }
        } elseif (0 == (int) $payment->is_paid) {
            if (1 == $payment->is_live) {
                $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_ERROR');
            } else {
                $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_ERROR_TEST');
            }
        }

        $order = new Order((int) $id_order);
        if (Validate::isLoadedObject($order)) {
            $current_state = (int) $order->getCurrentState();
            if (0 != $current_state && $current_state != $new_state) {
                $history = new OrderHistory();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState($new_state, (int) $order->id);
                $history->addWithemail();
            }
        }

        //$this->deletePayment($pay_id, $order->id_cart);

        exit(json_encode([
            'message' => $payplug->l('Order successfully updated.'),
            'reload' => true,
        ]));
    }
} else {
    exit;
}
