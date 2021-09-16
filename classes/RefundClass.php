<?php

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
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use Configuration;
use Exception;
use Order;
use OrderHistory;
use OrderSlip;
use Payplug\Exception\ConfigurationException;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\InstallmentPlan;
use Payplug\Payment;
use Payplug\Resource\Refund;
use PayPlug\src\repositories\LoggerRepository;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;
use Validate;

class RefundClass extends \PaymentModule
{
    protected $context;
    private $payplug;

    public function __construct($payplug)
    {
        parent::__construct();
        $this->payplug = $payplug;
        $this->context = \Context::getContext();
    }

    /**
     * Generate refund form
     * Done.
     *
     * @param int $amount_refunded_payplug
     * @param int $amount_available
     *
     * @return string
     */
    public function getRefundData($amount_refunded_payplug, $amount_available)
    {
        $this->context->smarty->assign([
            'amount_refunded_payplug' => $amount_refunded_payplug,
            'amount_available' => $amount_available,
        ]);

        return $this->payplug->fetchTemplate('/views/templates/admin//order/refund_data.tpl');
    }

    /**
     * Get total amount already refunded.
     *
     * @param $id_order
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return bool|int
     */
    public static function getTotalRefunded($id_order)
    {
        $order = new Order((int) $id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }
        $amount_refunded_presta = 0;
        $flag_shipping_refunded = false;

        $order_slips = OrderSlip::getOrdersSlip($order->id_customer, $order->id);
        if (isset($order_slips) && !empty($order_slips) && sizeof($order_slips)) {
            foreach ($order_slips as $order_slip) {
                $amount_refunded_presta += $order_slip['amount'];
                if (!$flag_shipping_refunded && 1 == $order_slip['shipping_cost']) {
                    $amount_refunded_presta += $order_slip['shipping_cost_amount'];
                    $flag_shipping_refunded = true;
                }
            }
        }

        return $amount_refunded_presta;
    }

