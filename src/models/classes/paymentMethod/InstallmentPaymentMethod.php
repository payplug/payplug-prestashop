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

namespace PayPlug\src\models\classes\paymentMethod;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InstallmentPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'installment';
    }

    /**
     * @description Add schedule to a stored installment resource
     *
     * @param array $retrieve
     *
     * @return bool
     */
    public function addInstallmentSchedules($retrieve = [])
    {
        if (!is_array($retrieve) || empty($retrieve)) {
            return false;
        }

        $installment = $retrieve['resource'];
        if (!is_object($installment) || !$installment) {
            return false;
        }

        // Check if schedules already exists in database
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $installment->id);
        if (!empty($stored_resource) && !empty($stored_resource['schedules'])) {
            return $this->updateInstallmentSchedules($retrieve);
        }

        if (!isset($retrieve['schedule']) || empty($retrieve['schedule'])) {
            return false;
        }

        $step_count = count($installment->schedule);
        $index = 0;
        $schedules = [];
        foreach ($retrieve['schedule'] as $schedule) {
            ++$index;
            $id_payment = '';
            if ($schedule['resource']) {
                $id_payment = $schedule['resource']->id;
                $status = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('standard')
                    ->getPaymentStatus($schedule['resource'])['id_status'];
            } else {
                $status = 6;
            }
            $schedules[] = [
                'id_payment' => $id_payment,
                'step' => $index . '/' . $step_count,
                'amount' => (int) $schedule['amount'],
                'status' => (int) $status,
                'scheduled_date' => $schedule['date'],
            ];
        }

        return $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->updateBy('resource_id', $installment->id, [
                'schedules' => json_encode($schedules),
            ]);
    }

    /**
     * @description Get option for given configuration
     * For this payment method we always return empty array since this payment feature is contain in standard payment option
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOption($current_configuration = [])
    {
        return [];
    }

    /**
     * @description Get order tab for given resource to create the order
     * @todo: add coverage to this method
     *
     * @param array $retrieve
     *
     * @return array
     */
    public function getOrderTab($retrieve = null)
    {
        $this->setParameters();

        $resource = $retrieve['resource'];
        if (!is_object($resource) || !$resource) {
            $this->logger->addLog('InstallmentPaymentMethod::getOrderTab() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        $amount = 0;
        $order_state = 0;
        foreach ($retrieve['schedule'] as $schedule) {
            if (!$amount) {
                $payment = $schedule['resource'];
                $state_addons = $resource->is_live ? '' : '_test';
                if ($this->dependencies->getValidators()['payment']->isDeferred($payment)['result']) {
                    $order_state = $this->configuration->getValue('order_state_auth' . $state_addons);
                } else {
                    $order_state = $this->configuration->getValue('order_state_paid' . $state_addons);
                }
            }
            $amount += (int) $schedule['amount'];
        }

        $amount = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($amount, true);

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();

        return [
            'order_state' => $order_state,
            'amount' => $amount,
            'module_name' => $translation['module_name']['default'],
        ];
    }

    /**
     * @description Get the current payment status from the resource
     *
     * @param object $resource
     *
     * @return array
     */
    public function getPaymentStatus($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            // todo: add error log
            return [];
        }

        if ((bool) $resource->is_active) {
            return [
                'id_status' => 6,
                'code' => 'on_going',
            ];
        }

        if ((bool) $resource->failure) {
            if ('aborted' == $resource->failure->code) {
                return [
                    'id_status' => 7,
                    'code' => 'cancelled',
                ];
            } elseif ('timeout' == $resource->failure->code) {
                return [
                    'id_status' => 11,
                    'code' => 'abandoned',
                ];
            }

            return [
                'id_status' => 3,
                'code' => 'failed',
            ];
        }

        $amount = 0;
        $amount_refounded = 0;
        foreach ($resource->schedule as $schedule) {
            $amount += $schedule->amount;
            if (count($schedule->payment_ids) > 0) {
                $pay_id = $schedule->payment_ids[0];
                $retrieve_payment = $this->dependencies
                    ->getPlugin()
                    ->getModule()
                    ->getInstanceByName($this->dependencies->name)
                    ->getService('payplug.utilities.service.api')
                    ->retrievePayment($pay_id);
                if ($retrieve_payment['result']) {
                    $amount_refounded += $retrieve_payment['resource']->amount_refunded;
                }
            }
        }

        if ((int) $amount == (int) $amount_refounded) {
            return [
                'id_status' => 5,
                'code' => 'refunded',
            ];
        }

        if (0 < (int) $amount_refounded) {
            return [
                'id_status' => 4,
                'code' => 'partially_refunded',
            ];
        }

        if ((bool) $resource->is_fully_paid) {
            return [
                'id_status' => 2,
                'code' => 'paid',
            ];
        }

        return [
            'id_status' => 1,
            'code' => 'not_paid',
        ];
    }

    /**
     * @return array
     */
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        // Update from current schedule configuration
        $schedule_nb = (int) $this->configuration->getValue('inst_mode');
        $schedule = [];
        for ($i = 0; $i < $schedule_nb; ++$i) {
            if (0 == $i) {
                $schedule[$i]['date'] = 'TODAY';
                $int_part = (int) ($payment_tab['amount'] / $schedule_nb);
                $schedule[$i]['amount'] = (int) ($int_part + ($payment_tab['amount'] - ($int_part * $schedule_nb)));
            } else {
                $delay = $i * 30;
                $schedule[$i]['date'] = date('Y-m-d', strtotime("+ {$delay} days"));
                $schedule[$i]['amount'] = (int) ($payment_tab['amount'] / $schedule_nb);
            }
        }
        $payment_tab['schedule'] = $schedule;

        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card'], $payment_tab['amount']);

        return $payment_tab;
    }

    /**
     * @description Get refundable amount for a given resource id.
     *
     * @param string $resource_id
     *
     * @return int
     */
    public function getRefundableAmount($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::getRefundableAmount - Invalid argument, $resource_id must be a non empty string.', 'error');

            return 0;
        }

        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getRefundableAmount - Can\'t retrieve the resource for the given id', 'error');

            return 0;
        }

        if (!isset($retrieve['schedule']) || empty($retrieve['schedule'])) {
            $this->logger->addLog('PaymentMethod::getRefundableAmount - Can\'t retrieve the installment schedules', 'error');

            return 0;
        }

        $amount = 0;
        foreach ($retrieve['schedule'] as $schedule) {
            if ($schedule['resource']) {
                if ($schedule['resource']->is_paid && !$schedule['resource']->is_refunded) {
                    $amount += $schedule['resource']->amount - $schedule['resource']->amount_refunded;
                }
            }
        }

        return (int) $amount;
    }

    /**
     * @description Get refunded amount for a given resource id.
     * todo: Add coverage to this method
     *
     * @param string $resource_id
     *
     * @return int
     */
    public function getRefundedAmount($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::getRefundedAmount - Invalid argument, $resource_id must be a non empty string.', 'error');

            return 0;
        }

        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getRefundedAmount - Can\'t retrieve the resource for the given id', 'error');

            return 0;
        }

        if (!isset($retrieve['schedule']) || empty($retrieve['schedule'])) {
            $this->logger->addLog('PaymentMethod::getRefundedAmount - Can\'t retrieve the installment schedules', 'error');

            return 0;
        }

        $amount = 0;
        foreach ($retrieve['schedule'] as $schedule) {
            if ($schedule['resource']) {
                $amount += $schedule['resource']->amount_refunded;
            }
        }

        return (int) $amount;
    }

    /**
     * @description Get the resource detail
     *
     * todo: add coverage to this method
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('InstallmentPaymentMethod::getResourceDetail - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [];
        }

        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Installment resource can\'t be retrieved for given resource id.', 'error');

            return [];
        }

        $resource = $retrieve['resource'];

        // Before processing resource schedules, we need to ensure that the database is updated
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource->id);
        if (!isset($stored_resource['schedules']) || !$stored_resource['schedules']) {
            $this->addInstallmentSchedules($retrieve);
        }
        $this->updateInstallmentSchedules($retrieve);

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $status = $this->getPaymentStatus($resource);

        $refund = [
            'refunded' => 0,
            'available' => 0,
            'is_refunded' => false,
        ];
        $payment_list = [];
        $amount = 0;
        foreach ($retrieve['schedule'] as $schedule) {
            $amount += $schedule['amount'];
            if ($schedule['resource']) {
                $schedule_resource = $schedule['resource'];
                $schedule_detail = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('standard')
                    ->getResourceDetail($schedule_resource->id);
                $refund['refunded'] += $schedule_detail['refund']['refunded'];
                $refund['available'] += $schedule_detail['refund']['available'];
                $refund['is_refunded'] = (bool) $schedule_resource->is_refunded;
                $payment_list[] = $schedule_detail;
            } else {
                $payment_list[] = [
                    'id' => null,
                    'status' => $translation['detail']['status'][$status['code']],
                    'status_class' => $resource->is_active ? 'pp_success' : 'pp_error',
                    'status_code' => 'incoming',
                    'amount' => $this->dependencies->getHelpers()['amount']->convertAmount($schedule['amount'], true),
                    'card_brand' => null,
                    'card_mask' => null,
                    'tds' => null,
                    'card_date' => null,
                    'mode' => null,
                    'authorization' => null,
                    'date' => \date('d/m/Y', \strtotime($schedule['date'])),
                ];
            }
        }

        return [
            'id' => $resource_id,
            'status' => !$resource->is_active && !$resource->is_fully_paid
                ? $translation['detail']['status']['suspended']
                : $translation['detail']['status'][$status['code']],
            'status_code' => $resource->is_active
                ? 'ongoing'
                : ($resource->is_fully_paid ? 'paid' : 'suspended'),
            'is_active' => $resource->is_active,
            'is_paid' => $resource->is_fully_paid,
            'payment_list' => $payment_list,
            'mode' => $resource->is_live
                ? $translation['detail']['mode']['live']
                : $translation['detail']['mode']['test'],
            'refund' => $refund,
            'currency' => $resource->currency,
            'amount' => $this->dependencies->getHelpers()['amount']->convertAmount($amount, true),
        ];
    }

    /**
     * @return array
     */
    public function getReturnUrl()
    {
        $this->setParameters();

        if (!is_string($this->name) || '' == $this->name) {
            $this->logger->addLog('InstallmentPaymentMethod::getReturnUrl() - Invalid object prop, $name must be a non empty string.');

            return [];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $this->context->cart->id);
        if (!$stored_resource) {
            $this->logger->addLog('InstallmentPaymentMethod::getReturnUrl() - No stored resource retrieve for current context cart id.');

            return [];
        }

        $resource = $this->retrieve($stored_resource['resource_id']);
        if (!$resource['result']) {
            $this->logger->addLog('InstallmentPaymentMethod::getReturnUrl() - Installment resource can\'t be retrieved for stored resource id.');

            return [];
        }

        $resource = $resource['resource'];
        $return_url = $resource->hosted_payment
            ? $resource->hosted_payment->payment_url
                ?: $resource->hosted_payment->return_url
            : '';

        // todo: getter of $_SERVER['HTTP_USER_AGENT'] should be in a service
        $regex_validator = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.validator.regex');

        return [
            'return_url' => $return_url,
            'embedded' => 'redirect' != (string) $this->configuration->getValue('embedded_mode')
                && !$regex_validator->isMobileDevice($_SERVER['HTTP_USER_AGENT'])['result'],
        ];
    }

    /**
     * @description Check if the stored resource is a non expired resource with no failure
     *
     * @return bool
     */
    public function isValidResource()
    {
        $this->setParameters();

        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->cart)) {
            $this->logger->addLog('InstallmentPaymentMethod::isValidResource() - Context Cart object must be a valid object.');

            return false;
        }
        $id_cart = (int) $this->context->cart->id;

        // Get the resource from context cart id
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $id_cart);
        if (empty($stored_resource)) {
            $this->logger->addLog('InstallmentPaymentMethod::isValidResource() - No stored resource retrieve for current context cart id.');

            return false;
        }

        // Check if resource is expired
        $is_expired = $this->dependencies
            ->getValidators()['payment']
            ->isTimeoutCachedPayment($stored_resource['date_upd'])['result'];
        if (!$is_expired) {
            $this->logger->addLog('InstallmentPaymentMethod::isValidResource() - Current resource is expired.');

            return false;
        }

        // Get the resource from API
        $retrieve = $this->retrieve($stored_resource['resource_id']);
        if (!$retrieve['result']) {
            $this->logger->addLog('InstallmentPaymentMethod::isValidResource() - Installment resource can\'t retrieved for stored resource id.');

            return false;
        }

        // Check if schedule has failure
        $first_chedule = reset($retrieve['schedule']);
        if (isset($first_chedule['resource']->failure->code) && $first_chedule['resource']->failure->code) {
            $this->logger->addLog('InstallmentPaymentMethod::isValidResource() - Retrieved Installment has failure');

            return false;
        }

        return true;
    }

    /**
     * @description Post process a given order from a resource retrieve
     * todo: add coverage to this method
     *
     * @param array $retrieve
     * @param int $id_order
     *
     * @return bool
     */
    public function postProcessOrder($retrieve = [], $id_order = 0)
    {
        if (!is_array($retrieve) || empty($retrieve)) {
            return false;
        }

        return $this->addInstallmentSchedules($retrieve);
    }

    /**
     * @description Refund the resource for a given resource id and amount
     *
     * @param string $resource_id
     * @param int $amount
     * @param array $metadata
     *
     * @return array
     */
    public function refund($resource_id = '', $amount = 0, $metadata = [])
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::refund - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        if (!is_numeric($amount) || !$amount) {
            $this->logger->addLog('PaymentMethod::refund - Invalid argument, $amount must be a non null integer.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $amount must be a non null integer.',
            ];
        }

        if (!is_array($metadata) || empty($metadata)) {
            $this->logger->addLog('PaymentMethod::refund - Invalid argument, $metadata must be a non empty array.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $metadata must be a non empty array.',
            ];
        }

        // Retrieve the resource
        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::refund - The resource can\'t be retrieve.', 'error');

            return $retrieve;
        }

        $refundable_payments = [];
        $payment_method_schedule = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('standard');
        foreach ($retrieve['schedule'] as $schedule) {
            if ($schedule['resource']) {
                $refund = $payment_method_schedule->refund($schedule['resource']->id, $amount, $metadata);
                if ($refund['result']) {
                    $amount -= (int) $refund['resource']->amount;
                    $refundable_payments[] = $refund;
                }
            }
        }

        if (empty($refundable_payments)) {
            $this->logger->addLog('PaymentMethod::refund - No refund executed for this installment plan.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'No refund executed for this installment plan.',
            ];
        }

        if (!$this->updateInstallmentSchedules($retrieve)) {
            $this->logger->addLog('PaymentMethod::refund - Can\'t update the schedule.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t update the schedule.',
            ];
        }

        return reset($refundable_payments);
    }

    /**
     * @description Get the related schedule for a given installment plan retrieve
     *
     * @param array $retrieve
     *
     * @return array
     */
    public function retrieveSchedules($retrieve = [])
    {
        $this->setParameters();

        if (!is_array($retrieve) || !$retrieve) {
            $this->logger->addLog('PaymentMethod::retrieveSchedule - Invalid argument, $retrieve must be a non empty array.', 'error');

            return [
                'result' => false,
                'code' => 500,
                'message' => 'Invalid argument, $retrieve must be a non empty array.',
            ];
        }

        if (!$retrieve['result']) {
            return $retrieve;
        }

        if (!isset($retrieve['resource']->schedule) || empty($retrieve['resource']->schedule)) {
            return $retrieve;
        }

        $retrieve['schedule'] = [];
        foreach ($retrieve['resource']->schedule as $schedule) {
            $resource = null;
            if (count($schedule->payment_ids) > 0) {
                $pay_id = $schedule->payment_ids[0];
                $retrieve_payment = $this->dependencies
                    ->getPlugin()
                    ->getModule()
                    ->getInstanceByName($this->dependencies->name)
                    ->getService('payplug.utilities.service.api')
                    ->retrievePayment($pay_id);
                if ($retrieve_payment['result']) {
                    $resource = $retrieve_payment['resource'];
                }
            }
            $retrieve['schedule'][] = [
                'amount' => $schedule->amount,
                'date' => $schedule->date,
                'resource' => $resource,
            ];
        }

        $is_live = !(bool) $this->configuration->getValue('sandbox_mode');
        if ($retrieve['resource']->is_live != $is_live) {
            $this->api_service->initialize((bool) $is_live);
        }

        return $retrieve;
    }

    /**
     * @param array $payment_tab
     *
     * @return array
     */
    public function saveResource($payment_tab = [])
    {
        $this->setParameters();

        if (!is_string($this->name)) {
            $this->logger->addLog('InstallmentPaymentMethod::saveResource - Invalid argument, the method name must be defined.', 'error');

            return [
                'result' => false,
            ];
        }
        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->logger->addLog('InstallmentPaymentMethod::saveResource - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [
                'result' => false,
            ];
        }

        $payment = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->createInstallment($payment_tab);

        if (200 != (int) $payment['code']) {
            return $this->processPaymentError((int) $payment['code'], $payment_tab);
        }

        return $this->retrieveSchedules($payment);
    }

    /**
     * @description Update stored installment ressource schedule
     *
     * @param array $retrieve
     *
     * @return bool
     */
    public function updateInstallmentSchedules($retrieve = [])
    {
        if (!is_array($retrieve) || empty($retrieve)) {
            return false;
        }

        $installment = $retrieve['resource'];
        if (!is_object($installment) || !$installment) {
            return false;
        }

        if (!isset($retrieve['schedule']) || empty($retrieve['schedule'])) {
            return false;
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $installment->id);
        if (empty($stored_resource)) {
            return false;
        }

        $step_count = count($installment->schedule);
        $step_to_update = [];
        $index = 0;
        foreach ($retrieve['schedule'] as $schedule) {
            ++$index;
            $id_payment = '';
            if ($schedule['resource']) {
                $id_payment = $schedule['resource']->id;
                $status = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('standard')
                    ->getPaymentStatus($schedule['resource'])['id_status'];
            } else {
                if (1 == (int) $installment->is_active) {
                    $status = 6; // ongoing
                } else {
                    $status = 7; // cancelled
                }
            }
            $step = $index . '/' . $step_count;
            $step_to_update[$step] = [
                'id_payment' => $id_payment,
                'status' => (int) $status,
            ];
        }

        $schedules = json_decode($stored_resource['schedules'], true);
        foreach ($schedules as &$schedule) {
            $step = $schedule['step'];
            if (array_key_exists($step, $step_to_update)) {
                $schedule['id_payment'] = $step_to_update[$step]['id_payment'];
                $schedule['status'] = $step_to_update[$step]['status'];
            }
        }

        return $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->updateBy('resource_id', $installment->id, [
                'schedules' => json_encode($schedules),
            ]);
    }

    /**
     * @description Get payment option
     *
     * @param array $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        $use_taxes = (bool) $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->get('PS_TAX');

        $context = $this->dependencies->getPlugin()->getContext()->get();
        $order_total = $context->cart->getOrderTotal($use_taxes);
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        if ($order_total < $this->configuration->getValue('inst_min_amount')) {
            return $payment_options;
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $payment_options[$this->name]['logo'] = $this->img_path
            . 'svg/checkout/installment/logos_schemes_installment_'
            . $this->configuration->getValue('inst_mode') . '_'
            . $this->dependencies->configClass->getImgLang() . '.png';

        $payment_options[$this->name]['callToActionText'] = sprintf(
            $payment_options[$this->name]['callToActionText'],
            $this->configuration->getValue('inst_mode')
        );

        return $payment_options;
    }
}
