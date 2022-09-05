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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use Configuration;
use Context;
use Exception;
use Order;
use OrderHistory;
use OrderSlip;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;
use Validate;

class RefundClass
{
    protected $context;
    private $dependencies;
    private $logger;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->context = \Context::getContext();
    }

    /**
     * Generate refund form
     * Done
     * @param int $amount_refunded_payplug
     * @param int $amount_available
     * @return string
     */
    public function getRefundData($amount_refunded_payplug, $amount_available)
    {
        $this->context->smarty->assign([
            'amount_refunded_payplug' => $amount_refunded_payplug,
            'amount_available' => $amount_available,
        ]);

        return $this->dependencies->configClass->fetchTemplate('/views/templates/admin//order/refund_data.tpl');
    }

    /**
     * Get total amount already refunded
     * @param $id_order
     * @return bool|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getTotalRefunded($id_order)
    {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        } else {
            $amount_refunded_presta = 0;
            $flag_shipping_refunded = false;

            $order_slips = OrderSlip::getOrdersSlip($order->id_customer, $order->id);
            if (isset($order_slips) && !empty($order_slips) && sizeof($order_slips)) {
                foreach ($order_slips as $order_slip) {
                    $amount_refunded_presta += $order_slip['amount'];
                    if (!$flag_shipping_refunded && $order_slip['shipping_cost'] == 1) {
                        $amount_refunded_presta += $order_slip['shipping_cost_amount'];
                        $flag_shipping_refunded = true;
                    }
                }
            }

            return $amount_refunded_presta;
        }
    }

    /**
     * @description Make a refund
     * @param $pay_id
     * @param $amount
     * @param $metadata
     * @param string $pay_mode
     * @param null $inst_id
     * @return mixed|string
     */
    public function makeRefund($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
    {
        $this->logger->setParams(['process' => 'refundClass']);

        $sandbox = Tools::strtoupper($pay_mode) == 'TEST';
        $this->dependencies->apiClass->initializeApi($sandbox);

        if ($pay_id == null) {
            if ($inst_id) {
                $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);
                if (!$installment['result']) {
                    $error = 'error [PayPlugClass - makeRefund()]: '
                        . 'Can\'t retrieve InstallmentPlan with given id: ' . $inst_id;
                    $this->logger->addLog($error, 'error');
                    return ('error');
                }
                $this->logger->addLog('[PayPlugClass - makeRefund()] Retrieve installment id: ' . $inst_id);
                $installment=$installment['resource'];
                if (isset($installment->schedule) && $installment->schedule) {
                    $total_amount = $amount;
                    $refund_to_go = [];
                    $truly_refundable_amount = 0;
                    foreach ($installment->schedule as $schedule) {
                        if (!empty($schedule->payment_ids)) {
                            foreach ($schedule->payment_ids as $p_id) {
                                $payment = $this->dependencies->apiClass->retrievePayment($p_id);
                                if (!$payment['result']) {
                                    return ('error');
                                }
                                $payment = $payment['resource'];
                                $this->logger->addLog('[PayPlugClass - makeRefund()] '
                                    . 'Retrieve payment id: ' . $payment->id);
                                if ($payment->is_paid && !$payment->is_refunded && $amount > 0) {
                                    $amount_refundable = (int)($payment->amount - $payment->amount_refunded);
                                    $truly_refundable_amount += $amount_refundable;
                                    if ($truly_refundable_amount < 10) {
                                        continue;
                                    } elseif ($amount >= $amount_refundable) {
                                        $data = [
                                            'amount' => $amount_refundable,
                                            'metadata' => $metadata
                                        ];
                                        $amount -= $amount_refundable;
                                    } else {
                                        $data = [
                                            'amount' => $amount,
                                            'metadata' => $metadata
                                        ];
                                        $amount = 0;
                                    }
                                    $refund_to_go[] = ['id' => $p_id, 'data' => $data];
                                }
                            }
                        }
                    }

                    if ($truly_refundable_amount < $total_amount) {
                        return ('error');
                    }

                    if (!empty($refund_to_go)) {
                        foreach ($refund_to_go as $ref) {
                            $response = $this->dependencies->apiClass->refundPayment($ref['id'], $ref['data']);
                            if (!$response['result']) {
                                return ('error');
                            }
                        }
                    }

                    $this->dependencies->installmentClass->updatePayplugInstallment($installment);
                } else {
                    return ('error');
                }
            } else {
                return ('error');
            }
        } else {
            $data = [
                'amount' => (int)$amount,
                'metadata' => $metadata
            ];

            $response = $this->dependencies->apiClass->refundPayment($pay_id, $data);
            if (!$response['result']) {
                return 'error';
            }
        }

        return $response['resource'];
    }

    /**
     * @description  Refund a payment
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function refundPayment()
    {
        $this->logger->addLog('[Payplug] Start refund', 'notice');
        $amount = str_replace(',', '.', Tools::getValue('amount'));

        if (!$this->dependencies->amountCurrencyClass->checkAmountToRefund($amount)) {
            $this->logger->addLog('Incorrect amount to refund', 'notice');
            die(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.refundPayment.incorrectAmount', 'refundclass')
            ]));
        } elseif ($this->dependencies->amountCurrencyClass->checkAmountToRefund($amount) && ($amount < 0.10)) {
            $this->logger->addLog('The amount to be refunded must be at least 0.10 €', 'notice');
            die(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.refundPayment.amountAtLeast', 'refundclass')
            ]));
        } else {
            $amount = str_replace(',', '.', Tools::getValue('amount'));
            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
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
        $refund = $this->makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
        if ($refund == 'error') {
            $this->logger->addLog('Cannot refund that amount.', 'notice');
            $this->logger->addLog(
                '$pay_id : ' . $pay_id .
                ' - $amount : ' . $amount .
                ' - $metadata : ' . json_encode($metadata) . /* or implode() ? */
                ' - $pay_mode : ' . $pay_mode .
                ' - $inst_id : ' . $inst_id,
                'debug'
            );

            die(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.refundPayment.cannotRefund', 'refundclass')
            ]));
        } else {
            $new_state = 7;
            $reload = false;

            if ($inst_id) {
                $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);
                if (!$installment['result']) {
                    $this->logger->addLog('Cannot retrieve installment with id: ' . $inst_id, 'error');
                    die(json_encode([
                        'status' => 'error',
                        'data' => $this->dependencies->l('payplug.refundPayment.errorOccurred', 'refundclass')
                    ]));
                }

                $installment = $installment['resource'];
                $amount_available = 0;
                $amount_refunded_payplug = 0;
                if (isset($installment->schedule) && $installment->schedule) {
                    foreach ($installment->schedule as $schedule) {
                        if (!empty($schedule->payment_ids)) {
                            foreach ($schedule->payment_ids as $p_id) {
                                $payment = $this->dependencies->apiClass->retrievePayment($p_id);
                                if (!$payment['result']) {
                                    return ('error');
                                }
                                $payment = $payment['resource'];
                                if ($payment->is_paid && !$payment->is_refunded) {
                                    $amount_available += (int)($payment->amount - $payment->amount_refunded);
                                }
                                $amount_refunded_payplug += $payment->amount_refunded;
                            }
                        }
                    }
                }

                $amount_available = (float)($amount_available / 100);
                $amount_refunded_payplug = (float)($amount_refunded_payplug / 100);

                if ((int)Tools::getValue('id_state') || !$amount_available) {
                    $new_state = (int)Tools::getValue('id_state');
                    if (!$new_state) {
                        if ($installment->is_live == 1) {
                            $new_state = (int)Configuration::get(
                                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND')
                            );
                        } else {
                            $new_state = (int)Configuration::get(
                                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND_TEST')
                            );
                        }
                    }
                    $order = new Order((int)$id_order);
                    if (Validate::isLoadedObject($order)) {
                        if (!$this->dependencies->cartClass->createLockFromCartId((int)$order->id_cart)) {
                            die(json_encode([
                                'status' => 'error',
                                'data' => $this->dependencies->l('payplug.refundPayment.errorOccurred', 'refundclass')
                            ]));
                        }

                        $current_state = (int)$this->dependencies->orderClass->getCurrentOrderState($order->id);
                        $this->logger->addLog('Current order state: ' . $current_state, 'notice');
                        if ($current_state != 0 && $current_state != $new_state) {
                            $history = new OrderHistory();
                            $history->id_order = (int)$order->id;
                            $history->changeIdOrderState($new_state, (int)$order->id, true);
                            $history->addWithemail();
                            $this->logger->addLog('Change order state to ' . $new_state, 'notice');
                        }

                        if (!$this->dependencies->cartClass->deleteLockFromCartId((int)$order->id_cart)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'notice');
                        }
                    }
                    $reload = true;
                }
            } else {
                $payment = $this->dependencies->apiClass->retrievePayment($refund->payment_id);
                if (!$payment['result']) {
                    die(json_encode([
                        'status' => 'error',
                        'data' => $this->dependencies->l('payplug.refundPayment.errorOccurred', 'refundclass')
                    ]));
                }
                $payment = $payment['resource'];

                if ((int)Tools::getValue('id_state') != 0) {
                    $new_state = (int)Tools::getValue('id_state');
                } elseif ($payment->is_refunded == 1) {
                    if ($payment->is_live == 1) {
                        $new_state = (int)Configuration::get(
                            $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND')
                        );
                    } else {
                        $new_state = (int)Configuration::get(
                            $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND_TEST')
                        );
                    }
                }
                if ((int)Tools::getValue('id_state') != 0 || ($payment->is_refunded == 1 && empty($inst_id))) {
                    $order = new Order((int)$id_order);
                    if (Validate::isLoadedObject($order)) {
                        if (!$this->dependencies->cartClass->createLockFromCartId((int)$order->id_cart)) {
                            die(json_encode([
                                'status' => 'error',
                                'data' => $this->dependencies->l('payplug.refundPayment.errorOccurred', 'refundclass')
                            ]));
                        }

                        $current_state = (int)$this->dependencies->orderClass->getCurrentOrderState($order->id);
                        $this->logger->addLog('Current order state: ' . $current_state, 'notice');
                        if ($current_state != 0 && $current_state != $new_state) {
                            $history = new OrderHistory();
                            $history->id_order = (int)$order->id;
                            $history->changeIdOrderState($new_state, (int)$order->id, true);
                            $history->addWithemail();
                            $this->logger->addLog('Change order state to ' . $new_state, 'notice');
                        } else {
                            $this->logger->addLog('Order status is already \'refunded\'', 'notice');
                        }

                        if (!$this->dependencies->cartClass->deleteLockFromCartId((int)$order->id_cart)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'notice');
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
            die(json_encode([
                'status' => 'ok',
                'data' => $data,
                'template' => $this->dependencies->hookClass->displayAdminOrderMain(['id_order' => $id_order]),
                'message' => $this->dependencies->l('payplug.refundPayment.success', 'refundclass'),
                'reload' => $reload
            ]));
        }
    }
}
