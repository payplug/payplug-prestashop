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

    /**
     * @description Get the available carrier carrier list for the current Cart
     *
     * @return array
     */
    public function getCarriersList()
    {
        $this->setParameters();

        $carriers_list = [];
        $allowed_carriers = json_decode($this->configuration->getValue('applepay_carriers'), true) ?: [];

        if (!is_array($allowed_carriers) || empty($allowed_carriers)) {
            return $carriers_list;
        }

        $delivery_options = $this->dependencies
            ->getPlugin()
            ->getCart()
            ->getDeliveryOptionList((int) $this->context->cart->id);

        if (empty($delivery_options)) {
            return $carriers_list;
        }

        $carrier_ids = [];
        foreach ($delivery_options as $address_options) {
            foreach ($address_options as $option_data) {
                $carrier_list = $option_data['carrier_list'];
                foreach ($carrier_list as $carrier_id => $carrier_data) {
                    if (!is_int($carrier_id) || !$carrier_id) {
                        return [];
                    }
                    $carrier_ids[] = $carrier_id;
                }
            }
        }

        return array_intersect($carrier_ids, $allowed_carriers);
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
        $this->setParameters();

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

        if (!isset($payment_tab['shipping']) || empty($payment_tab['shipping'])) {
            $payment_tab['shipping'] = [
                'title' => null,
                'first_name' => 'apple_pay_first_name',
                'last_name' => 'apple_pay_last_name',
                'address1' => null,
                'address2' => null,
                'company_name' => null,
                'postcode' => null,
                'city' => null,
                'state' => null,
                'country' => null,
                'email' => 'noreply@' . $this->context->shop->domain_ssl,
                'mobile_phone_number' => null,
                'landline_phone_number' => null,
                'language' => $this->context->language->iso_code,
            ];
        } else {
            unset($payment_tab['shipping']['delivery_type']);
        }

        if (!isset($payment_tab['billing']) || empty($payment_tab['billing'])) {
            $payment_tab['billing'] = $payment_tab['shipping'];
        }

        unset($payment_tab['force_3ds'], $payment_tab['allow_save_card'], $payment_tab['shipping']['delivery_type']);

        return $payment_tab;
    }

    /**
     * @description Get the request to create applepay resource
     *
     * @return array
     */
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
            $carrier = $this->tools->tool('getValue', 'carrier');

            $delivery_options = $this->getDeliveryOptions();
            if ($carrier) {
                $id_carrier = (int) $carrier['identifier'];
            } else {
                $id_carrier = $delivery_options[0]['identifier'];
            }
        } else {
            $id_carrier = (int) $this->context->cart->id_carrier;
        }

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
                'amount' => $this->dependencies
                    ->getPlugin()
                    ->getCart()
                    ->getOrderTotal((int) $this->context->cart->id, true, (int) $id_carrier),
            ],
            'applicationData' => base64_encode(json_encode([
                'apple_pay_domain' => $this->context->shop->domain_ssl,
            ])),
        ];

        if ('checkout' != $workflow) {
            if (!empty($delivery_options)) {
                $additionalPaymentRequestDatas['shippingMethods'] = $delivery_options;
            }

            $additionalPaymentRequestDatas['requiredBillingContactFields'] = [
                'postalAddress',
                'name',
            ];
            $additionalPaymentRequestDatas['requiredShippingContactFields'] = [
                'email',
                'name',
                'phone',
                'postalAddress',
            ];

            $lineItems = $this->getLinesItems($carrier ? [$carrier] : $delivery_options);
            $additionalPaymentRequestDatas['lineItems'] = $lineItems;
        }

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
        if (empty($resource_details)) {
            return $resource_details;
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $resource_details['type'] = $translation['detail']['method']['applepay'];

        return $resource_details;
    }

    /**
     * @description  hydrate $cart_data array
     *
     * @param $address_data
     * @param null $shipping_email
     * @param false $is_billing
     *
     * @return array
     */
    public function prepareAddressData($address_data = [], $shipping_email = '', $is_billing = false)
    {
        $this->setParameters();
        if (empty($address_data) || !$address_data) {
            return [];
        }
        $prepared_data = [
            'first_name' => $address_data['givenName'],
            'last_name' => $address_data['familyName'],
            'address1' => $address_data['addressLines'][0],
            'postcode' => $address_data['postalCode'],
            'city' => $address_data['locality'],
            'country' => $address_data['countryCode'],
            'language' => $this->tools->tool('strtolower', $address_data['countryCode']),
            'email' => (!empty($shipping_email)) ? $shipping_email : $address_data['emailAddress'],
        ];

        // Include mobile_phone_number only if it's for shipping
        if (!$is_billing) {
            $prepared_data['mobile_phone_number'] = $this->dependencies->configClass->formatPhoneNumber(
                $address_data['phoneNumber'],
                $this->country_adapter->getByIso($address_data['countryCode'])
            );
        }

        return $prepared_data;
    }

    /**
     * @description Get delivery options for the applepay request
     *
     * @return array
     */
    protected function getDeliveryOptions()
    {
        $this->setParameters();
        $carriers_list = $this->getCarriersList();
        $shipping_methods = [];

        if (empty($carriers_list)) {
            return $shipping_methods;
        }

        $carrier_adapter = $this->dependencies
            ->getPlugin()
            ->getCarrier();

        foreach ($carriers_list as $id_carrier) {
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
     * @description Get line items for the applepay request
     *
     * @param array $carriers
     *
     * @return array
     */
    protected function getLinesItems($carriers = [])
    {
        $this->setParameters();

        if (!is_array($carriers)) {
            return [];
        }
        if (!empty($carriers)) {
            $carrier = reset($carriers);
            $carrier_id = $carrier['identifier'];
            $delivery_cost = $carrier['amount'];
        } else {
            $carrier_id = 0;
            $delivery_cost = 0;
        }

        $subtotal_with_taxes = $this->dependencies
            ->getPlugin()
            ->getCart()
            ->getOrderTotalWithoutShipping((int) $this->context->cart->id, true);

        $subtotal_without_taxes = $this->dependencies
            ->getPlugin()
            ->getCart()
            ->getOrderTotalWithoutShipping((int) $this->context->cart->id, false);

        $taxes = (float) $subtotal_with_taxes - (float) $subtotal_without_taxes;

        $line_items = [
            [
                'label' => $this->translation[$this->name]['modal']['subtotal'],
                'type' => 'final',
                'amount' => $this->tools->tool('ps_round', $subtotal_without_taxes, 2),
            ],
            [
                'label' => $this->translation[$this->name]['modal']['tva'],
                'type' => 'final',
                'amount' => $this->tools->tool('ps_round', $taxes, 2),
            ],
        ];
        $discount = $this->dependencies
            ->getPlugin()
            ->getCart()
            ->getOrderTotalDiscount((int) $this->context->cart->id, true, $carrier_id);
        if ((float) $discount > 0) {
            $line_items[] = [
                'label' => $this->translation[$this->name]['modal']['discount'],
                'type' => 'final',
                'amount' => $this->tools->tool('ps_round', $discount, 2) * -1,
            ];
        }

        $line_items[] = [
            'label' => $this->translation[$this->name]['modal']['delivery_cost'],
            'type' => 'final',
            'amount' => $delivery_cost,
        ];

        return $line_items;
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

        if (!(bool) $this->configuration->getValue('applepay_checkout')) {
            return $payment_options;
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
                'iso_lang' => $this->context->language->iso_code,
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
            ->getAllActiveCarriers($id_lang);

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
