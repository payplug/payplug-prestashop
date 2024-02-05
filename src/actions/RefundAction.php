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
    private $dependencies;
    private $logger;
    private $plugin;
    private $tools;
    private $validators;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Processes a refund for a payment or an installment.
     * This method initializes the API, checks the arguments,
     * and either processes a payment refund
     * or initiates the refund for each payment in the installment.
     *
     * @param $pay_id
     * @param $amount
     * @param $metadata
     * @param string $pay_mode
     * @param null $inst_id
     *
     * @return null|array|mixed|string
     */
    public function refundAction($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
    {
        $this->setParameters();

        if (!is_string($pay_mode)) {
            $this->logger->addLog(
                'RefundAction::refundAction - Invalid argument, $pay_mode must be a string.',
                'error'
            );

            return 'error';
        }

        $sandbox = 'TEST' == $this->tools->tool('strtoupper', $pay_mode);

        $this->dependencies->apiClass->initializeApi($sandbox);

        if (null == $pay_id) {
            return $inst_id ? $this->processInstallmentRefund($inst_id, $amount, $metadata) : 'error';
        }

        if (!is_numeric($amount) || $amount <= 0) {
            $this->logger->addLog(
                'RefundAction::refundAction - Invalid argument, $amount must be a positive numeric value.',
                'error'
            );

            return 'error';
        }

        if (!is_array($metadata)) {
            $this->logger->addLog(
                'RefundAction::refundAction - Invalid argument, $metadata must be an array.',
                'error'
            );

            return 'error';
        }

        return $this->processPaymentRefund($pay_id, $amount, $metadata);
    }

    /**
     * @description process refunds for each payment in the installment
     *
     * @param $inst_id
     * @param $amount
     * @param $metadata
     *
     * @return mixed|string
     */
    public function processInstallmentRefund($inst_id, &$amount, $metadata)
    {
        $this->setParameters();
        // if installment_id not defined
        $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);

        if (!$installment['result']) {
            $error = 'error [RefundAction - processInstallmentRefund()]: Can\'t retrieve InstallmentPlan with given id: ' . $inst_id;
            $this->logger->addLog($error, 'error');

            return 'error';
        }

        $this->logger->addLog('[RefundAction - processInstallmentRefund()] Retrieve installment id: ' . $inst_id);

        $refundablePayments = [];
        foreach ($installment['resource']->schedule as $schedule) {
            if (!empty($schedule->payment_ids)) {
                foreach ($schedule->payment_ids as $p_id) {
                    $refundData = $this->processPaymentRefund($p_id, $amount, $metadata);

                    if ($refundData) {
                        $refundablePayments[] = $refundData;
                    }
                }
            }
        }
        if ($this->validators['payment']->canBeRefund($inst_id, $refundablePayments)['result']) {
            foreach ($refundablePayments as $refund) {
                if (!$refund['response']['result']) {
                    return 'error';
                }
            }
            $this->dependencies->installmentClass->updatePayplugInstallment($installment);
        } else {
            return 'error';
        }

        return $refundablePayments[0]['response']['resource'];
    }

    /**
     * @description This method retrieves the payment details,
     * checks if it is paid and not fully refunded,
     * calculates the refundable amount,
     * and initiates the refund if applicable.
     *
     * @param $payment_id
     * @param $amount
     * @param $metadata
     *
     * @return null|array
     */
    public function processPaymentRefund($payment_id, $amount, $metadata)
    {
        $payment = $this->dependencies->apiClass->retrievePayment($payment_id);

        if (!$payment['result']) {
            return null;
        }

        $payment = $payment['resource'];

        if ($payment->is_paid && !$payment->is_refunded && $amount > 0) {
            $amountRefundable = (int) ($payment->amount - $payment->amount_refunded);
            $trulyRefundableAmount = min($amount, $amountRefundable);

            if ($trulyRefundableAmount > 0) {
                $amount -= $trulyRefundableAmount;

                return [
                    'id' => $payment_id,
                    'data' => [
                        'amount' => $trulyRefundableAmount,
                        'metadata' => $metadata,
                    ],
                    'response' => $this->dependencies->apiClass->refundPayment(
                        $payment_id,
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

    /**
     * @description Set needed object from dependencies
     */
    private function setParameters()
    {
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validators = $this->dependencies->getValidators();
        $this->plugin = $this->plugin ?: $this->dependencies
            ->getPlugin();
        $this->logger = $this->logger ?: $this->plugin
            ->getLogger();
    }
}
