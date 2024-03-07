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

class AmexPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'amex';
        $this->order_name = 'amex';
        $this->cancellable = false;
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
        $option['name'] = 'american_express';

        return $option;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['payment_method'] = 'american_express';
        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);

        return $payment_tab;
    }

    // todo: add coverage to this method
    public function getReturnUrl()
    {
        $this->setParameters();

        $return = parent::getReturnUrl();

        if (empty($return)) {
            return $return;
        }

        // todo: getter of $_SERVER['HTTP_USER_AGENT'] should be in a service
        $return['embedded'] = 'redirect' != (string) $this->configuration->getValue('embedded_mode')
            && !$this->dependencies->getValidators()['browser']->isMobileDevice($_SERVER['HTTP_USER_AGENT'])['result'];

        return $return;
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
        $resource_details['type'] = $translation['detail']['method']['amex'];
        unset($resource_details['tds'], $resource_details['card_brand']);

        return $resource_details;
    }
}
