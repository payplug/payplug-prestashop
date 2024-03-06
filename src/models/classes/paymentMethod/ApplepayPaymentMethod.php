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

class ApplepayPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'applepay';
        $this->order_name = 'applepay';
        $this->force_resource = true;
    }

    /**
     * @description Get option for given configuration
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOption($current_configuration = [])
    {
        $option = parent::getOption($current_configuration);
        $option['available_test_mode'] = false;

        return $option;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['payment_method'] = 'apple_pay';
        $payment_tab['payment_context'] = [
            'apple_pay' => [
                'domain_name' => $this->context->shop->domain_ssl,
                'application_data' => base64_encode(json_encode([
                    'apple_pay_domain' => $this->context->shop->domain_ssl,
                ])),
            ],
        ];
        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card'], $payment_tab['shipping']['delivery_type']);

        return $payment_tab;
    }

    // todo: add coverage to this method
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            // todo: add error log
            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $resource_details = parent::getResourceDetail($resource_id);
        if (empty($resource_details)) {
            return $resource_details;
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $resource_details['type'] = $translation['detail']['method']['applepay'];

        return $resource_details;
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

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $browser = $this->dependencies->getPlugin()->getBrowser()->getName();
        $isApplePayCompatible = $this->dependencies->getValidators()['browser']->isApplePayCompatible($browser);
        if (!$isApplePayCompatible['result']) {
            unset($payment_options[$this->name]);

            return $payment_options;
        }
        $payment_options[$this->name]['action'] = 'javascript:void(0)';
        $payment_options[$this->name]['additionalInformation'] = $this->dependencies->configClass->fetchTemplate('checkout/payment/applepay.tpl');

        return $payment_options;
    }
}
