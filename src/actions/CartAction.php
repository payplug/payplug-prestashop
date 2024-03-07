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
class CartAction
{
    private $dependencies;
    private $plugin;
    private $configuration;
    private $context;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description Generic method for rendering payment
     * actions elements for the cart and product pages
     *
     * @return false|string
     */
    public function renderPaymentCTA()
    {
        $this->setParameters();

        if (!(bool) $this->configuration->getValue('applepay_cart') && !((bool) $this->configuration->getValue(
            'applepay_checkout'
        )) && $this->configuration->getValue('sandbox_mode')) {
            return false;
        }

        return $this->renderApplepayCartCheckout();
    }

    /**
     * @description Render the Apple Pay button based on cart contents and allowed carriers.
     *
     * This function checks if Apple Pay is compatible, analyzes the cart contents, and
     * compares it with the allowed carriers. If at least one common carrier is found,
     * it returns the Apple Pay button template; otherwise, it returns false. If the cart
     * contains at least one carrier with a value of 0, it returns false.
     *
     * @return false|string
     */
    public function renderApplePayCartCheckout()
    {
        $this->setParameters();

        $browser = $this->dependencies->getPlugin()->getBrowser()->getName();
        $isApplePayCompatible = $this->dependencies->getValidators()['browser']->isApplePayCompatible($browser);
        // If browser is not safari, no appelpay on cart page
        if (!$isApplePayCompatible['result']) {
            return false;
        }

        // Get allowed carriers from configuration table and check if it's filled by the merchant
        $allowed_carriers = json_decode($this->configuration->getValue('applepay_carriers'), true) ?: [];

        if (!is_array($allowed_carriers) && empty($allowed_carriers)) {
            return false;
        }

        $delivery_options = $this->context->cart->getDeliveryOptionList();
        // Extract carrier IDs from delivery options
        $carrier_ids = [];

        foreach ($delivery_options as $id_address => $address_options) {
            foreach ($address_options as $option_key => $option_data) {
                $carrier_list = $option_data['carrier_list'];

                foreach ($carrier_list as $carrier_id => $carrier_data) {
                    // Check if the content of the cart is not suitable for any carrier.
                    if (0 == $carrier_id) {
                        return false;
                    }

                    $carrier_ids[] = $carrier_id;
                }
            }
        }

        if (empty(array_intersect($carrier_ids, $allowed_carriers))) {
            return false;
        }

        return $this->dependencies->configClass->fetchTemplate('checkout/payment/applepay.tpl');
    }

    /**
     * @description Set needed object from dependencies
     */
    private function setParameters()
    {
        $this->plugin = $this->plugin ?: $this->dependencies
            ->getPlugin();
        $this->context = $this->plugin->getContext()->get();
        $this->configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
    }
}
