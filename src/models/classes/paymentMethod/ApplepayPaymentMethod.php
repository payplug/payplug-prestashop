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
     * @description Cancel a applepay resource and retrieve the previous one
     *
     * @return array
     */
    public function cancelPaymentResource()
    {
        $this->setParameters();

        // Check payment id correspondance between the given one and the one from the DB
        $payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $this->context->cart->id);

        if (empty($payment)) {
            return [
                'result' => false,
                'message' => 'No payment id for given cart id',
            ];
        }

        $aborted = $this->abort($payment['resource_id']);
        if (!$aborted['result']) {
            return [
                'result' => false,
                'message' => $aborted['message'],
            ];
        }

        // Retrieve the previous cart
        if (isset($this->context->cookie->previous_cart_id) && $this->context->cookie->previous_cart_id) {
            $this->context->cart = $this->dependencies
                ->getPlugin()
                ->getCart()
                ->get((int) $this->context->cookie->previous_cart_id);
            $this->context->cookie->id_cart = $this->context->cart->id;
            $this->context->cookie->previous_cart_id = null;
            $this->dependencies
                ->getPlugin()
                ->getCartRule()
                ->autoAddToCart($this->context);
            $this->context->cookie->write();
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Retrieves the list of compatible carriers for a given product.
     *
     * @param $product_id
     * @param $carriers_list
     *
     * @return array
     */
    public function hasCompatibleCarriersForProduct($product_id)
    {
        $this->setParameters();
        $product_adapter = $this->dependencies
            ->getPlugin()
            ->getProduct();
        $product = $product_adapter->get((int) $product_id);
        $carriers_list = $product->getCarriers();
        $available_carriers = $this->dependencies
            ->getPlugin()
            ->getCarrier()
            ->getAllActiveCarriers($this->context->language->id);

        if (empty($carriers_list)) {
            $carriers_list = $available_carriers;
        }

        $is_compatible = false;
        foreach ($carriers_list as $carrier) {
            if (!$is_compatible) {
                $carrier_sizes = [
                    'width' => (float) $carrier['max_width'],
                    'height' => (float) $carrier['max_height'],
                    'depth' => (float) $carrier['max_depth'],
                    'weight' => (float) $carrier['max_weight'],
                ];
                $product_sizes = [
                    'width' => (float) $product->width,
                    'height' => (float) $product->height,
                    'depth' => (float) $product->depth,
                    'weight' => (float) $product->weight,
                ];
                if (($carrier_sizes['width'] > 0 && $carrier_sizes['width'] < $product_sizes['width'])
                    || ($carrier_sizes['height'] > 0 && $carrier_sizes['height'] < $product_sizes['height'])
                    || ($carrier_sizes['depth'] > 0 && $carrier_sizes['depth'] < $product_sizes['depth'])
                    || ($carrier_sizes['weight'] > 0 && $carrier_sizes['weight'] < $product_sizes['weight'])) {
                    continue;
                }
                $is_compatible = in_array($carrier['id_carrier'], json_decode($this->configuration->getValue('applepay_carriers'), true));
            }
        }

        return $is_compatible;
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
        $display = json_decode($current_configuration['applepay_display'], true);
        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/applepay/';

        $options = [
            [
                'name' => 'applepay_checkout',
                'image_url' => $img_path . 'checkoutCta.jpg',
                'title' => $this->translation[$this->name]['display']['checkout'],
                'switch' => true,
                'checked' => (bool) $display['checkout'],
            ],
            [
                'name' => 'applepay_cart',
                'image_url' => $img_path . 'cartCta.jpg',
                'title' => $this->translation[$this->name]['display']['cart'],
                'switch' => true,
                'checked' => (bool) $display['cart'],
            ],
        ];
        if ($this->dependencies->configClass->isValidFeature('feature_applepay_product')) {
            $options[] = [
                'name' => 'applepay_product',
                'image_url' => $img_path . 'productCta.jpg',
                'title' => $this->translation[$this->name]['display']['product'],
                'switch' => true,
                'checked' => (bool) $display['product'],
            ];
        }
        $option['options'] = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'applepay_display',
                'title' => $this->translation[$this->name]['display']['title'],
                'multiple' => true,
                'options' => $options,
                'carriers' => empty($carriers) ? [] : [
                    'title' => $this->translation[$this->name]['carrier']['title'],
                    'alert' => $this->translation[$this->name]['carrier']['alert'],
                    'descriptions' => [
                        'live' => [
                            'description' => $this->translation[$this->name]['carrier']['description'],
                            'description_bold' => $this->translation[$this->name]['carrier']['description_bold'],
                            'description_warning' => $this->translation[$this->name]['carrier']['description_warning'],
                        ],
                        'sandbox' => [],
                    ],
                    'instructions' => $this->translation[$this->name]['carrier']['instructions'],
                    'carriers_list' => $carriers,
                ],
            ],
        ];

        return $option;
    }

    /**
     * @description Patch the payement resource
     *
     * @param string $resource_id
     * @param string $token
     * @param string $workflow
     * @param array $carrier
     * @param array $user
     *
     * @return array
     */
    public function patchPaymentResource($resource_id = '', $token = [], $workflow = '', $carrier = [], $user = [])
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('ApplepayPaymentMethod::patchPaymentResource() - Invalid argument given, $resource_id must be a non empty string.');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }
        if (!is_array($token) || empty($token)) {
            $this->logger->addLog('ApplepayPaymentMethod::patchPaymentResource() - Invalid argument given, $token must be a non empty array.');

            return [
                'result' => false,
                'message' => 'Invalid argument, $token must be a non empty string.',
            ];
        }
        if (!is_string($workflow) || !$workflow) {
            $this->logger->addLog('ApplepayPaymentMethod::patchPaymentResource() - Invalid argument given, $workflow must be a non empty string.');

            return [
                'result' => false,
                'message' => 'Invalid argument, $workflow must be a non empty string. given: ' . json_encode($workflow),
            ];
        }

        // Check payment id correspondance between the given one and the one from the DB
        $payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);

        if (empty($payment)) {
            return [
                'result' => false,
                'message' => 'No payment id for given resource id',
            ];
        }

        if ($resource_id != $payment['resource_id']) {
            return [
                'result' => false,
                'message' => 'No correspondance with given payment id',
            ];
        }

        $data = [
            'apple_pay' => [
                'payment_token' => $token,
            ],
        ];

        $cart_data = $this->getCartData($workflow, $carrier, $user);

        if (!$cart_data['result']) {
            return $cart_data;
        }
        $data['apple_pay'] = array_merge($data['apple_pay'], $cart_data['data']);

        $patchPayment = $this->dependencies
            ->getPlugin()
            ->getApiService()
            ->patchPayment($resource_id, $data);

        if (!$patchPayment['result']) {
            return [
                'result' => false,
                'message' => 'An error occured during payment patch : ' . $patchPayment['message'],
            ];
        }

        $payment = $patchPayment['resource'];

        // Check if payment has failure...
        $validate_payment = $this->dependencies->getValidators()['payment']->isFailed($payment);
        if ($validate_payment['result']) {
            return [
                'result' => false,
                'message' => $validate_payment['message'],
            ];
        }

        // ... or if is not  paid
        if (!$payment->is_paid) {
            return [
                'result' => false,
                'message' => 'Payment is not paid',
            ];
        }

        $return_url = $this->context->link->getModuleLink(
            $this->dependencies->name,
            'validation',
            ['ps' => 1, 'cartid' => (int) $this->context->cart->id],
            true
        );

        return [
            'result' => true,
            'return_url' => $return_url,
        ];
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

        $payment_tab['metadata']['applepay_workflow'] = $workflow;
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

        $currency = $this->dependencies
            ->getPlugin()
            ->getCurrency()
            ->get((int) $this->context->cart->id_currency);

        $workflow = $this->tools->tool('getValue', 'workflow');
        $cart_adapter = $this->dependencies->getPlugin()->getCart();
        $cart_rule_adapter = $this->dependencies->getPlugin()->getCartRule();
        $address_adapter = $this->dependencies->getPlugin()->getAddress();
        $current_delivery_id = null;
        $current_invoice_id = null;
        $delivery_options = null;
        $carrier = null;

        // Check if this an appelpay 'product' shopping page
        // check empty_cart this double check since we go through this condition twice on js side
        if ('product' === $workflow && true === (bool) $this->tools->tool('getValue', 'empty_cart')) {
            $id_customer_id = (int) $address_adapter->getFirstCustomerAddressId((int) $this->context->cookie->id_customer);

            // Saved current cart id in the cookie...
            $this->context->cookie->previous_cart_id = $this->context->cart->id;

            // ...Then create a new cart and add it to the context.
            $this->context->cart = $cart_adapter->createNewCart($this->context, $id_customer_id);
            $cart_rule_adapter->autoAddToCart($this->context);

            // Update the cookie with the new cart ID
            $this->context->cookie->id_cart = $this->context->cart->id;
            $this->context->cookie->write();

            $id_product = (int) $this->tools->tool('getValue', 'id_product');
            $quantity = (int) $this->tools->tool('getValue', 'quantity');
            // add product to cart
            $cart_adapter->updateQty((int) $this->context->cart->id, $quantity, $id_product);
            $current_address_delivery = (int) $this->context->cart->id_address_delivery;
            $cart_adapter->update($this->context->cart);
            $cart_adapter->updateAddressId((int) $this->context->cart->id, $current_address_delivery, (int) $this->context->cart->id_address_delivery);

            // Reload cart in context after update
            $this->context->cart = $cart_adapter->get((int) $this->context->cart->id);
        }

        if ('checkout' != $workflow) {
            $address = $this->tools->tool('getValue', 'address');
            if ($address) {
                $formated_address = [
                    'firstname' => 'applepay firstname',
                    'lastname' => 'applepay lastname',
                    'address1' => 'applepay address1',
                    'postcode' => $address['postalCode'],
                    'city' => $address['locality'],
                    'id_country' => $this->dependencies
                        ->getPlugin()
                        ->getCountry()
                        ->getByIso($address['countryCode']),
                ];
                $new_address_id = $this->dependencies
                    ->getPlugin()
                    ->getAddressClass()
                    ->checkAndSaveAddress($formated_address);

                // We stack here the context cart address id to prevent update of the cart
                $current_delivery_id = $this->context->cart->id_address_delivery;
                $current_invoice_id = $this->context->cart->id_address_invoice;
                $cart_adapter->updateAddresses($this->context->cart, $new_address_id, $new_address_id);

                // update context iun order to update carrier information, taxes ...
                $this->context->cart = $cart_adapter->get((int) $this->context->cart->id);
            }

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
        $this->context->cart->id_carrier = $id_carrier;

        $applePayPaymentRequest = [
            'country_code' => $this->context->country->iso_code,
            'currency_code' => $currency->iso_code,
            'total' => [
                'label' => $this->context->shop->name,
                'amount' => $this->dependencies
                    ->getPlugin()
                    ->getCart()
                    ->getOrderTotal((int) $this->context->cart->id, true, (int) $id_carrier),
            ],
            'apple_pay_domain' => $this->context->shop->domain_ssl,
        ];

        // This assertion must be handled after to ensure that the updateAddresses method is correctly used
        // and the cart will be updated with the correct carrier ID.
        if ('checkout' != $workflow) {
            if (!empty($delivery_options)) {
                $applePayPaymentRequest['carriers'] = $delivery_options;
            }

            $lineItems = $this->getLinesItems($carrier ? [$carrier] : $delivery_options);
            $applePayPaymentRequest['line_items'] = $lineItems;

            // delete newly created address
            if (isset($new_address_id)) {
                $cart_adapter->updateAddresses($this->context->cart, $current_delivery_id, $current_invoice_id);
                $tmp_address = $address_adapter->get((int) $new_address_id);
                if (!$tmp_address->id_customer) {
                    $address_adapter->delete($tmp_address);
                }
                $this->context->cart = $cart_adapter->get((int) $this->context->cart->id);
            }
        }

        return $applePayPaymentRequest;
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
            $this->logger->addLog('ApplepayPaymentMethod::getResourceDetail() - Invalid argument given, $resource_id must be a non empty string.');

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
        $resource_details['type'] = $translation['detail']['method']['applepay'];

        return $resource_details;
    }

    /**
     * @description  hydrate $cart_data array
     *
     * @param $address_data
     * @param null $shipping_email
     *
     * @return array
     */
    public function prepareAddressData($address_data = [], $shipping_email = '')
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
            'language' => $this->dependencies->configClass->getIsoFromLanguageCode($this->context->language),
            'email' => '' != $shipping_email ? $shipping_email : $address_data['emailAddress'],
        ];

        if (isset($address_data['phoneNumber'])) {
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
        if ($this->context->cart->id_address_delivery) {
            $address = $this->dependencies
                ->getPlugin()
                ->getAddress()
                ->get((int) $this->context->cart->id_address_delivery);
            $this->context->country = $this->country_adapter->get((int) $address->id_country);
        }

        $default_carrier = (int) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('PS_CARRIER_DEFAULT');
        foreach ($carriers_list as $id_carrier) {
            $carrier = $carrier_adapter->get((int) $id_carrier, (int) $this->context->language->id);
            if (!$this->validate_adapter->validate('isLoadedObject', $carrier)) {
                continue;
            }

            $shipping_infos = [
                'identifier' => (string) $carrier->id,
                'label' => (string) $carrier->name,
                'detail' => (string) $carrier->delay,
                'amount' => (string) $this->context->cart->getPackageShippingCost($carrier->id),
            ];
            if ($default_carrier == $id_carrier
                && !empty($shipping_methods)) {
                $temp_shipping = $shipping_methods[0];
                $shipping_methods[0] = $shipping_infos;
                $shipping_methods[] = $temp_shipping;

                continue;
            }

            $shipping_methods[] = $shipping_infos;
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

        $applepay_display = json_decode($this->configuration->getValue('applepay_display'), true);
        if (!(bool) $applepay_display['checkout']) {
            return $payment_options;
        }

        $payment_options = parent::getPaymentOption($payment_options);

        if (!isset($payment_options[$this->name])) {
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

    /**
     * @description Get cart data to patch payment resource
     * todo: add coverage to this method
     *
     * @param string $workflow
     * @param array $carrier
     * @param array $user
     *
     * @return array
     */
    protected function getCartData($workflow = '', $carrier = [], $user = [])
    {
        if (!is_string($workflow) || !$workflow) {
            return [
                'result' => false,
                'message' => 'Invalid given $workflow',
            ];
        }
        if ('checkout' == $workflow) {
            return [
                'result' => true,
                'data' => [],
            ];
        }
        if (!is_array($carrier) || empty($carrier)) {
            return [
                'result' => false,
                'message' => 'Invalid given $carrier',
            ];
        }
        if (!is_array($user) || empty($user)) {
            return [
                'result' => false,
                'message' => 'Invalid given $user',
            ];
        }

        $cart_data = [
            'billing' => $this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod('applepay')
                ->prepareAddressData($user['billing'], $user['shipping']['emailAddress']),
            'shipping' => $this->dependencies
                ->getPlugin()
                ->getPaymentMethodClass()
                ->getPaymentMethod('applepay')
                ->prepareAddressData($user['shipping']),
        ];

        if (empty($cart_data['billing']) || empty($cart_data['shipping'])) {
            return [
                'result' => false,
                'message' => 'Invalid $user datas',
            ];
        }

        $cart_adapter = $this->dependencies
            ->getPlugin()
            ->getCart();
        $cart = $cart_adapter->get((int) $this->context->cart->id);
        $customer_adapter = $this->dependencies
            ->getPlugin()
            ->getCustomer();

        $user_shipping_address = [
            'firstname' => $cart_data['shipping']['first_name'],
            'lastname' => $cart_data['shipping']['last_name'],
            'address1' => $cart_data['shipping']['address1'],
            'postcode' => $cart_data['shipping']['postcode'],
            'city' => $cart_data['shipping']['city'],
            'id_country' => $this->country_adapter->getByIso($cart_data['shipping']['country']),
            'phone_mobile' => $cart_data['shipping']['mobile_phone_number'],
        ];
        $user_billing_address = [
            'firstname' => $cart_data['billing']['first_name'],
            'lastname' => $cart_data['billing']['last_name'],
            'address1' => $cart_data['billing']['address1'],
            'postcode' => $cart_data['billing']['postcode'],
            'city' => $cart_data['billing']['city'],
            'id_country' => $this->country_adapter->getByIso($cart_data['billing']['country']),
        ];
        // check if country if active on merchant's shop
        $is_active = $this->country_adapter->isCountryActiveByCountryId($user_shipping_address['id_country']);
        if (!$is_active) {
            return [
                'result' => false,
                'message' => 'The delivery address country is not active on the shop',
            ];
        }
        $customer = $customer_adapter->get((int) $cart->id_customer);
        if ($this->context->customer->isLogged()) {
            // Prepare shipping and billing addresses data
            // Check if the addresses already exist for the customer
            $customer_addresses = $customer_adapter->getAddresses(
                $this->context->customer->id,
                $this->context->language->id
            );
            // Check and save the shipping address
            $existing_shipping_address = $this->dependencies
                ->getPlugin()
                ->getAddressClass()
                ->checkAndSaveAddress($user_shipping_address, $this->context->customer->id, $customer_addresses);

            // Check and save the billing address if it's different from the shipping address
            if ($user_shipping_address != $user_billing_address) {
                $existing_billing_address = $this->dependencies
                    ->getPlugin()
                    ->getAddressClass()
                    ->checkAndSaveAddress($user_billing_address, $this->context->customer->id, $customer_addresses);
            } else {
                // Use the same address for billing
                $existing_billing_address = $existing_shipping_address;
            }

            // Update cart with address IDs
            $id_address_invoice = $existing_billing_address;
            $id_address_delivery = $existing_shipping_address;
        } else {
            // create guest user
            $customer->is_guest = true;
            $customer->firstname = $cart_data['shipping']['first_name'];
            $customer->lastname = $cart_data['shipping']['last_name'];
            $customer->email = $cart_data['shipping']['email'];
            $customer->passwd = $this->tools->tool('passwdGen', 32, 'ALPHANUMERIC');
            if (!$customer_adapter->add($customer)) {
                return [
                    'result' => false,
                    'message' => 'Guest customer can\'t be created',
                ];
            }

            $cart->id_customer = (int) $customer->id;

            // Set customer in context...
            $this->context->customer = $customer;

            // then update the cookie with the new customer ID
            $this->context->cookie->id_customer = (int) $customer->id;
            $this->context->cookie->write();

            // Create shipping address
            $shipping_address_id = $this->dependencies
                ->getPlugin()
                ->getAddressClass()
                ->checkAndSaveAddress($user_shipping_address, (int) $customer->id);

            // Check if shipping address is different than billing address
            if (hash('sha256', json_encode($user_shipping_address)) != hash('sha256', json_encode($user_billing_address))) {
                // Create billing address
                $billing_address_id = $this->dependencies
                    ->getPlugin()
                    ->getAddressClass()
                    ->checkAndSaveAddress(
                        $user_billing_address,
                        (int) $customer->id,
                        []
                    );
            } else {
                // Use the same address for billing
                $billing_address_id = $shipping_address_id;
            }

            // Update cart with address IDs
            $id_address_invoice = $billing_address_id;
            $id_address_delivery = $shipping_address_id;
        }

        // Set selected carrier in cart
        $carrier = $this->dependencies
            ->getPlugin()
            ->getCarrier()
            ->get((int) $carrier['identifier']);
        $id_zone = $this->dependencies
            ->getPlugin()
            ->getAddress()
            ->getZoneById((int) $id_address_delivery);
        $carrier_in_range = $cart_adapter->isCarrierInRange(
            (int) $carrier->id,
            (int) $id_zone
        );
        $carrier_in_zone = $this->dependencies
            ->getPlugin()
            ->getCarrier()
            ->checkCarrierZone(
                (int) $carrier->id,
                (int) $id_zone
            );
        if (!(bool) $carrier_in_range || !(bool) $carrier_in_zone) {
            return [
                'result' => false,
                'message' => 'Given carrier is not available for this delivery address',
            ];
        }

        // then update the cart
        $cart_adapter->updateAddresses($cart, $id_address_delivery, $id_address_invoice);

        // then get the new amount for the request
        $request = $this->getRequest();
        $cart_data['amount'] = $this->dependencies->getHelpers()['amount']->convertAmount($request['total']['amount']);

        return [
            'result' => true,
            'data' => $cart_data,
        ];
    }
}
