<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RefundAction
{
    private $tools;
    private $validators;
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function refundAction($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
    {
        $this->setParameters();
        $sandbox = $this->isSandboxMode($pay_mode);
        $this->dependencies->apiClass->initializeApi($sandbox);

        if (null == $pay_id) {
            return $inst_id ? $this->processInstallmentRefund($inst_id, $amount, $metadata) : 'error';
        }

        return $this->processPaymentRefund($pay_id, $amount, $metadata);
    }

    private function isSandboxMode($pay_mode)
    {
        return 'TEST' == $this->tools->tool('strtoupper', $pay_mode);
    }

    // Helper function to process refunds for each payment in the installment
    private function processInstallmentRefund($inst_id, &$amount, $metadata)
    {
        $installment = $this->getInstallment($inst_id);

        if (!$installment) {
            return 'error';
        }

        $this->logger->addLog('[PayPlugClass - makeRefund()] Retrieve installment id: ' . $inst_id);

        $refundablePayments = $this->getRefundablePayments($installment, $amount, $metadata);

        if ($this->validators['payment']->canBeRefund($inst_id, $refundablePayments)['result']) {
            $this->performRefunds($refundablePayments);
            $this->dependencies->installmentClass->updatePayplugInstallment($installment);
        } else {
            return 'error';
        }

        return $refundablePayments[0]['response']['resource'];
    }

    // Helper function to retrieve installment details
    private function getInstallment($inst_id)
    {
        $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);

        if (!$installment['result']) {
            $error = 'error [RefundAction - getInstallment()]: Can\'t retrieve InstallmentPlan with given id: ' . $inst_id;
            $this->logger->addLog($error, 'error');

            return null;
        }

        return $installment['resource'];
    }

    private function getRefundablePayments($installment, &$amount, $metadata)
    {
        $refundablePayments = [];

        foreach ($installment->schedule as $schedule) {
            if (!empty($schedule->payment_ids)) {
                foreach ($schedule->payment_ids as $p_id) {
                    $refundData = $this->processPaymentRefund($p_id, $amount, $metadata);

                    if ($refundData) {
                        $refundablePayments[] = $refundData;
                    }
                }
            }
        }

        return $refundablePayments;
    }

    // Helper function to process refund for a single payment
    private function processPaymentRefund($p_id, &$amount, $metadata)
    {
        $payment = $this->dependencies->apiClass->retrievePayment($p_id);

        if (!$payment['result']) {
            return null;
        }

        $payment = $payment['resource'];

        $this->logger->addLog('[PayPlugClass - makeRefund()] Retrieve payment id: ' . $payment->id);

        if ($payment->is_paid && !$payment->is_refunded && $amount > 0) {
            $amountRefundable = (int) ($payment->amount - $payment->amount_refunded);
            $trulyRefundableAmount = min($amount, $amountRefundable);

            if ($trulyRefundableAmount > 0) {
                $amount -= $trulyRefundableAmount;

                return [
                    'id' => $p_id,
                    'data' => [
                        'amount' => $trulyRefundableAmount,
                        'metadata' => $metadata,
                    ],
                    'response' => $this->dependencies->apiClass->refundPayment(
                        $p_id,
                        [
                            'amount' => $trulyRefundableAmount,
                            'metadata' => $metadata,
                        ]
                    ),
                ];
            }
        }

        return null;
    }

    // Helper function to perform refunds for each payment
    private function performRefunds($refundablePayments)
    {
        foreach ($refundablePayments as $refund) {
            if (!$refund['response']['result']) {
                return 'error';
            }
        }
    }

    /**
     * @description Set needed object from dependencies
     */
    private function setParameters()
    {
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validators = $this->dependencies->getValidators();
    }
}
