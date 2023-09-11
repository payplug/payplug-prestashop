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
