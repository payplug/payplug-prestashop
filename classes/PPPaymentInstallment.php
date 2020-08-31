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

if (!defined('_PS_VERSION_')) {
    exit;
}

class PPPaymentInstallment extends PPPayment
{
    public function __construct($id = null)
    {
        if ($id != null) {
            $payment = $this->retrieve($id);
            $this->populateFromInstallment($payment);
        } else {
            $id = null;
            $resource = null;
        }
    }

    public function retrieve($id)
    {
        try {
            $payment = \Payplug\InstallmentPlan::retrieve($id);
        } catch (\Payplug\Exception $e) {
            $data = array(
                'result' => false,
                'response' => $e->__toString(),
            );
            return $data;
        }
        return $payment;
    }

    private function populateFromInstallment($installment)
    {
        $this->resource = $installment;
    }

    public function getPaymentList()
    {
        $list = array();
        $index = 0;
        foreach ($this->resource->schedule as $schedule) {
            if (count($schedule->payment_ids) > 0) {
                foreach ($schedule->payment_ids as $pay_id) {
                    $list[$index] = array(
                        'pay_id' => $pay_id,
                        'date' => $schedule->date,
                        'amount' => $schedule->amount
                    );
                    $index ++;
                }
            }
        }
        return $list;
    }

    public function getFirstPayment()
    {
        $payment_list = $this->getPaymentList();
        if (count($payment_list) > 0) {
            $payment = new PPPayment($payment_list[0]['pay_id']);
            return $payment;
        }
    }

    public function isDeferred()
    {
        $payment_list = $this->getPaymentList();
        if (count($payment_list) > 0) {
            $payment = new PPPayment($payment_list[0]['pay_id']);
            return $payment->isDeferred();
        }
        return false;
    }
}
