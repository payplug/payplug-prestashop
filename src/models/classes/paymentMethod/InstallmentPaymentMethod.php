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

    // todo: add coverage to this method
    public function getOrderTab($installment = null)
    {
        $this->setParameters();

        if (!is_object($installment) || !$installment) {
            // todo: add error log
            return [];
        }

        $amount = 0;
        $order_state = 0;
        foreach ($installment->schedule as $schedule) {
            if (!$amount) {
                $pay_id = $schedule->payment_ids[0];
                $retrieve_payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                if (!$retrieve_payment['result']) {
                    return [];
                }
                $payment = $retrieve_payment['resource'];
                $state_addons = $installment->is_live ? '' : '_test';
                if ($this->dependencies->getValidators()['payment']->isDeferred($payment)['result']) {
                    $order_state = $this->configuration->getValue('order_state_auth' . $state_addons);
                } else {
                    $order_state = $this->configuration->getValue('order_state_paid' . $state_addons);
                }
            }
            $amount += (int) $schedule->amount;
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

    // todo: add coverage to this method
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();
        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('InstallmentPaymentMethod::getResourceDetail - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $sandbox = (bool) $this->configuration->getValue('sandbox_mode');
        $retrieve = $this->dependencies->apiClass->retrieveInstallment($resource_id);
        if (!$retrieve['result']) {
            if ($sandbox) {
                $this->dependencies->apiClass->setSecretKey((string) $this->configuration->getValue('live_api_key'));
                $retrieve = $this->dependencies->apiClass->retrieveInstallment($resource_id);
            } else {
                $this->dependencies->apiClass->setSecretKey((string) $this->configuration->getValue('test_api_key'));
                $retrieve = $this->dependencies->apiClass->retrieveInstallment($resource_id);
            }
        }
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Cannot retrieve the resource.', 'error');

            return [];
        }

        $resource = $retrieve['resource'];

        $payment_list = [];
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $status = $this->getPaymentStatus($resource);

        $amount_available = 0;
        $refund = [
            'refunded' => 0,
            'available' => 0,
            'is_refunded' => false,
        ];
        $payment_list = [];
        $amount = 0;
        foreach ($resource->schedule as $schedule) {
            $amount += $schedule->amount;
            if (!empty($schedule->payment_ids)) {
                foreach ($schedule->payment_ids as $schedule_id) {
                    $schedule_retrieve = $this->dependencies->apiClass->retrievePayment($schedule_id);
                    if (!$schedule_retrieve['result']) {
                        return false;
                    }
                    $schedule_resource = $schedule_retrieve['resource'];
                    $schedule_detail = $this->dependencies
                        ->getPlugin()
                        ->getPaymentMethodClass()
                        ->getPaymentMethod('standard')
                        ->getResourceDetail($schedule_resource->id);
                    $refund['refunded'] += $schedule_detail['refund']['refunded'];
                    $refund['available'] += $schedule_detail['refund']['available'];
                    $refund['is_refunded'] = (bool) $schedule_resource->is_refunded;
                    $payment_list[] = $schedule_detail;
                }
            } else {
                $payment_list[] = [
                    'id' => null,
                    'status' => $translation['detail']['status'][$status['code']],
                    'status_class' => $resource->is_active ? 'pp_success' : 'pp_error',
                    'status_code' => 'incoming',
                    'amount' => $this->dependencies->getHelpers()['amount']->convertAmount($schedule->amount, true),
                    'card_brand' => null,
                    'card_mask' => null,
                    'tds' => null,
                    'card_date' => null,
                    'mode' => null,
                    'authorization' => null,
                    'date' => \date('d/m/Y', \strtotime($schedule->date)),
                ];
            }
        }

        $resource_tab = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByResourceId($resource->id);
        if (!isset($resource_tab['schedules']) || !$resource_tab['schedules']) {
            $this->dependencies->installmentClass->addPayplugInstallment($resource);
        }

        $this->dependencies->installmentClass->updatePayplugInstallment($resource);

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

        if (!isset($this->name) || !$this->name) {
            // todo: add error log
            return [];
        }

        $resource_stored = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $this->context->cart->id);
        if (!$resource_stored) {
            // todo: add error log
            return [];
        }

        $resource = $this->dependencies->apiClass->retrieveInstallment($resource_stored['resource_id']);
        if (!$resource['result']) {
            // todo: add error log
            return [];
        }

        $resource = $resource['resource'];
        $return_url = $resource->hosted_payment
            ? $resource->hosted_payment->payment_url
                ?: $resource->hosted_payment->return_url
            : '';

        // todo: getter of $_SERVER['HTTP_USER_AGENT'] should be in a service
        return [
            'return_url' => $return_url,
            'embedded' => 'redirect' != (string) $this->configuration->getValue('embedded_mode')
                && !$this->dependencies
                    ->getValidators()['browser']
                    ->isMobileDevice($_SERVER['HTTP_USER_AGENT'])['result'],
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
            // todo: Add error log
            return false;
        }
        $id_cart = (int) $this->context->cart->id;

        // Get the resource from context cart id
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $id_cart);
        if (empty($stored_resource)) {
            // todo: Add error log
            return false;
        }

        // Check if resource is expired
        $is_expired = $this->dependencies
            ->getValidators()['payment']
            ->isTimeoutCachedPayment($stored_resource['date_upd'])['result'];
        if (!$is_expired) {
            // todo: Add error log
            return false;
        }

        // Get the resource from API
        $retrieved_resource = $this->dependencies->apiClass->retrieveInstallment($stored_resource['resource_id']);
        if (!$retrieved_resource['result']) {
            // todo: Add error log
            return false;
        }

        // Check if schedule has failure
        $first_chedule = $retrieved_resource['resource']->schedule[0]->payment_ids;
        $schedule_id = end($first_chedule);
        $retrieved_schedule = $this->dependencies->apiClass->retrievePayment($schedule_id);
        if (isset($retrieved_schedule['resource']->failure->code) && $retrieved_schedule['resource']->failure->code) {
            // todo: Add error log
            return false;
        }

        return true;
    }

    // todo: add coverage to this method
    public function postProcessOrder($resource = null, $order = null)
    {
        return $this->dependencies
            ->installmentClass
            ->addPayplugInstallment($resource->id, $order);
    }

    /**
     * @param array $payment_tab
     *
     * @return array
     */
    public function saveResource($payment_tab = [])
    {
        $this->setParameters();

        if (!isset($this->name) || !$this->name) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('InstallmentPaymentMethod::saveResource - Invalid argument, the method name must be defined.', 'error');

            return [
                'result' => false,
            ];
        }
        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('InstallmentPaymentMethod::saveResource - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [
                'result' => false,
            ];
        }

        $payment = $this->dependencies->apiClass->createInstallment($payment_tab);

        // If the payment resource can\'t be created due to to bad permission, we update the feature activation
        if (403 == (int) $payment['code']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('InstallmentPaymentMethod::saveResource - Bad permission error is returned by API.', 'error');
            $cart = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get()->cart;
            $permissions = $this->dependencies->configClass->getAvailableOptions($cart);
            $this->resetPaymentMethodFromPermission($permissions);
        }

        // If the payment resource can\'t be created due to bad credential, we log out the merchand
        if (401 == (int) $payment['code']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('InstallmentPaymentMethod::saveResource - Bad credential error is returned by API.', 'error');
            $this->dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->logoutAction();
        }

        return $payment;
    }

    // todo: add coverage to this method
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

        return parent::getPaymentStatus($resource);
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
