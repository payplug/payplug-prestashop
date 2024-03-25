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

        $payment_methods = $this->configuration->getValue('payment_methods');
        $payment_methods = json_decode($payment_methods, true);

        if (!(bool) $this->configuration->getValue('applepay_cart')
            || (!(bool) $payment_methods['applepay'])
            || (bool) $this->configuration->getValue('sandbox_mode')) {
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

        // Get Carrier list
        $carriers_list = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('applepay')
            ->getCarriersList();
        if (empty($carriers_list)) {
            return false;
        }

        $applepay_js_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getSourceUrl()['applepay'];

        $this->dependencies
            ->getPlugin()
            ->getAssign()
            ->assign([
                'applepay_js_url' => $applepay_js_url,
                'applepay_workflow' => 'shopping-cart',
            ]);

        $this->dependencies
            ->getPlugin()
            ->getMedia()
            ->addJsDef([
                'applePayPaymentRequestAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'applepaypaymentrequest', [], true),
                'applePayMerchantSessionAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', [], true),
                'applePayPaymentAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'validation', [], true),
                'applePayIdCart' => $this->context->cart->id,
            ]);

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
