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
     * @param null $params
     *
     * @return bool
     */
    public function renderCTA($params = null)
    {
        $this->setParameters();
        if (!isset($params['type'])
            && 'cart' == $this->current_controller) {
            $params['type'] = 'oney_cart';
        }

        if (('product' != $this->current_controller
            && 'cart' != $this->current_controller)
            || !$this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod('oney')
                ->isOneyAllowed()
            || (string) $this->plugin
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
     * @description Display the Oney Schedule.
     *
     * @param $oney_payment
     * @param $amount
     *
     * @return bool
     */
    public function renderSchedule($oney_payment, $amount)
    {
        $this->setParameters();
        if (!is_array($oney_payment) || !$oney_payment) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderRequiredFields() - Invalid argument given, $oney_payment must be a non empty array.');

            return false;
        }

        if (!is_float($amount) || !$amount) {
            $this->plugin
                ->getLogger()
                ->addLog('OneyAction::renderRequiredFields() - Invalid argument given, $amount must be a non null float.');

            return false;
        }

        $withFirstSchedule = 'it' == $this->context->language->iso_code;

        $vars = [
            'use_fees' => (bool) $this->configuration->getValue('oney_fees'),
            'oney_payment_option' => $oney_payment,
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $this->tools->tool('displayPrice', $amount),
            ],
            'withFirstSchedule' => $withFirstSchedule,
            'iso_code' => $this->tools->tool(
                'strtoupper',
                $this->context->language->iso_code
            ),
            'merchant_company_iso' => $this->configuration->getValue('company_iso'),
        ];
        $this->context->getContext()->smarty->assign($vars);

        return $this->dependencies->configClass->fetchTemplate('oney/schedule.tpl');
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
