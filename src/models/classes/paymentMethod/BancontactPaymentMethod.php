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

class BancontactPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'bancontact';
        $this->order_name = 'bancontact';
        $this->cancellable = false;
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
        $this->setParameters();
        $option = parent::getOption($current_configuration);
        $option['available_test_mode'] = false;
        $option['options'] = [
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'bancontact_country',
                'title' => $this->translation[$this->name]['user']['title'],
                'descriptions' => [
                    'live' => [
                        'description' => $this->translation[$this->name]['user']['description'],
                        'link_know_more' => [
                            'text' => $this->translation[$this->name]['link'],
                            'url' => $this->external_url[$this->name],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $this->translation[$this->name]['user']['description'],
                        'link_know_more' => [
                            'text' => $this->translation[$this->name]['link'],
                            'url' => $this->external_url[$this->name],
                            'target' => '_blank',
                        ],
                    ],
                ],
                'checked' => $current_configuration['bancontact_country'],
            ],
        ];

        return $option;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $payment_tab = $this->getDefaultPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $payment_tab['payment_method'] = 'bancontact';
        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);

        return $payment_tab;
    }

    /**
     * @description Get the resource detail
     *
     * todo: add coverage to this method
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('BancontactPaymentMethod::getResourceDetail() - Invalid argument given, $resource_id must be a non empty string.');

            return [];
        }

        $resource_details = parent::getResourceDetail($resource_id);
        if (empty($resource_details)) {
            return $resource_details;
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $resource_details['type'] = $translation['detail']['method']['bancontact'];
        unset($resource_details['tds'], $resource_details['card_brand']);

        return $resource_details;
    }

    // todo: add coverage to this method
    protected function getPaymentOption($payment_options = [])
    {
        if (!is_array($payment_options)) {
            return [];
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $address = $this->dependencies->getPlugin()->getAddress();
        $shipping_address = $address->get((int) $this->context->cart->id_address_delivery);
        $shipping_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $shipping_address->id_country);
        $invoice_address = $address->get((int) $this->context->cart->id_address_invoice);
        $invoice_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $invoice_address->id_country);

        if (
            (bool) $this->configuration->getValue('bancontact_country')
            && (
                !$this->dependencies->getValidators()['payment']->isAllowedCountry('BE', $shipping_iso)['result']
                || $shipping_iso != $invoice_iso
            )
        ) {
            unset($payment_options[$this->name]);

            return $payment_options;
        }

        return $payment_options;
    }
}
