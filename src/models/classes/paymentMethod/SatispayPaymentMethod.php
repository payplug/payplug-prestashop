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

class SatispayPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'satispay';
        $this->order_name = 'satispay';
        $this->refundable = false;
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

        $payment_tab['payment_method'] = 'satispay';
        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);

        return $payment_tab;
    }

    /**
     * @description override getOrderTab
     *  If the order state matches the configured pending state,
     * it logs a message indicating an abandoned order
     * and returns an empty array.
     *
     * @param null $resource
     *
     * @return array
     */
    public function getOrderTab($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            $this->logger->addLog('$resource must be a non-empty object');

            return [];
        }
        $order_tab = parent::getOrderTab($resource);
        $order_state_pending = $this->configuration->getValue('order_state_pending');

        if ($order_state_pending == $order_tab['order_state']) {
            $this->logger->addLog('this is an abandoned statispay order, it will not be be created ');

            return [];
        }

        return $order_tab;
    }
}
