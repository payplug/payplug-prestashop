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

class SMSLinkPaymentMethod extends StandardPaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'sms_link';
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
        return [];
    }

    /**
     * @description Get the payment tab required to generate a resource payment.
     *
     * @return array
     */
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();
        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['billing']['landline_phone_number'] = $payment_tab['billing']['landline_phone_number'] ?: $payment_tab['shipping']['landline_phone_number'];
        $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['mobile_phone_number'] ?: $payment_tab['shipping']['mobile_phone_number'];
        $payment_tab['hosted_payment']['sent_by'] = 'SMS';

        // If one click is activated, we disable it in the payment link process
        if (isset($payment_tab['allow_save_card']) && $payment_tab['allow_save_card']) {
            $payment_tab['allow_save_card'] = false;
        }

        // If deferred payment is activated, we disable it or the resource will be considere as expired at his creation
        if (isset($payment_tab['authorized_amount']) && $payment_tab['authorized_amount']) {
            $payment_tab['amount'] = $payment_tab['authorized_amount'];
            unset($payment_tab['authorized_amount']);
        }

        // If display mode is integrated, we have to disable it to ensure the validation of the payment page
        if (isset($payment_tab['integration']) && $payment_tab['integration']) {
            unset($payment_tab['integration']);
        }

        // After the payment validation, the customer should not be redirected
        if (isset($payment_tab['hosted_payment']['return_url']) && $payment_tab['hosted_payment']['return_url']) {
            unset($payment_tab['hosted_payment']['return_url']);
        }

        return $payment_tab;
    }

    /**
     * @param mixed $payment_options
     *
     * @return array
     */
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        return $payment_options;
    }
}
