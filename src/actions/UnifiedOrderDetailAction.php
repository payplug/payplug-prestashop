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

use PayPlug\classes\DependenciesClass;
use PayPlug\src\unified\helpers\UnifiedPaymentDetails;
use PayPlug\src\utilities\traits\ServiceGetter;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;
use PayplugUnifiedCore\Utilities\Helpers\ExecCodeMapper;

/**
 * Builds the "Payplug payment details" block of an order paid with Unified Hosted Fields, in the
 * shape PaymentMethod::getResourceDetail() gives a Retail payment (views/templates/admin/order/details.tpl).
 *
 * Refunded amounts come from the module's own refund history, card and 3-D Secure details from the
 * snapshot recorded on the operation, fetched with getOperation() for an order without one. A fetch
 * that gives no recorded snapshot is not tried again for FETCH_FAILED_TTL seconds.
 */
class UnifiedOrderDetailAction
{
    use ServiceGetter;

    private const FACTORY_SERVICE = 'payplug.unified.payment_service_factory';

    private const FETCH_FAILED_KEY_PREFIX = 'uhf_details_fetch_failed:';

    private const FETCH_FAILED_TTL = 300;

    private const STATUS_CLASSES = [
        'paid' => 'pp_success',
        'partially_refunded' => 'pp_neutral',
        'refunded' => 'pp_warning',
    ];

    public $dependencies;

    /**
     * @param mixed|null $dependencies
     */
    public function __construct($dependencies = null)
    {
        $this->dependencies = $dependencies ?: new DependenciesClass();
    }

    /**
     * @description Build the payment details of a paid Unified Hosted Fields order
     *
     * @param mixed $order
     * @param OperationData $operation the operation that paid the order
     * @param mixed $currency currency of the order
     * @param int $refunded_cents amount refunded so far, in minor units
     *
     * @return array
     */
    public function buildPayment($order, $operation, $currency, $refunded_cents)
    {
        $factory = $this->getService(self::FACTORY_SERVICE);
        $repository = $factory->createPaymentRepository();
        $plugin = $this->dependencies->getPlugin();
        $translations = $plugin->getTranslationClass()->getOrderTranslations()['detail'];
        $iso_code = (string) $currency->iso_code;
        $is_live = !(bool) $plugin->getConfigurationClass()->getValue('sandbox_mode');

        $payment = array_merge(
            $this->statusFields((int) $operation->amount, (int) $refunded_cents, $translations),
            $this->amountFields((int) $operation->amount, (int) $refunded_cents, $iso_code),
            $this->cardFields($this->resolveSnapshot($factory, $repository, $operation->operationId), $translations),
            [
                'mode' => $translations['mode'][$is_live ? 'live' : 'test'],
                'paid' => true,
                'authorization' => false,
                'date' => $this->paidDate($repository->getDateAddByOperationId($operation->operationId), $order),
                'error' => '',
                'currency' => $iso_code,
            ]
        );

        $payment_id = $repository->getPaymentIdByOperationId($operation->operationId);
        if (null !== $payment_id) {
            $payment['id'] = $payment_id;
        }

        return $payment;
    }

    /**
     * @param int $amount_cents
     * @param int $refunded_cents
     *
     * @return array{status: string, status_code: string, status_class: string}
     */
    private function statusFields($amount_cents, $refunded_cents, array $translations)
    {
        $status_code = 'paid';
        if ($refunded_cents > 0) {
            $status_code = $refunded_cents < $amount_cents ? 'partially_refunded' : 'refunded';
        }

        return [
            'status' => $translations['status'][$status_code],
            'status_code' => $status_code,
            'status_class' => self::STATUS_CLASSES[$status_code],
        ];
    }

    /**
     * @param int $amount_cents
     * @param int $refunded_cents
     * @param string $iso_code currency of the order
     *
     * @return array
     */
    private function amountFields($amount_cents, $refunded_cents, $iso_code)
    {
        $amount_helper = $this->getService('payplug.utilities.helper.amount');
        $price_adapter = $this->getService('payplug.application.adapter.price');
        $amount = (float) $amount_helper->convertAmount($amount_cents, true, $iso_code);
        $refunded = (float) $amount_helper->convertAmount($refunded_cents, true, $iso_code);
        $available = (float) $amount_helper->convertAmount(max(0, $amount_cents - $refunded_cents), true, $iso_code);

        return [
            'amount' => $amount,
            'amount_display' => $price_adapter->formatPrice($amount, $iso_code),
            'refund' => [
                'refunded' => $refunded,
                'refunded_display' => $price_adapter->formatPrice($refunded, $iso_code),
                'available' => $available,
                'available_display' => $price_adapter->formatPrice($available, $iso_code),
                'is_refunded' => $refunded_cents > 0 && $refunded_cents >= $amount_cents,
            ],
        ];
    }