    /**
     * Make a refund.
     *
     * @param $pay_id
     * @param $amount
     * @param $metadata
     * @param string $pay_mode
     * @param null   $inst_id
     *
     * @throws ConfigurationException
     *
     * @return Refund|string
     */
    public static function makeRefund($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
    {
        $logger = new LoggerRepository();
        $logger->setParams(['process' => 'refundClass']);
        if ('TEST' == Tools::strtoupper($pay_mode)) {
            ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
        } else {
            ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
        }
        if (null == $pay_id) {
            if (null != $inst_id) {
                try {
                    $installment = InstallmentPlan::retrieve($inst_id);
                    if (isset($installment->schedule)) {
                        $total_amount = $amount;
                        $refund_to_go = [];
                        $truly_refundable_amount = 0;
                        foreach ($installment->schedule as $schedule) {
                            if (!empty($schedule->payment_ids)) {
                                foreach ($schedule->payment_ids as $p_id) {
                                    $payment = Payment::retrieve($p_id);
                                    if ($payment->is_paid && !$payment->is_refunded && $amount > 0) {
                                        $amount_refundable = (int) ($payment->amount - $payment->amount_refunded);
                                        $truly_refundable_amount += $amount_refundable;
                                        if ($truly_refundable_amount < 10) {
                                            continue;
                                        }
                                        if ($amount >= $amount_refundable) {
                                            $data = [
                                                'amount' => $amount_refundable,
                                                'metadata' => $metadata,
                                            ];
                                            $amount -= $amount_refundable;
                                        } else {
                                            $data = [
                                                'amount' => $amount,
                                                'metadata' => $metadata,
                                            ];
                                            $amount = 0;
                                        }
                                        $refund_to_go[] = ['id' => $p_id, 'data' => $data];
                                    }
                                }
                            }
                        }
                        if ($truly_refundable_amount < $total_amount) {
                            return 'error';
                        }
                        if (!empty($refund_to_go)) {
                            foreach ($refund_to_go as $refnd) {
                                try {
                                    $refund = Refund::create($refnd['id'], $refnd['data']);
                                } catch (Exception $e) {
                                    return 'error';
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    return 'error';
                }
                InstallmentClass::updatePayplugInstallment($installment);
            } else {
                return 'error';
            }
        } else {
            $data = [
                'amount' => (int) $amount,
                'metadata' => $metadata,
            ];

            try {
                $refund = Refund::create($pay_id, $data);
            } catch (Exception $e) {
                $error = 'error [PayPlugClass - makeRefund()]: '.$e->getMessage();
                $logger->addLog($error, 'error');

                return 'error';
            }
        }

        return $refund;
    }

    /**
     * @description  Refund a payment
     *
     * @throws ConfigurationException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ConfigurationNotSetException
     */
    public function refundPayment()
    {
        $this->payplug->logger->addLog('[Payplug] Start refund', 'notice');
        $amount = Tools::getValue('amount');

        if (!$this->payplug->amountCurrencyClass->checkAmountToRefund($amount)) {
            $this->payplug->logger->addLog('Incorrect amount to refund', 'notice');

            exit(json_encode([
                'status' => 'error',
                'data' => $this->payplug->l('payplug.refundPayment.incorrectAmount'),
            ]));
        }
        if ($this->payplug->amountCurrencyClass->checkAmountToRefund($amount) && ($amount < 0.10)) {
            $this->payplug->logger->addLog('The amount to be refunded must be at least 0.10 €', 'notice');

            exit(json_encode([
                'status' => 'error',
                'data' => $this->payplug->l('payplug.refundPayment.amountAtLeast'),
            ]));
        }
        $amount = str_replace(',', '.', Tools::getValue('amount'));
        $amount = (float) ($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float) ($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
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
            $this->payplug->logger->addLog('Cannot refund that amount.', 'notice');
            $this->payplug->logger->addLog(
                '$pay_id : '.$pay_id.
                ' - $amount : '.$amount.
                ' - $metadata : '.json_encode($metadata). // or implode() ?
                ' - $pay_mode : '.$pay_mode.
                ' - $inst_id : '.$inst_id,
                'debug'
            );

            exit(json_encode([
                'status' => 'error',
                'data' => $this->payplug->l('payplug.refundPayment.cannotRefund'),
            ]));
        }
        $new_state = 7;
        $reload = false;

        if (null != $inst_id) {
            $installment = InstallmentClass::retrieveInstallment($inst_id);
            $amount_available = 0;
            $amount_refunded_payplug = 0;
            if (isset($installment->schedule)) {
                foreach ($installment->schedule as $schedule) {
                    if (!empty($schedule->payment_ids)) {
                        foreach ($schedule->payment_ids as $p_id) {
                            $payment = Payment::retrieve($p_id);
                            if ($payment->is_paid && !$payment->is_refunded) {
                                $amount_available += (int) ($payment->amount - $payment->amount_refunded);
                            }
                            $amount_refunded_payplug += $payment->amount_refunded;
                        }
                    }
                }
            }
            $amount_available = (float) ($amount_available / 100);
            $amount_refunded_payplug = (float) ($amount_refunded_payplug / 100);
            if (0 != (int) Tools::getValue('id_state') || 0 == $amount_available) {
                $new_state = (int) Tools::getValue('id_state');
                if (0 == $new_state) {
                    if (1 == $installment->is_live) {
                        $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                    } else {
                        $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                    }
                }
                $order = new Order((int) $id_order);
                if (Validate::isLoadedObject($order)) {
                    if (!$this->payplug->createLockFromCartId($order->id_cart)) {
                        exit(json_encode([
                            'status' => 'error',
                            'data' => $this->payplug->l('payplug.refundPayment.errorOccurred'),
                        ]));
                    }

                    $current_state = (int) $this->payplug->orderClass->getCurrentOrderState($order->id);
                    $this->payplug->logger->addLog('Current order state: '.$current_state, 'notice');
                    if (0 != $current_state && $current_state != $new_state) {
                        $history = new OrderHistory();
                        $history->id_order = (int) $order->id;
                        $history->changeIdOrderState($new_state, (int) $order->id);
                        $history->addWithemail();
                        $this->payplug->logger->addLog('Change order state to '.$new_state, 'notice');
                    }

                    if (!$this->payplug->deleteLockFromCartId($order->id_cart)) {
                        $this->payplug->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->payplug->logger->addLog('Lock deleted.', 'notice');
                    }
                }
                $reload = true;
            }
        } else {
            //TODO: call retrievePayment from PaymentClass
            $payment = $this->payplug->retrievePayment($refund->payment_id);

            if (0 != (int) Tools::getValue('id_state')) {
                $new_state = (int) Tools::getValue('id_state');
            } elseif (1 == $payment->is_refunded) {
                if (1 == $payment->is_live) {
                    $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                } else {
                    $new_state = (int) Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                }
            }
            if (0 != (int) Tools::getValue('id_state') || (1 == $payment->is_refunded && empty($inst_id))) {
                $order = new Order((int) $id_order);
                if (Validate::isLoadedObject($order)) {
                    if (!$this->payplug->createLockFromCartId($order->id_cart)) {
                        exit(json_encode([
                            'status' => 'error',
                            'data' => $this->l('payplug.refundPayment.errorOccurred'),
                        ]));
                    }

                    $current_state = (int) $this->payplug->orderClass->getCurrentOrderState($order->id);
                    $this->payplug->logger->addLog('Current order state: '.$current_state, 'notice');
                    if (0 != $current_state && $current_state != $new_state) {
                        $history = new OrderHistory();
                        $history->id_order = (int) $order->id;
                        $history->changeIdOrderState($new_state, (int) $order->id);
                        $history->addWithemail();
                        $this->payplug->logger->addLog('Change order state to '.$new_state, 'notice');
                    } else {
                        $this->payplug->logger->addLog('Order status is already \'refunded\'', 'notice');
                    }

                    if (!$this->payplug->deleteLockFromCartId($order->id_cart)) {
                        $this->payplug->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->payplug->logger->addLog('Lock deleted.', 'notice');
                    }
                }
                $reload = true;
            }

            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available = ($payment->amount - $payment->amount_refunded) / 100;
        }

        $data = $this->getRefundData(
            $amount_refunded_payplug,
            $amount_available
        );

        exit(json_encode([
            'status' => 'ok',
            'data' => $data,
            //TODO: call hookDisplayAdminOrderMain from HookClass
            'template' => $this->payplug->hookDisplayAdminOrderMain(['id_order' => $id_order]),
            'message' => $this->payplug->l('payplug.refundPayment.success'),
            'reload' => $reload,
        ]));
    }
}
