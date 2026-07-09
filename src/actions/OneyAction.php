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

class OneyAction
{
    private $dependencies;
    private $configuration;
    private $configuration_adapter;
    private $context;
    private $current_controller;
    private $dispatcher;
    private $tools;
    private $plugin;

    /**
     * OneyAction constructor.
     *
     * @param $dependencies
     */
    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description This function display the Oney CTA
     *
     * @param array $params
     *
     * @return bool
     */
    public function renderCTA($params = [])
    {
        // Early exit before setParameters() to avoid unnecessary initialization
        if (!in_array(
            $this->dependencies->getPlugin()->getDispatcher()->getInstance()->getController(),
            ['product', 'cart']
        )) {
            return false;
        }

        $this->setParameters();

        if (!isset($params['type']) && 'cart' == $this->current_controller) {
            $params['type'] = 'oney_cart';
        }

        // then check if oney is allowed
        if (!$this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney')
            ->isOneyAllowed()) {
            return false;
        }

        // then check if the current language is the same as the one configured in the back office
        if ((string) $this->plugin
            ->getTools()
            ->tool('strtoupper', $this->plugin
            ->getContext()
            ->get()->language->iso_code) !=
            $this->plugin->getConfigurationClass()->getValue('company_iso')) {
            return false;
        }

        $action = $this->plugin
            ->getTools()
            ->tool('getValue', 'action');
        if (('product' == $this->current_controller
            && 'after_price' != $params['type'])
            || 'quickview' == $action) {
            return false;
        }

        if ('cart' == $this->current_controller
            && 'unit_price' == $params['type']) {
            return false;
        }

        if ('cart' == $this->current_controller) {
            $use_taxes = (bool) $this->configuration_adapter->get('PS_TAX');
            $amount = $this->context->cart->getOrderTotal($use_taxes);
        } else {
            $quantity_wanted = $this->plugin
                ->getTools()
                ->tool('getValue', 'quantity_wanted');
            $amount = $params['product']['price_amount'] * $quantity_wanted;
        }

        $is_elligible = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney')
            ->isValidOneyAmount($amount);

        $this->context->getContext()->smarty->assign([
            'env' => 'checkout',
            'payplug_is_oney_elligible' => $is_elligible['result'],
            'use_fees' => (bool) $this->configuration->getValue('oney_fees'),
            'iso_code' => $this->plugin
                ->getTools()
                ->tool('strtoupper', $this->context->language->iso_code),
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @description This function display the Oney payment errors
     *
     * @param string $error
     *
     * @return array
     */
    public function renderRequiredFields($error = '')
    {
        $this->setParameters();
        if (!is_string($error) || !$error) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderRequiredFields() - Invalid argument given, $error must be a non empty string.');

            return [];
        }

        $fields = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney')
            ->getOneyRequiredFields();

        // todo: we should use smarty adapter instead
        $this->context->smarty->assign([
            'oney_type' => str_replace('oney_required_field_', '', $error),
            'oney_required_fields' => $fields,
        ]);

        return [
            'type' => 'template',
            'value' => 'oney/required.tpl',
        ];
    }

    /**
     * @description Display the checkout placeholder for the official Oney widget
     * (oneyMerchantApp.loadCheckoutSection), filtered by the single
     * business_transaction_code matching this Oney payment option. Unlike the PDP/cart
     * pop-in, this is a legal requirement: the widget renders the schedule inline.
     *
     * @param string $type Oney operation type (e.g. 'x3_with_fees')
     * @param float|int $amount
     * @param string $isoCode country used to resolve the per-country merchant_guid/business codes
     *
     * @return bool|string
     */
    public function renderCheckoutSection($type, $amount, $isoCode)
    {
        $this->setParameters();

        if (!is_string($type) || !$type) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderCheckoutSection() - Invalid argument given, $type must be a non empty string.');

            return false;
        }

        if (!is_numeric($amount) || !$amount) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderCheckoutSection() - Invalid argument given, $amount must be a non null numeric value.');

            return false;
        }

        if (!is_string($isoCode) || !$isoCode) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderCheckoutSection() - Invalid argument given, $isoCode must be a non empty string.');

            return false;
        }

        $oney_payment_method = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney');

        // Reuse OneyPaymentMethod::getOperations() as the single source of truth for
        // the 4 known Oney types, instead of duplicating that list here.
        $known_types = $oney_payment_method->getOperations();
        if (!in_array($type, $known_types, true)) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderCheckoutSection() - Invalid argument given, $type must be one of: ' . implode(', ', $known_types) . '.');

            return false;
        }

        // A merchant's Oney contract (merchant_guid/business codes) is set up per
        // country, matching the buyer's delivery country used for this payment option.
        // The widget must be initialized with that same country/merchant_guid pair:
        // the shop-wide company_iso used for the PDP/cart pop-in can differ from it
        // for multi-country merchants and would make the widget reject the request.
        $oney_account_data = $oney_payment_method->getOneyAccountData();

        $business_codes = $oney_account_data->getBusinessCodes($isoCode);
        $merchant_guid = $oney_account_data->getMerchantGuid($isoCode);

        $with_fees = false !== strpos($type, 'with_fees') && false === strpos($type, 'without_fees');
        $operation = 0 === strpos($type, 'x4') ? 'x4' : 'x3';
        $business_transaction_code = $business_codes->get($operation, $with_fees);

        if (!$business_transaction_code || !$merchant_guid) {
            return false;
        }

        $this->context->getContext()->smarty->assign([
            'oney_checkout_placeholder' => $this->dependencies->name . 'OneyCheckout_' . $type,
            'oney_checkout_business_transaction_code' => $business_transaction_code,
            'oney_checkout_amount' => $amount,
            'oney_checkout_country' => strtoupper($isoCode),
            'oney_checkout_merchant_guid' => $merchant_guid,
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/checkout.tpl');
    }

    /**
     * @description Set needed object from dependencies
     */
    private function setParameters()
    {
        $this->plugin = $this->dependencies
            ->getPlugin();

        $this->configuration = $this->plugin->getConfigurationClass();
        $this->configuration_adapter = $this->plugin->getConfiguration();
        $this->context = $this->plugin->getContext()->get();
        $this->dispatcher = $this->plugin->getDispatcher();
        $this->tools = $this->plugin->getTools();
        $this->current_controller = $this->dispatcher->getInstance()->getController();
    }
}
