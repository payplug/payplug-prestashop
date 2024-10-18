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
     * @param string $resource_id
     * @param string $amount
     * @param array $metadata
     * @param string $pay_mode
     * @param false $is_installment
     *
     * @return array
     */
    public function refundAction($resource_id = '', $amount = '0', $metadata = [], $pay_mode = 'LIVE', $is_installment = false)
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('RefundAction::refundAction - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [];
        }

        if (!is_numeric($amount) || $amount <= 0) {
            $this->logger->addLog('RefundAction::refundAction - Invalid argument, $amount must be a positive numeric value.', 'error');

            return [];
        }

        if (!is_array($metadata) || empty($metadata)) {
            $this->logger->addLog('RefundAction::refundAction - Invalid argument, $metadata must be an array.', 'error');

            return [];
        }

        if (!is_string($pay_mode)) {
            $this->logger->addLog('RefundAction::refundAction - Invalid argument, $pay_mode must be a string.', 'error');

            return [];
        }

        if (!is_bool($is_installment)) {
            $this->logger->addLog('RefundAction::refundAction - Invalid argument, $is_installment must be a boolean.', 'error');

            return [];
        }

        return (bool) $is_installment
            ? $this->processInstallmentRefund($resource_id, $amount, $metadata)
            : $this->processPaymentRefund($resource_id, $amount, $metadata);
    }

    /**
     * @description process refunds for each payment in the installment
     *
     * @param string $resource_id
     * @param string $amount
     * @param array $metadata
     *
     * @return array
     */
    public function processInstallmentRefund($resource_id = '', $amount = '0', $metadata = [])
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('RefundAction::processInstallmentRefund - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [];
        }

        if (!is_numeric($amount) || !$amount) {
            $this->logger->addLog('RefundAction::processInstallmentRefund - Invalid argument, $amount must be a non null integer.', 'error');

            return [];
        }

        if (!is_array($metadata) || empty($metadata)) {
            $this->logger->addLog('RefundAction::processInstallmentRefund - Invalid argument, $metadata must be a non empty array.', 'error');

            return [];
        }

        // if installment_id not defined
        $payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('installment');
        $retrieve = $payment_method->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('RefundAction::processInstallmentRefund - Can\'t retrieve InstallmentPlan with given id.', 'error');

            return [];
        }

        $this->logger->addLog('RefundAction::processInstallmentRefund - Retrieve installment id: ' . $resource_id);

        $refundablePayments = [];
        foreach ($retrieve['schedule'] as $schedule) {
            if ($schedule['resource']) {
                $refundData = $this->processPaymentRefund($schedule['resource']->id, $amount, $metadata);
                if ($refundData) {
                    $refundablePayments[] = $refundData;
                }
            }
        }

        if (empty($refundablePayments)) {
            $this->logger->addLog('RefundAction::processInstallmentRefund - Retrieve installment id: ' . $resource_id);

            return [];
        }

        $payment_method->updateInstallmentSchedules($retrieve);

        $refund = reset($refundablePayments);

        return $refund['response']['resource'];
    }

    /**
     * @description This method retrieves the payment details,
     * checks if it is paid and not fully refunded,
     * calculates the refundable amount,
     * and initiates the refund if applicable.
     *
     * @param string $resource_id
     * @param int $amount
     * @param array $metadata
     *
     * @return array
     */
    public function processPaymentRefund($resource_id = '', $amount = 0, $metadata = [])
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [];
        }

        if (!is_numeric($amount) || !$amount) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Invalid argument, $amount must be a non null integer.', 'error');

            return [];
        }

        if (!is_array($metadata) || empty($metadata)) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Invalid argument, $metadata must be a non empty array.', 'error');

            return [];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);

        if (empty($stored_resource)) {
            $stored_resource = $this->dependencies
                ->getPlugin()
                ->getPaymentRepository()
                ->getFromSchedule($resource_id);
        }

        if (empty($stored_resource)) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Stored payment can\'t be getted.', 'error');

            return [];
        }

        $method = 'installment' == $stored_resource['method']
            ? 'standard'
            : $stored_resource['method'];

        $retrieve = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($method)
            ->retrieve($resource_id);

        if (!$retrieve['result']) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Payment resource can\'t be retrieved.', 'error');

            return [];
        }

        $payment = $retrieve['resource'];
        if (!$payment->is_paid) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Payment resource is not paid.', 'error');

            return [];
        }

        if ($payment->is_refunded) {
            $this->logger->addLog('RefundAction::processPaymentRefund - Payment resource is fully refund.', 'error');

            return [];
        }

        $amountRefundable = (int) ($payment->amount - $payment->amount_refunded);
        $trulyRefundableAmount = min($amount, $amountRefundable);

        // After the retrieved of the resource
        // If configured mode and resource mode are different
        // then we set the api from the stored payment configuration
        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $is_live = !(bool) $configuration->getValue('sandbox_mode');
        if ($payment->is_live != $is_live) {
            $api_service = $this->dependencies
                ->getPlugin()
                ->getApiService();

            $api_key = (bool) $payment->is_live
                ? $configuration->getValue('live_api_key')
                : $configuration->getValue('test_api_key');

            $api_service->initialize($api_key);
        }

        // Then we do the refund of the resource
        $refund = $api_service->refundPayment(
            $resource_id,
            [
                'amount' => $trulyRefundableAmount,
                'metadata' => $metadata,
            ]
        );

        // Then we reset the initial mode from configuration
        if ($payment->is_live != $is_live) {
            $api_key = (bool) $is_live
                ? $configuration->getValue('live_api_key')
                : $configuration->getValue('test_api_key');

            $api_service->initialize($api_key);
        }

        return [
            'id' => $resource_id,
            'data' => [
                'amount' => $trulyRefundableAmount,
                'metadata' => $metadata,
            ],
            'response' => $refund,
        ];
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
