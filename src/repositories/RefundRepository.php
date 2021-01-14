<?php

namespace PayPlug\src\repositories;

class RefundRepository extends Repository
{
    /**
     * Generate refund form
     *
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

        $this->html = $this->fetchTemplateRC('/views/templates/admin//order/refund_data.tpl');

        return $this->html;
    }

    /**
     * Get total amount already refunded
     *
     * @param $id_order
     * @return bool|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getTotalRefunded($id_order)
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
     * Make a refund
     *
     * @param string $pay_id
     * @param int $amount
     * @param string $metadata
     * @param string $pay_mode
     * @param null $inst_id
     * @return string
     * @throws \Payplug\Exception\ConfigurationException
     * @throws ConfigurationNotSetException
     */
    public function makeRefund($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
    {
        if ($pay_mode == 'TEST') {
            $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
        } else {
            $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
        }
        if ($pay_id == null) {
            if ($inst_id != null) {
                try {
                    $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
                    if (isset($installment->schedule)) {
                        $total_amount = $amount;
                        $refund_to_go = [];
                        $truly_refundable_amount = 0;
                        foreach ($installment->schedule as $schedule) {
                            if (!empty($schedule->payment_ids)) {
                                foreach ($schedule->payment_ids as $p_id) {
                                    $p = \Payplug\Payment::retrieve($p_id);
                                    if ($p->is_paid && !$p->is_refunded && $amount > 0) {
                                        $amount_refundable = (int)($p->amount - $p->amount_refunded);
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
                            foreach ($refund_to_go as $refnd) {
                                try {
                                    $refund = \Payplug\Refund::create($refnd['id'], $refnd['data']);
                                } catch (Exception $e) {
                                    return ('error');
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    return ('error');
                }
                $this->updatePayplugInstallment($installment);
            } else {
                return ('error');
            }
        } else {
            $data = [
                'amount' => (int)$amount,
                'metadata' => $metadata
            ];

            try {
                $refund = \Payplug\Refund::create($pay_id, $data);
            } catch (Exception $e) {
                return ('error');
            }
        }

        return $refund;
    }
}