    /**
     * @param array $snapshot UnifiedPaymentDetails::fromOperation() shape
     *
     * @return array
     */
    private function cardFields(array $snapshot, array $translations)
    {
        $brand = $this->snapshotValue($snapshot, 'brand');
        $last4 = $this->snapshotValue($snapshot, 'last4');
        $exp_month = $this->snapshotValue($snapshot, 'exp_month');
        $exp_year = $this->snapshotValue($snapshot, 'exp_year');
        $tds = (string) $this->snapshotValue($snapshot, 'tds');
        $tds_labels = [
            UnifiedPaymentDetails::TDS_YES => $translations['yes'],
            UnifiedPaymentDetails::TDS_NO => $translations['no'],
        ];

        return [
            'card_brand' => null !== $brand ? $translations['card'] . ' ' . $brand : null,
            'card_mask' => null !== $last4 ? '**** **** **** ' . $last4 : null,
            'card_date' => null !== $exp_month && null !== $exp_year ? $exp_month . '/' . $exp_year : null,
            'card_country' => null,
            'tds' => isset($tds_labels[$tds]) ? $tds_labels[$tds] : false,
        ];
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    private function snapshotValue(array $snapshot, $key)
    {
        return isset($snapshot[$key]) && is_string($snapshot[$key]) && '' !== $snapshot[$key] ? $snapshot[$key] : null;
    }

    /**
     * @description Get the card and 3-D Secure details recorded for the operation, fetching and
     *              recording them when none were
     *
     * @param mixed $factory
     * @param mixed $repository
     * @param string $operation_id
     *
     * @return array empty when they are not available
     */
    private function resolveSnapshot($factory, $repository, $operation_id)
    {
        $snapshot = $repository->getPaymentDetails($operation_id);
        if (null !== $snapshot) {
            return $snapshot;
        }

        if ($this->hasRecentFetchFailure($factory, $operation_id)) {
            return [];
        }

        $snapshot = $this->fetchSnapshot($factory, $operation_id);
        if (null === $snapshot) {
            $this->markFetchFailure($factory, $operation_id);

            return [];
        }

        try {
            $saved = (bool) $repository->savePaymentDetails($operation_id, $snapshot);
            $reason = 'not saved';
        } catch (\Throwable $exception) {
            $saved = false;
            $reason = $exception->getMessage();
        }

        if (!$saved) {
            $factory->createLogger()->error('UnifiedOrderDetailAction::buildPayment - details of operation '
                . $operation_id . ' not recorded: ' . $reason);
            $this->markFetchFailure($factory, $operation_id);
        }

        return $snapshot;
    }

    /**
     * @param mixed $factory
     * @param string $operation_id
     *
     * @return bool false when the marker cannot be read (logged)
     */
    private function hasRecentFetchFailure($factory, $operation_id)
    {
        try {
            return null !== $factory->createTokenCache()->get(self::FETCH_FAILED_KEY_PREFIX . $operation_id);
        } catch (\Throwable $exception) {
            $factory->createLogger()->error('UnifiedOrderDetailAction::buildPayment - fetch failure marker of operation '
                . $operation_id . ' not read: ' . $exception->getMessage());

            return false;
        }
    }

    /**
     * @param mixed $factory
     * @param string $operation_id
     */
    private function markFetchFailure($factory, $operation_id)
    {
        try {
            $factory->createTokenCache()->set(self::FETCH_FAILED_KEY_PREFIX . $operation_id, '1', self::FETCH_FAILED_TTL);
        } catch (\Throwable $exception) {
            $factory->createLogger()->error('UnifiedOrderDetailAction::buildPayment - fetch failure marker of operation '
                . $operation_id . ' not written: ' . $exception->getMessage());
        }
    }

    /**
     * @param mixed $factory
     * @param string $operation_id
     *
     * @return array|null null when the operation cannot be read or is not paid (logged)
     */
    private function fetchSnapshot($factory, $operation_id)
    {
        $operation = null;

        try {
            $response = $factory->create()->getOperation($operation_id);
            $operation = json_decode(isset($response['body']) ? (string) $response['body'] : '', true);
            $exec_code = is_array($operation) && isset($operation['execCode']) ? (string) $operation['execCode'] : '';
            $paid = PaymentOutcome::PAID === ExecCodeMapper::toPaymentOutcome($exec_code);
            $reason = 'not a paid operation';
        } catch (\Throwable $exception) {
            $paid = false;
            $reason = $exception->getMessage();
        }

        if (!$paid) {
            $factory->createLogger()->error('UnifiedOrderDetailAction::buildPayment - card details of operation '
                . $operation_id . ' not available: ' . $reason);

            return null;
        }

        return UnifiedPaymentDetails::fromOperation($operation);
    }

    /**
     * @param string|null $date_add creation date of the operation row
     * @param mixed $order
     *
     * @return string|null d/m/Y, the order date for an undated operation
     */
    private function paidDate($date_add, $order)
    {
        $date = null !== $date_add ? $date_add : (isset($order->date_add) ? (string) $order->date_add : '');
        $timestamp = '' !== $date ? strtotime($date) : false;

        return false !== $timestamp ? date('d/m/Y', $timestamp) : null;
    }
}
