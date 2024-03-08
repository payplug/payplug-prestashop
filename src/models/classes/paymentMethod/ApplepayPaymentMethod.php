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

class ApplepayPaymentMethod extends PaymentMethod
{
    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'applepay';
        $this->order_name = 'applepay';
        $this->force_resource = true;
    }

    public function getCarriersList()
    {
        $this->setParameters();

        $carriers_list = [];
        $allowed_carriers = json_decode($this->configuration->getValue('applepay_carriers'), true) ?: [];

        if (!is_array($allowed_carriers) && empty($allowed_carriers)) {
            return $carriers_list;
        }

        $delivery_options = $this->context->cart->getDeliveryOptionList();
        $carrier_ids = [];
        foreach ($delivery_options as $address_options) {
            foreach ($address_options as $option_data) {
                $carrier_list = $option_data['carrier_list'];
                foreach ($carrier_list as $carrier_id => $carrier_data) {
                    if (!is_int($carrier_id) || !$carrier_id) {
                        continue;
                    }
                    $carrier_ids[] = $carrier_id;
                }
            }
        }

        return array_intersect($carrier_ids, $allowed_carriers);
    }

    public function getDeliveryOptions()
    {
        $this->setParameters();
        $carriers_list = $this->getCarriersList();
        $carrier_adapter = $this->dependencies
            ->getPlugin()
            ->getCarrier();

        $shipping_methods = [];

        foreach ($carriers_list as $key => $id_carrier) {
            $carrier = $carrier_adapter->get((int) $id_carrier);
            if (!$this->validate_adapter->validate('isLoadedObject', $carrier)) {
                continue;
            }

            $shipping_methods[] = [
                'identifier' => $carrier->id,
                'label' => $carrier->name,
                'detail' => $carrier->delay,
                'amount' => $this->context->cart->getPackageShippingCost($carrier->id),
            ];
        }

        return $shipping_methods;
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

        $id_lang = $this->context->language->id;
        $carriers = $this->getAvailableCarriers((int) $id_lang);

        $option['options'] = [
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'applepay_checkout',
                'title' => $this->translation[$this->name]['checkout']['title'],
                'checked' => $current_configuration['applepay_checkout'],
            ],
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'applepay_cart',
                'title' => $this->translation[$this->name]['cart']['title'],
                'descriptions' => [
                    'live' => [
                        'description' => $this->translation[$this->name]['cart']['description'],
                    ],
                    'sandbox' => [
                        'description' => $this->translation[$this->name]['cart']['description'],
                    ],
                ],
                'checked' => $current_configuration['applepay_cart'],
                'carriers' => empty($carriers) ? [] : [
                    'title' => $this->translation[$this->name]['carrier']['title'],
                    'alert' => $this->translation[$this->name]['carrier']['alert'],
                    'description' => $this->translation[$this->name]['carrier']['description'],
                    'carriers_list' => $carriers,
                ],
            ],
        ];

        return $option;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $workflow = $this->tools->tool('getValue', 'workflow');
        if (empty($workflow)) {
            return [];
        }

        $payment_tab['metadata'] = [
            'applepay_workflow' => $workflow,
        ];
        $payment_tab['payment_method'] = 'apple_pay';
        $payment_tab['payment_context'] = [
            'apple_pay' => [
                'domain_name' => $this->context->shop->domain_ssl,
                'application_data' => base64_encode(json_encode([
                    'apple_pay_domain' => $this->context->shop->domain_ssl,
                ])),
            ],
        ];
        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card'], $payment_tab['shipping']['delivery_type']);

        return $payment_tab;
    }

    public function getRequest()
    {
        $this->setParameters();

        $additionalPaymentRequestDatas = [];
        $currency = $this->dependencies
            ->getPlugin()
            ->getCurrency()
            ->get((int) $this->context->cart->id_currency);

        $workflow = $this->tools->tool('getValue', 'workflow');
        if ('checkout' != $workflow) {
            $delivery_options = $this->getDeliveryOptions();
            if (!empty($delivery_options)) {
                $additionalPaymentRequestDatas['shippingMethods'] = $delivery_options;
            }
        }

        // Uncomment this when page panier developments will starts
        /*if ($page != 'order') {
            $carriers = $this->carrier->getCarriers($this->context->language->id, true);
            $shippingMethods = array();

            foreach ($carriers as $key => $carrier) {
                $shippingMethods[$key]['label'] = $carrier['name'];
                $shippingMethods[$key]['detail'] = $carrier['delay'];
                $shippingMethods[$key]['amount'] = $this->context->cart->getPackageShippingCost($carrier['id_carrier']);
                $shippingMethods[$key]['identifier'] = 'FreeShip';
            }

            $summaryDetails = $this->context->cart->getSummaryDetails();

            $additionalPaymentRequestDatas = array(
                'shippingType' => 'storePickup',
                'shippingMethods' => $shippingMethods,
                'requiredShippingContactFields' => array(
                    'postalAddress',
                    'name',
                    'phone',
                    'email'
                ),
                'lineItems' => array(
                    array(
                        'label' => 'Products',
                        'amount' => $summaryDetails['total_products_wt']
                    ),
                    array(
                        'label' => 'Shipping',
                        'amount' => $summaryDetails['total_shipping']
                    )
                ),
            );
        }*/

        $applePayPaymentRequest = [
            'countryCode' => $this->context->country->iso_code,
            'currencyCode' => $currency->iso_code,
            'merchantCapabilities' => [
                'supports3DS',
            ],
            'supportedNetworks' => [
                'visa',
                'masterCard',
                // 'amex', Amex is not supported yet by PayPlug
                'discover',
            ],
            'total' => [
                'label' => $this->context->shop->name,
                'type' => 'final',
                'amount' => $this->context->cart->getOrderTotal(),
            ],
            'applicationData' => base64_encode(json_encode([
                'apple_pay_domain' => $this->context->shop->domain_ssl,
            ])),
        ];

        return array_merge($applePayPaymentRequest, $additionalPaymentRequestDatas);
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
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $resource_details['type'] = $translation['detail']['method']['applepay'];

        return $resource_details;
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
        $this->setParameters();

        if (!is_array($payment_options)) {
            return [];
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
            return $payment_options;
        }

        $browser = $this->dependencies->getPlugin()->getBrowser()->getName();
        $isApplePayCompatible = $this->dependencies->getValidators()['browser']->isApplePayCompatible($browser);
        if (!$isApplePayCompatible['result']) {
            unset($payment_options[$this->name]);

            return $payment_options;
        }
        $payment_options[$this->name]['action'] = 'javascript:void(0)';
        $applepay_js_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getSourceUrl()['applepay'];

        $this->dependencies
            ->getPlugin()
            ->getAssign()
            ->assign([
                'applepay_js_url' => $applepay_js_url,
                'applepay_workflow' => 'checkout',
            ]);

        $payment_options[$this->name]['additionalInformation'] = $this->dependencies->configClass->fetchTemplate('checkout/payment/applepay.tpl');

        return $payment_options;
    }

    /**
     * @description Get all enable carrier from the merchand configuration from a given id lang
     *
     * @param int $id_lang
     *
     * @return array
     */
    protected function getAvailableCarriers($id_lang = 0)
    {
        $this->setParameters();

        $carriers = [];

        if (!is_int($id_lang) || !$id_lang) {
            return $carriers;
        }

        $shop_carriers = $this->dependencies
            ->getPlugin()
            ->getCarrier()
            ->getCarriers($id_lang, true);

        if (empty($shop_carriers)) {
            return $carriers;
        }

        $applepay_carriers = json_decode($this->configuration->getValue('applepay_carriers'), true);
        if (!is_array($applepay_carriers)) {
            return $carriers;
        }

        foreach ($shop_carriers as $carrier) {
            $checked = in_array($carrier['id_carrier'], $applepay_carriers);
            $carriers[] = [
                'id_carrier' => $carrier['id_carrier'],
                'name' => $carrier['name'],
                'checked' => $checked,
            ];
        }

        return $carriers;
    }
}
