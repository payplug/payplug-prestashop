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

class OneyAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description This function display the Oney CTA
     *
     * @param null|mixed $params
     *
     * @return bool
     */
    public function renderCTA($params = null)
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $configuration_adapter = $this->dependencies->getPlugin()->getConfiguration();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $dispatcher = $this->dependencies->getPlugin()->getDispatcher();
        $tools = $this->dependencies->getPlugin()->getTools();

        $current_controller = $dispatcher->getInstance()->getController();

        if (!isset($params['type'])
            && 'cart' == $current_controller) {
            $params['type'] = 'oney_cart';
        }

        if (('product' != $current_controller
            && 'cart' != $current_controller)
            || !$this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod('oney')
                ->isOneyAllowed()
            || (string) $tools->tool('strtoupper', $context->language->iso_code) !=
            $configuration->getValue('company_iso')) {
            return false;
        }

        $action = $tools->tool('getValue', 'action');
        if (('product' == $current_controller
            && 'after_price' != $params['type'])
            || 'quickview' == $action) {
            return false;
        }

        if ('cart' == $current_controller
            && 'unit_price' == $params['type']) {
            return false;
        }

        $use_taxes = (bool) $configuration_adapter->get('PS_TAX');
        $amount = $context->cart->getOrderTotal($use_taxes);
        $is_elligible = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney')
            ->isValidOneyAmount($amount);
        $is_elligible = $is_elligible['result'];

        $context->getContext()->smarty->assign([
            'env' => 'checkout',
            'payplug_is_oney_elligible' => $is_elligible,
            'use_fees' => (bool) $configuration->getValue('oney_fees'),
            'iso_code' => $tools->tool('strtoupper', $context->language->iso_code),
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @description This function display the Oney payment errors
     *
     * @param $error
     *
     * @return array
     */
    public function renderRequiredFields($error = '')
    {
        if (!is_string($error) || !$error) {
            // todo: add log
            return [];
        }

        $context = $this->dependencies->getPlugin()->getContext()->get();

        $fields = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney')
            ->getOneyRequiredFields();

        $context->getContext()->smarty->assign([
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
        if (!is_array($oney_payment) || !$oney_payment) {
            // todo: add log
            return false;
        }

        if (!is_float($amount) || !$amount) {
            // todo: add log
            return false;
        }

        $context = $this->dependencies->getPlugin()->getContext()->get();
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $tools = $this->dependencies->getPlugin()->getTools();

        $withFirstSchedule = 'it' == $context->language->iso_code;

        $vars = [
            'use_fees' => (bool) $configuration->getValue('oney_fees'),
            'oney_payment_option' => $oney_payment,
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $tools->tool('displayPrice', $amount),
            ],
            'withFirstSchedule' => $withFirstSchedule,
            'iso_code' => $tools->tool(
                'strtoupper',
                $context->language->iso_code
            ),
            'merchant_company_iso' => $configuration->getValue('company_iso'),
        ];
        $context->getContext()->smarty->assign($vars);

        return $this->dependencies->configClass->fetchTemplate('oney/schedule.tpl');
    }
}
