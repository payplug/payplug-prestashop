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

class OneyPaymentMethod extends PaymentMethod
{
    private $oney_allowed_iso_codes = ['FR', 'IT', 'ES', 'NL'];

    public function __construct($dependencies)
    {
        parent::__construct($dependencies);
        $this->name = 'oney';
        $this->force_resource = true;
        $this->cancellable = false;

        $this->logger = $this->dependencies
            ->getPlugin()
            ->getLogger();
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

        if (!is_array($current_configuration)) {
            $this->logger->addLog('OneyPaymentMethod::getOption: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $amounts = json_decode($this->configuration->getDefault('amounts'), true);
        $payment_methods = json_decode($this->configuration->getDefault('payment_methods'), true);
        $default_configuration = [
            'oney' => (bool) $payment_methods['oney'],
            'oney_min_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['min'] : '',
            'oney_max_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['max'] : '',
            'oney_custom_min_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['min'] : '',
            'oney_custom_max_amounts' => isset($amounts['oney_x3_with_fees']) ? $amounts['oney_x3_with_fees']['max'] : '',
            'oney_product_animation' => $this->configuration->getDefault('oney_product_animation'),
            'oney_cart_animation' => $this->configuration->getDefault('oney_cart_animation'),
            'oney_schedule' => $this->configuration->getDefault('oney_schedule'),
            'oney_fees' => $this->configuration->getDefault('oney_fees'),
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $advanced_options = [];
        $thresholds = $this->getThresholds($current_configuration);
        if ($thresholds) {
            $advanced_options[] = $thresholds;
        }
        $schedules = $this->getSchedule((bool) $current_configuration['oney_schedule']);
        if ($schedules) {
            $advanced_options[] = $schedules;
        }

        $can_use_cta = !in_array(
            $this->configuration->getValue('oney_allowed_countries'),
            ['ES', 'BE']
        );
        if ($can_use_cta) {
            $product = $this->getProductCallToAction((bool) $current_configuration['oney_product_animation']);
            if ($product) {
                $advanced_options[] = $product;
            }
            $cart = $this->getCartCallToAction((bool) $current_configuration['oney_cart_animation']);
            if ($cart) {
                $advanced_options[] = $cart;
            }
        }

        return [
            'name' => 'paymentMethodsBlock',
            'title' => $this->translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $this->translation['description'],
                ],
                'sandbox' => [
                    'description' => $this->translation['description'],
                ],
            ],
            'options' => [
                'name' => 'oney',
                'title' => $this->translation['options']['title'],
                'image' => 'assets/images/lg-oney.png',
                'checked' => $current_configuration['oney'],
                'descriptions' => [
                    'live' => [
                        'description' => $this->translation['options']['description'],
                        'link_know_more' => [
                            'text' => $this->translation['link'],
                            'url' => $this->external_url['oney'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $this->translation['options']['description'],
                        'link_know_more' => [
                            'text' => $this->translation['link'],
                            'url' => $this->external_url['oney'],
                            'target' => '_blank',
                        ],
                    ],
                    'advanced' => [
                        'description' => $this->translation['advanced'],
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_oney',
                        'className' => '_paylaterLabel',
                        'label' => $this->translation['options']['with_fees']['label'],
                        'subText' => $this->translation['options']['with_fees']['subtext'],
                        'value' => 1,
                        'checked' => $this->configuration->getValue('oney_fees'),
                    ],
                    [
                        'name' => 'payplug_oney',
                        'className' => '_paylaterLabel',
                        'label' => $this->translation['options']['without_fees']['label'],
                        'subText' => $this->translation['options']['without_fees']['subtext'],
                        'value' => 0,
                        'checked' => !$this->configuration->getValue('oney_fees'),
                    ],
                ],
                'advanced_options' => $advanced_options,
            ],
        ];
    }

    // todo: add coverage to this method
    public function getOrderTab($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            // todo: add error log
            return [];
        }

        $order_tab = parent::getOrderTab($resource);

        if (!$resource->is_paid) {
            $state_addons = $resource->is_live ? '' : '_test';
            $order_tab['order_state'] = $this->configuration->getValue('order_state_oney_pg' . $state_addons);
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();

        switch ($resource->payment_method['type']) {
            case 'oney_x3_with_fees':
                $order_tab['module_name'] = $translation['module_name']['oney']['x3_with_fees'];

                break;
            case 'oney_x3_without_fees':
                $order_tab['module_name'] = $translation['module_name']['oney']['x3_without_fees'];

                break;
            case 'oney_x4_with_fees':
                $order_tab['module_name'] = $translation['module_name']['oney']['x4_with_fees'];

                break;
            case 'oney_x4_without_fees':
                $order_tab['module_name'] = $translation['module_name']['oney']['x4_without_fees'];

                break;
            default:
                $order_tab['module_name'] = $translation['module_name']['oney']['default'];

                break;
        }

        return $order_tab;
    }

    // todo: add coverage to this method
    public function getPaymentTab()
    {
        $payment_tab = parent::getPaymentTab();

        if (empty($payment_tab)) {
            return $payment_tab;
        }

        $oney_schedule = $this->tools->tool('getValue', 'payplugOney_type');
        $payment_tab['authorized_amount'] = $payment_tab['amount'];

        // Check if oney was elligible then return if not
        $is_valid_cart = $this->isValidOneyCart($this->context->cart)['result'];

        $is_elligible = $this->dependencies
            ->getValidators()['payment']
            ->isOneyElligible($is_valid_cart, true, true);

        if (!$is_elligible['result']) {
            $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([$is_elligible['message']]);

            return [];
        }

        // Check billing phonenumber
        $is_valid_phone = $this->dependencies
            ->getValidators()['payment']
            ->isPhoneNumber($payment_tab['billing']['mobile_phone_number'])['result'];
        if (!$is_valid_phone || !$this->dependencies
            ->getHelpers()['phone']::isMobilePhoneNumber(
                $payment_tab['billing']['country'],
                $payment_tab['billing']['mobile_phone_number']
            )) {
            $is_valid_phone = $this->dependencies
                ->getValidators()['payment']
                ->isPhoneNumber($payment_tab['billing']['landline_phone_number'])['result'];

            if ($is_valid_phone && $this->dependencies
                ->getHelpers()['phone']::isMobilePhoneNumber(
                    $payment_tab['billing']['country'],
                    $payment_tab['billing']['landline_phone_number']
                )) {
                $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['landline_phone_number'];
            }
        }

        // check shipping phonenumber
        $is_valid_phone = $this->dependencies
            ->getValidators()['payment']
            ->isPhoneNumber($payment_tab['shipping']['mobile_phone_number'])['result'];
        if (!$is_valid_phone || !$this->dependencies
            ->getHelpers()['phone']::isMobilePhoneNumber(
                $payment_tab['shipping']['country'],
                $payment_tab['shipping']['mobile_phone_number']
            )) {
            $is_valid_phone = $this->dependencies
                ->getValidators()['payment']
                ->isPhoneNumber($payment_tab['shipping']['landline_phone_number'])['result'];
            if ($is_valid_phone && $this->dependencies
                ->getHelpers()['phone']::isMobilePhoneNumber(
                    $payment_tab['shipping']['country'],
                    $payment_tab['shipping']['landline_phone_number']
                )) {
                $payment_tab['shipping']['mobile_phone_number'] = $payment_tab['shipping']['landline_phone_number'];
            }
        }

        if ($this->hasOneyRequiredFields($payment_tab)) {
            // check oney required fields
            $payment_data = $this->dependencies->getHelpers()['cookies']->getPaymentDataCookie();
            if (!$payment_data) {
                $payment_data = $this->tools->tool('getValue', 'oney_form');
            }

            if ((bool) $payment_data) {
                // hydrate with payment data
                $payment_tab = $this->hydratePaymentTabFromPaymentData($payment_tab, $payment_data);

                // then recheck
                if ($this->hasOneyRequiredFields($payment_tab)) {
                    $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie(['oney_required_field_' . $oney_schedule]);

                    return [];
                }
            } else {
                $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie(['oney_required_field_' . $oney_schedule]);

                return [];
            }
        }

        $payment_tab['force_3ds'] = false;
        $payment_tab['auto_capture'] = true;
        $payment_tab['payment_method'] = 'oney_' . $oney_schedule;
        $payment_tab['payment_context'] = $this->getOneyPaymentContext();
        $payment_tab['hosted_payment']['return_url'] = $this->context->link->getModuleLink(
            $this->dependencies->name,
            'validation',
            [
                'ps' => 1,
                'cartid' => (int) $this->context->cart->id,
                'isoney' => $this->tools->tool('getValue', 'io'),
            ],
            true
        );

        unset($payment_tab['allow_save_card'], $payment_tab['amount']);

        return $payment_tab;
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
        $method_name = str_replace('oney_', '', $resource_details['type']);
        $resource_details['type'] = $translation['detail']['method']['oney'][$method_name];

        unset($resource_details['card_brand'], $resource_details['card_mask'], $resource_details['card_date']);

        return $resource_details;
    }

    // todo: add coverage to this method
    public function getPaymentStatus($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            // todo: add error log
            return [];
        }

        if (isset($resource->payment_method, $payment->payment_method['is_pending'])
            && (bool) $resource->payment_method['is_pending']) {
            return [
                'id_status' => 10,
                'code' => 'oney_pending',
            ];
        }

        return parent::getPaymentStatus($resource);
    }

    /**
     * @description Get the Oney required fields from Context
     *
     * @return array
     */
    public function getOneyRequiredFields()
    {
        $addressAdapter = $this->dependencies->getPlugin()->getAddress();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $validateAdapter = $this->dependencies->getPlugin()->getValidate();

        $fields = [];
        $customer = $context->customer;
        if (!$validateAdapter->validate('isLoadedObject', $customer)) {
            return $fields;
        }
        $id_address_delivery = $context->cart->id_address_delivery;
        $id_address_invoice = $context->cart->id_address_invoice;
        $is_same = $id_address_delivery == $id_address_invoice;

        $shipping_fields = [];
        $shipping_address = $addressAdapter->get((int) $id_address_delivery);

        if (!$validateAdapter->validate('isLoadedObject', $shipping_address)) {
            return $fields;
        }
        $shipping_data = [
            'email' => $context->customer->email,
            'mobile_phone_number' => $shipping_address->phone_mobile
                ? $shipping_address->phone_mobile
                : $shipping_address->phone,
            'city' => $shipping_address->city,
        ];

        foreach ($shipping_data as $key => $data) {
            $errors = $this->checkOneyRequiredFields(['shipping-' . $key => $data]);

            if ($errors) {
                $message = reset($errors);

                switch ($key) {
                    case 'email':
                    case 'mobile_phone_number':
                        $shipping_fields[$key] = [
                            'text' => $message,
                            'input' => [
                                [
                                    'name' => $key,
                                    'value' => $data,
                                    'type' => 'text',
                                ],
                            ],
                        ];

                        break;

                    case 'city':
                        $shipping_fields['city'] = [
                            'text' => $message,
                            'input' => [
                                [
                                    'name' => 'first_name',
                                    'value' => $shipping_address->firstname,
                                    'type' => 'text',
                                ],
                                [
                                    'name' => 'last_name',
                                    'value' => $shipping_address->lastname,
                                    'type' => 'text',
                                ],
                                [
                                    'name' => 'address1',
                                    'value' => $shipping_address->address1,
                                    'type' => 'text',
                                ],
                                [
                                    'name' => 'postcode',
                                    'value' => $shipping_address->postcode,
                                    'type' => 'text',
                                ],
                                [
                                    'name' => $key,
                                    'value' => $data,
                                    'type' => 'text',
                                ],
                            ],
                        ];

                        break;
                }
            }
        }

        if ($is_same && !empty($shipping_fields)) {
            $fields['same'] = $shipping_fields;
        } else {
            if (!empty($shipping_fields)) {
                $fields['shipping'] = $shipping_fields;
            }

            $billing_fields = [];
            $billing_address = $addressAdapter->get((int) $id_address_invoice);

            if (!$validateAdapter->validate('isLoadedObject', $billing_address)) {
                return $fields;
            }

            $billing_data = [
                'mobile_phone_number' => $billing_address->phone_mobile
                    ? $billing_address->phone_mobile
                    : $billing_address->phone,
                'city' => $billing_address->city,
            ];

            foreach ($billing_data as $key => $data) {
                $errors = $this->checkOneyRequiredFields(['billing-' . $key => $data]);

                if ($errors) {
                    $message = reset($errors);

                    switch ($key) {
                        case 'mobile_phone_number':
                            $billing_fields[$key] = [
                                'text' => $message,
                                'input' => [
                                    [
                                        'name' => $key,
                                        'value' => $data,
                                        'type' => 'text',
                                    ],
                                ],
                            ];

                            break;

                        case 'city':
                            $billing_fields['city'] = [
                                'text' => $message,
                                'input' => [
                                    [
                                        'name' => 'first_name',
                                        'value' => $billing_address->firstname,
                                        'type' => 'text',
                                    ],
                                    [
                                        'name' => 'last_name',
                                        'value' => $billing_address->lastname,
                                        'type' => 'text',
                                    ],
                                    [
                                        'name' => 'address1',
                                        'value' => $billing_address->address1,
                                        'type' => 'text',
                                    ],
                                    [
                                        'name' => 'postcode',
                                        'value' => $billing_address->postcode,
                                        'type' => 'text',
                                    ],
                                    [
                                        'name' => $key,
                                        'value' => $data,
                                        'type' => 'text',
                                    ],
                                ],
                            ];

                            break;
                    }
                }
            }

            if (!empty($billing_fields)) {
                $fields['billing'] = $billing_fields;
            }
        }

        return $fields;
    }

    /**
     * @description Check Oney required fields in form
     *
     * todo: to clean or update
     *
     * @param mixed $payment_data
     *
     * @return array
     */
    public function checkOneyRequiredFields($payment_data)
    {
        $addressAdapter = $this->dependencies->getPlugin()->getAddress();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $country = $this->dependencies->getPlugin()->getCountry();
        $tools = $this->dependencies->getPlugin()->getTools();
        $validate = $this->dependencies->getPlugin()->getValidate();
        $validators = $this->dependencies->getValidators();
        $errors = [];

        if (!$payment_data || !is_array($payment_data)) {
            return [$this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('Please fill in the required fields', 'oneyrepository'), ];
        }

        foreach ($payment_data as $key => $data) {
            $parsed = explode('-', $key);
            $type = $parsed[0];
            $field = '';
            if (isset($parsed[1])) {
                $field = $parsed[1];
            }

            switch ($field) {
                case 'email':
                    $is_valid_email = $this->isValidOneyEmail($data);
                    if (!$is_valid_email['result']) {
                        $errors[] = $is_valid_email['message'];
                    }

                    break;

                case 'landline_phone_number':
                case 'mobile_phone_number':
                    $id_address = 'shipping' == $type ?
                        $context->cart->id_address_delivery :
                        $context->cart->id_address_invoice;
                    $address = $addressAdapter->get((int) $id_address);
                    $country = $country->getCountry($address->id_country);
                    $is_valid_phone = $validators['payment']
                        ->isPhoneNumber($data)['result'];
                    $valid = $is_valid_phone
                        && $validators['payment']
                            ->isValidMobilePhoneNumber($country->iso_code, $data);
                    if (!$valid) {
                        $errors[] = $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('Please enter your mobile phone number.', 'oneyrepository');
                    }

                    break;

                case 'first_name':
                    if (!$validate->validate('isName', $data)) {
                        $text = 'shipping' == $type ?
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your shipping firstname.', 'oneyrepository') :
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your billing firstname.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'last_name':
                    if (!$validate->validate('isName', $data)) {
                        $text = 'shipping' == $type ?
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your shipping lastname.', 'oneyrepository') :
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your billing lastname.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'address1':
                    if (!$validate->validate('isAddress', $data)) {
                        $text = 'shipping' == $type ?
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your shipping address.', 'oneyrepository') :
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your billing address.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'postcode':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = 'shipping' == $type ?
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your shipping postcode.', 'oneyrepository') :
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your billing postcode.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'city':
                    if (!$validate->validate('isCityName', $data)) {
                        $text = 'shipping' == $type ?
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your shipping city.', 'oneyrepository') :
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please enter your billing city.', 'oneyrepository');
                        $errors[] = $text;
                    } elseif ($tools->tool('strlen', $data, 'UTF-8') > 32) {
                        $text = $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('Your city name is too long (max 32 characters). ', 'oneyrepository')
                            . $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Please change it to another one or select another payment method.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                default:
                    break;
            }
        }

        return $errors;
    }

    /**
     * @description Display Oney payment options
     *
     * @param $cart
     * @param $amount
     * @param false $country
     *
     * @return array
     */
    public function getOneyPriceAndPaymentOptions($cart, $amount, $country = false)
    {
        $assign_adapter = $this->dependencies->getPlugin()->getAssign();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $tools = $this->dependencies->getPlugin()->getTools();
        $validate = $this->dependencies->getPlugin()->getValidate();

        if ($validate->validate('isLoadedObject', $cart)
            && $cart->id_address_invoice
            && $cart->id_address_delivery) {
            $is_valid_cart = $this->isValidOneyCart($cart)['result'];
            $is_valid_addresses = $this->isValidOneyAddresses($cart->id_address_delivery, $cart->id_address_invoice);
            $is_valid_amount = $this->isValidOneyAmount($amount ? $amount : $cart->getOrderTotal(true))['result'];

            $is_elligible = $this->dependencies
                ->getValidators()['payment']
                ->isOneyElligible($is_valid_cart, $is_valid_amount, $is_valid_addresses);
        } else {
            $is_elligible = $this->isValidOneyAmount($amount);
        }

        if ($is_elligible['result']) {
            $oney_payment_options = $this->getOneyPaymentOptionsList($amount, $country);
        } else {
            $oney_payment_options = false;
        }

        $error = isset($is_elligible['error']) ? $is_elligible['error'] : (
            $oney_payment_options
                ? false
                : $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('oney.getOneyPriceAndPaymentOptions.unavailable', 'oneyrepository')
        );

        $withFirstSchedule = 'it' == $context->language->iso_code;

        $assign_adapter->assign([
            'payplug_oney_required_field' => false,
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $tools->tool('displayPrice', $amount),
            ],
            'payplug_oney_allowed' => $is_elligible['result'] && $oney_payment_options,
            'payplug_oney_error' => $error,
            'withFirstSchedule' => $withFirstSchedule,
        ]);

        if ($oney_payment_options) {
            $assign_adapter->assign([
                'oney_payment_options' => $oney_payment_options,
            ]);
        }

        $popin_tpl = $this->displayOneyPopin();

        return [
            'result' => $is_elligible['result'] && $oney_payment_options,
            'error' => $error,
            'popin' => $popin_tpl,
        ];
    }

    /**
     * @description Display Oney popin template
     *
     * @return mixed
     */
    public function displayOneyPopin()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $tools = $this->dependencies->getPlugin()->getTools();

        $this->assignLegalNotice();
        $context->getContext()->smarty->assign([
            'use_fees' => (bool) $configuration->getValue('oney_fees'),
            'iso_code' => $tools->tool(
                'strtoupper',
                $context->language->iso_code
            ),
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/popin.tpl');
    }

    /**
     * @description Assign Oney Legal Notice
     */
    public function assignLegalNotice()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $helpers = $this->dependencies->getHelpers();
        $tools = $this->dependencies->getPlugin()->getTools();

        $limits = $this->getOneyPriceLimit();
        $learnMoreLink = 'IT' == $configuration->getValue('company_iso')
        && 'it' == $tools->tool('strtolower', $context->language->iso_code);
        $context->getContext()->smarty->assign([
            'learnMoreLink' => (bool) $learnMoreLink,
            'oneyWithFees' => (bool) $configuration->getValue('oney_fees'),
            'oneyMinAmounts' => $tools->tool('displayPrice', $helpers['amount']->formatOneyAmount($limits['min'])['result']),
            'oneyMaxAmounts' => $tools->tool('displayPrice', $helpers['amount']->formatOneyAmount($limits['max'])['result']),
            'oneyUrl' => 'https://www.oney.' . $context->language->iso_code,
        ]);
    }

    /**
     * @description Get Oney payment options
     *
     * @param int $amount
     * @param false $country
     *
     * @return array
     */
    public function getOneyPaymentOptionsList($amount = 0, $country = false)
    {
        $tools = $this->dependencies->getPlugin()->getTools();

        // get Oney resource
        $payment_list = [];
        if (!is_numeric($amount) || !$amount) {
            return $payment_list;
        }

        $amount = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($amount);
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();

        if (!$country) {
            $iso_code_list = $configuration->getValue('oney_allowed_countries');
            if (!$iso_code_list) {
                return $payment_list;
            }

            $iso_list = explode(',', $iso_code_list);
            if (isset($iso_list)) {
                $country = reset($iso_list);
            }
        }
        $country = $tools->tool('strtoupper', $country);

        $available_oney_payments = $this->getOperations();
        $oney_simulations = $this->getOneySimulations($amount, $country, $available_oney_payments);
        if (!$oney_simulations['result']) {
            return $payment_list;
        }

        $use_fees = (bool) $configuration->getValue('oney_fees');

        foreach (array_keys($oney_simulations['simulations']) as $key) {
            $with_fees = false !== (bool) strpos($key, 'with_fees');
            if (($use_fees && !$with_fees) || (!$use_fees && $with_fees)) {
                unset($oney_simulations['simulations'][$key]);
            }
        }

        foreach ($oney_simulations['simulations'] as $method => $oney_simulation) {
            if (isset($oney_simulation['installments']) && $oney_simulation['installments']) {
                $payment_list[$method] = $this->formatOneyResource($method, $oney_simulation, $amount);
                if (isset($use_fees) && !$use_fees) {
                    $payment_list[$method]['effective_annual_percentage_rate'] = 0;
                }
            }
        }

        return $payment_list;
    }

    /**
     * @description Format Oney simulation from resource
     *
     * @param bool $operation
     * @param array $resource
     * @param bool $total_amount
     *
     * @return array
     */
    public function formatOneyResource($operation = false, $resource = [], $total_amount = false)
    {
        $tools = $this->dependencies->getPlugin()->getTools();

        if (!in_array($operation, $this->getOperations()) || !$operation) {
            return false;
        }
        if (!is_array($resource) || empty($resource)) {
            return false;
        }

        if ($total_amount && !is_int($total_amount)) {
            return false;
        }

        $type = explode('_', $operation);

        $resource['nominal_annual_percentage_rate'] = number_format($resource['nominal_annual_percentage_rate'], 2);
        $resource['effective_annual_percentage_rate'] = number_format($resource['effective_annual_percentage_rate'], 2);

        $resource['split'] = (int) str_replace('x', '', $type[0]);
        $resource['title'] = sprintf($this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->l('Payment in %sx', 'oneyrepository'), $resource['split']);

        // format price
        $total_cost = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($resource['total_cost'], true);
        $resource['total_cost'] = [
            'amount' => number_format($total_cost, 2),
            'value' => $tools->tool('displayPrice', $total_cost),
        ];
        $down_payment_amount = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($resource['down_payment_amount'], true);
        $resource['down_payment_amount'] = [
            'amount' => number_format($down_payment_amount, 2),
            'value' => $tools->tool('displayPrice', $down_payment_amount),
        ];
        foreach ($resource['installments'] as &$installment) {
            $amount = $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($installment['amount'], true);
            $installment['amount'] = number_format($amount, 2);
            $installment['value'] = $tools->tool('displayPrice', $amount);
        }

        $total_amount = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($total_amount, true);
        $total_amount += $total_cost;
        $resource['total_amount'] = [
            'amount' => number_format($total_amount, 2),
            'value' => $tools->tool('displayPrice', $total_amount),
        ];

        return $resource;
    }

    /**
     * @description Get Oney Payment Simulations
     *
     * @param $amount
     * @param $country
     * @param $operation
     *
     * @return array|mixed
     */
    public function getOneySimulations($amount, $country, $operation)
    {
        if (!$amount || !is_int($amount)) {
            return [
                'result' => false,
                'error' => '$amount is not a valid int',
            ];
        }

        if (!$country || !is_string($country)) {
            return [
                'result' => false,
                'error' => '$country is not a valid string',
            ];
        }

        if (!$operation || !is_array($operation)) {
            return [
                'result' => false,
                'error' => '$operation is not a valid array',
            ];
        }

        $cacheRepository = $this->dependencies->getPlugin()->getCache();

        $cache_key = $cacheRepository->setCacheKey($amount, $country, $operation);

        if (!$cache_key['result']) {
            return [
                'result' => false,
                'error' => $cache_key['message'],
            ];
        }

        // Checks if the current simulation is already saved in the database
        // If not, we do a simulation for Oney, and we will store it to the DB
        $cache = $cacheRepository->getCacheByKey($cache_key['result']);

        if ($cache['result']) {
            return json_decode($cache['result']['cache_value'], true);
        }

        $data = [
            'amount' => $amount,
            'country' => $this->getOneyCountry($country),
            'operations' => $operation,
        ];
        $simulations = $this->dependencies->apiClass->getOneySimulations($data);

        if (!$simulations['result']) {
            $this->logger->setProcess('oney');
            $this->logger->addLog($simulations['message'], 'error');

            return [
                'result' => false,
                'error' => $simulations['message'],
            ];
        }

        $simulations = $simulations['resource'];
        if (isset($simulations['object']) && 'error' == $simulations['object']) {
            return [
                'result' => false,
                'error' => $simulations['message'],
            ];
        }
        if ($simulations) {
            ksort($simulations);
            $to_cache = [
                'result' => true,
                'simulations' => $simulations,
            ];

            // $cache_id = cache_key in db
            // $to_cache = cache_value in db
            if (!$cacheRepository->setCache($cache_key['result'], $to_cache)) {
                $this->logger->setProcess('oney');
                $error_message = 'Error during setting Oney Simulation in DB cache [OneyRepository]';
                $error_level = 'error';
                $this->logger->addLog($error_message, $error_level);
            }
        }

        return [
            'result' => true,
            'simulations' => $simulations,
        ];
    }

    /**
     * @description Temp get valid iso code for french overseas,
     * todo: remove when it's fix in API
     *
     * @param $iso_country
     *
     * @return string
     */
    public function getOneyCountry($iso_country)
    {
        if (!$iso_country || !is_string($iso_country)) {
            return false;
        }
        $overseas_iso = ['GP', 'MQ', 'GF', 'RE', 'YT'];
        if (in_array($iso_country, $overseas_iso, true)) {
            return 'FR';
        }

        return $iso_country;
    }

    public function getOperations()
    {
        $validators = $this->dependencies->getValidators();

        $options = [
            'x3_with_fees',
            'x3_without_fees',
            'x4_with_fees',
            'x4_without_fees',
        ];

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $oney_allowed_countries = $configuration->getValue('oney_allowed_countries');
        if ('payplug' != $this->dependencies->name
            && $validators['payment']->isAllowedCountry($oney_allowed_countries, 'BE')['result']) {
            $options = [
                'x3_with_fees',
                'x3_without_fees',
            ];
        }

        return $options;
    }

    /**
     * @description Get Oney price limit
     *
     * @param bool $custom
     * @param false $id_currency
     *
     * @return array
     */
    public function getOneyPriceLimit($custom = true, $id_currency = false)
    {
        $configurationAdapter = $this->dependencies->getPlugin()->getConfiguration();
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $country = $this->dependencies->getPlugin()->getCountry();
        $currencyAdapter = $this->dependencies->getPlugin()->getCurrency();
        $tools = $this->dependencies->getPlugin()->getTools();
        $validate = $this->dependencies->getPlugin()->getValidate();

        if ($validate->validate('isLoadedObject', $id_currency)) {
            $currency = $id_currency;
        } else {
            if (!is_int($id_currency) && $validate->validate('isLanguageIsoCode', $id_currency)) {
                $id_currency = $country->getByIso($id_currency);
            }
            if (!$id_currency) {
                $id_currency = $configurationAdapter->get('PS_CURRENCY_DEFAULT');
            }

            $currency = $currencyAdapter->get((int) $id_currency);
        }

        $limits = [
            'min' => false,
            'max' => false,
        ];

        if (!$validate->validate('isLoadedObject', $currency)) {
            return $limits;
        }

        $iso_code = $tools->tool('strtoupper', $currency->iso_code);
        $amounts = json_decode($configuration->getValue('amounts'), true);

        if ((bool) $custom) {
            $oney_min_amounts = explode(
                ',',
                $tools->tool('strtoupper', $configuration->getValue('oney_custom_min_amounts'))
            );
        } else {
            $oney_min_amounts = explode(
                ',',
                $tools->tool('strtoupper', $amounts['oney_x3_with_fees']['min'])
            );
        }
        foreach ($oney_min_amounts as $min_amount) {
            $min = explode(':', $min_amount);
            if ($min[0] == $iso_code) {
                $limits['min'] = (int) $min[1];

                break;
            }
        }
        if ($custom) {
            $oney_max_amounts = explode(',', $tools->tool('strtoupper', $configuration->getValue('oney_custom_max_amounts')));
        } else {
            $amounts = json_decode($configuration->getValue('amounts'), true);
            $oney_max_amounts = explode(',', $tools->tool('strtoupper', $amounts['oney_x3_with_fees']['max']));
        }
        foreach ($oney_max_amounts as $max_amount) {
            $max = explode(':', $max_amount);
            if ($max[0] == $iso_code) {
                $limits['max'] = (int) $max[1];

                break;
            }
        }

        return $limits;
    }

    /**
     * @description Check if cart is valid for Oney
     *
     * @param Cart $cart
     *
     * @return array
     */
    public function isValidOneyCart($cart)
    {
        $cartAdapter = $this->dependencies->getPlugin()->getCart();
        $validate = $this->dependencies->getPlugin()->getValidate();
        $validators = $this->dependencies->getValidators();

        if (!$validate->validate('isLoadedObject', $cart)) {
            return [
                'result' => false,
                'error' => $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('The cart is unvalid', 'oneyrepository'),
            ];
        }

        $nb_products = $cartAdapter->nbProducts($cart);
        $max = 1000;
        $is_valid_cart_quantity = $validators['payment']->isValidProductQuantity($nb_products, $max);

        if (!$is_valid_cart_quantity['result']) {
            $error = 'The payment with Oney is not available because you have more than 1000 items in your cart.';

            return [
                'result' => false,
                'error' => $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l($error, 'oneyrepository'),
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Check if billing and shipping addresses are valid.
     *
     * @param int $id_shipping
     * @param int $id_billing
     *
     * @return bool
     */
    public function isValidOneyAddresses($id_shipping = 0, $id_billing = 0)
    {
        $addressAdapter = $this->dependencies->getPlugin()->getAddress();

        $shipping = $addressAdapter->get((int) $id_shipping);
        $billing = $addressAdapter->get((int) $id_billing);
        $shipping_iso_code = $this
            ->dependencies
            ->configClass
            ->getIsoCodeByCountryId((int) $shipping->id_country);

        $billing_iso_code = $this
            ->dependencies
            ->configClass
            ->getIsoCodeByCountryId((int) $billing->id_country);

        return $shipping_iso_code == $billing_iso_code;
    }

    /**
     * @description Check if amount is valid for Oney
     *
     * @param float $amount
     *
     * @return array
     */
    public function isValidOneyAmount($amount)
    {
        $helpers = $this->dependencies->getHelpers();
        $validators = $this->dependencies->getValidators();

        $limits = $this->getOneyPriceLimit();
        $is_valid_amount = $validators['payment']->isAmount(
            $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($amount),
            [
                'min' => $this->dependencies
                    ->getHelpers()['amount']
                    ->convertAmount($helpers['amount']->formatOneyAmount($limits['min'])['result']),
                'max' => $this->dependencies
                    ->getHelpers()['amount']
                    ->convertAmount($helpers['amount']->formatOneyAmount($limits['max'])['result']),
            ]
        );

        if (!$is_valid_amount['result']) {
            return [
                'result' => false,
                'error' => sprintf(
                    $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('oney.isValidOneyAmount.unvalid', 'oneyrepository'),
                    $helpers['amount']->formatOneyAmount($limits['min'])['result'],
                    $helpers['amount']->formatOneyAmount($limits['max'])['result']
                ),
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Get Oney Delivery Context
     *
     * @return array
     */
    public function getOneyDeliveryContext()
    {
        $carrierAdapter = $this->dependencies->getPlugin()->getCarrier();
        $cartAdapter = $this->dependencies->getPlugin()->getCart();
        $cart = $cartAdapter->get((int) $cartAdapter->get()->id);
        $validate = $this->dependencies->getPlugin()->getValidate();

        if ($cartAdapter->isVirtualCart($cart)) {
            return [
                'delivery_label' => $this->dependencies
                    ->getPlugin()
                    ->getConfiguration()
                    ->get('PS_SHOP_NAME'),
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'edelivery',
            ];
        }

        $carrier = $carrierAdapter->get((int) $cart->id_carrier);

        if ($validate->validate('isLoadedObject', $carrier)) {
            return [
                'delivery_label' => $carrier->name ? $carrier->name : $this->dependencies
                    ->getPlugin()
                    ->getConfiguration()
                    ->get('PS_SHOP_NAME'),
                'expected_delivery_date' => date(
                    'Y-m-d',
                    strtotime('+' . $carrierAdapter->getDefaultDelay() . ' day')
                ),
                'delivery_type' => $carrierAdapter->getDefaultDeliveryType(),
            ];
        }

        return [
            'delivery_label' => $this->dependencies
                ->getPlugin()
                ->getConfiguration()
                ->get('PS_SHOP_NAME'),
            'expected_delivery_date' => date('Y-m-d'),
            'delivery_type' => 'edelivery',
        ];
    }

    /**
     * @description Get Oney payment Context
     *
     * @return array
     */
    public function getOneyPaymentContext()
    {
        $cartAdapter = $this->dependencies->getPlugin()->getCart();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $tools = $this->dependencies->getPlugin()->getTools();
        $validate = $this->dependencies->getPlugin()->getValidate();

        $cart_context = [];
        $cart = $cartAdapter->get((int) $context->cart->id);
        if (!$validate->validate('isLoadedObject', $cart)) {
            return ['cart' => $cart_context];
        }

        $products = $cartAdapter->getProducts($cart);
        $delivery_context = $this->getOneyDeliveryContext();

        foreach ($products as $product) {
            $unit_price = $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($product['price_wt']);
            $productName = (string) $product['name'] . (isset($product['attributes'])
                    ? ' - ' . $product['attributes']
                    : '');

            $item = [
                'merchant_item_id' => (string) $product['id_product'],
                'name' => $tools->substr($productName, 0, 250),
                'price' => (int) $unit_price,
                'quantity' => (int) $product['cart_quantity'],
                'total_amount' => (string) $unit_price * $product['cart_quantity'],
                'brand' => (isset($product['manufacturer_name']) && $product['manufacturer_name']) ?
                    $tools->substr($product['manufacturer_name'], 0, 250) :
                    $this->dependencies
                        ->getPlugin()
                        ->getConfiguration()
                        ->get('PS_SHOP_NAME'),
            ];

            $cart_context[] = array_merge($item, $delivery_context);
        }

        return ['cart' => $cart_context];
    }

    /**
     * @description   get custom oney amount from BO form
     *
     * @param $custom_oney_amount
     *
     * @return string
     */
    public function setCustomOneyLimit($custom_oney_amount)
    {
        $currencyAdapter = $this->dependencies->getPlugin()->getCurrency();
        $tools = $this->dependencies->getPlugin()->getTools();

        $id_currency = $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->get('PS_CURRENCY_DEFAULT');
        $currency = $currencyAdapter->get((int) $id_currency);

        $iso_code = $tools->tool('strtoupper', $currency->iso_code);

        $oneyAmount = [
            'currency' => $iso_code . ':',
            'amount' => $custom_oney_amount,
        ];

        return implode($oneyAmount);
    }

    /**
     * @description Get the Oney required fields from Context
     *
     * @param array $payment_data
     *
     * @return bool
     */
    public function hasOneyRequiredFields($payment_data = [])
    {
        if (!$payment_data || !is_array($payment_data) || empty($payment_data)) {
            return false;
        }

        $tools = $this->dependencies->getPlugin()->getTools();
        $validators = $this->dependencies->getValidators();

        // Check the shipping fields
        $shipping = $payment_data['shipping'];

        // Validate email format
        $is_valid_email = $this->isValidOneyEmail($shipping['email']);
        if (!$is_valid_email['result']) {
            return true;
        }

        // Validate phone number
        $is_valid_phone = $validators['payment']
            ->isPhoneNumber($shipping['mobile_phone_number'])['result'];
        $is_valid_mobile_phone_number = $validators['payment']
            ->isValidMobilePhoneNumber($shipping['country'], $shipping['mobile_phone_number'])['result'];
        $valid_shipping_mobile = $is_valid_phone && $this->dependencies->configClass->isValidMobilePhoneNumber(
            $shipping['country'],
            $shipping['mobile_phone_number']
        );

        if (!$valid_shipping_mobile) {
            return true;
        }

        // Validate address
        if ($tools->tool('strlen', $shipping['city'], 'UTF-8') > 32) {
            return true;
        }

        // Check the billing fields
        $billing = $payment_data['billing'];

        // Validate phone number
        $is_valid_phone = $validators['payment']
            ->isPhoneNumber($billing['mobile_phone_number'])['result'];
        $is_valid_mobile_phone_number = $validators['payment']
            ->isValidMobilePhoneNumber($shipping['country'], $shipping['mobile_phone_number'])['result'];
        $valid_billing_mobile = $is_valid_phone && $this->dependencies->configClass->isValidMobilePhoneNumber(
            $billing['country'],
            $billing['mobile_phone_number']
        );

        if (!$valid_billing_mobile) {
            return true;
        }

        // Validate address
        if ($tools->tool('strlen', $billing['city'], 'UTF-8') > 32) {
            return true;
        }

        return false;
    }

    /**
     * @description Check if Oney allow a given currency
     *
     * @param $id_currency
     *
     * @return bool
     */
    public function isOneyAllowedCurrency($id_currency)
    {
        $currencyAdapter = $this->dependencies->getPlugin()->getCurrency();
        $tools = $this->dependencies->getPlugin()->getTools();
        $validate = $this->dependencies->getPlugin()->getValidate();
        $validators = $this->dependencies->getValidators();

        if ($validate->validate('isLoadedObject', $id_currency)) {
            $currency = $id_currency;
        } elseif (is_int($id_currency)) {
            $currency = $currencyAdapter->get((int) $id_currency);
        } else {
            return false;
        }

        if (!$validate->validate('isLoadedObject', $currency)) {
            return false;
        }

        // we use the Oney limit to get allowed currencies
        $currencies = [];
        $amounts = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('amounts'), true);
        foreach (explode(';', $amounts['oney_x3_with_fees']['min']) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $currencies[] = $tools->tool('strtoupper', $cur[1]);
        }
        $iso_code = $tools->tool('strtoupper', $currency->iso_code);
        $is_valid_amount = $validators['payment']->isCurrency($iso_code, $currencies);

        return $is_valid_amount['result'];
    }

    /**
     * @description Check if Oney is allowed
     *
     * @return bool
     */
    public function isOneyAllowed()
    {
        $context = $this->dependencies->getPlugin()->getContext()->get();

        $payment_methods = json_decode($this->dependencies->getPlugin()->getConfigurationClass()->getValue('payment_methods'), true);

        return $this->dependencies->configClass->isAllowed()
            && (bool) $payment_methods['oney']
            && $this->isOneyAllowedCurrency($context->currency);
    }

    /**
     * @description Check given email is valid to use Oney payment
     *
     * @param $email
     *
     * @return array
     */
    public function isValidOneyEmail($email)
    {
        $validators = $this->dependencies->getValidators();

        $is_valid_email = $validators['account']->isEmail($email);
        if (!$is_valid_email['result']) {
            return [
                'result' => false,
                'message' => $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('Your email address is not a valid email', 'oneyrepository'),
            ];
        }
        $is_oney_email = $validators['payment']->isOneyEmail($email);
        if (!$is_oney_email['result']) {
            $code = isset($is_oney_email['code']) ? $is_oney_email['code'] : 'invalid';
            switch ($code) {
                case 'length-char':
                    $error = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('Your email address is too long and the + character is not valid', 'oneyrepository');
                    $error .= $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l(' please change it to another address (max 100 characters).', 'oneyrepository');

                    break;
                case 'char':
                    $error = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('The + character is not valid. Please change your email address (100 characters max).', 'oneyrepository');

                    break;
                case 'length':
                    $error = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('Your email address is too long. Please change your email address (100 characters max).', 'oneyrepository');

                    break;
                case 'format':
                default:
                    $error = $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('Your email address is not a valid email', 'oneyrepository');

                    break;
            }

            return [
                'result' => false,
                'message' => $error,
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
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
        if (!is_array($payment_options)) {
            return [];
        }

        $this->setParameters();

        $use_taxes = (bool) $this->dependencies
            ->getPlugin()
            ->getConfiguration()
            ->get('PS_TAX');
        $cart_amount = $this->context->cart->getOrderTotal($use_taxes);

        $is_valid_cart = $this->isValidOneyCart($this->context->cart)['result'];
        $is_valid_amount = $this->isValidOneyAmount($cart_amount ? $cart_amount : $this->context->cart->getOrderTotal(true))['result'];

        $is_elligible = $this->dependencies
            ->getValidators()['payment']
            ->isOneyElligible($is_valid_cart, $is_valid_amount, true);
        $error = isset($is_elligible['result']) ? false : $is_elligible['error_type'];
        $err_label = $this->getErrorLabel($error);

        $optimized = $this->configuration->getValue('oney_optimized') && !$error;

        $use_fees = (bool) $this->configuration->getValue('oney_fees');
        $delivery_address = $this->dependencies
            ->getPlugin()
            ->getAddress()
            ->get($this->context->cart->id_address_delivery);
        $iso = $this->dependencies
            ->getPlugin()
            ->getTools()
            ->tool('strtoupper', $this->context->language->iso_code);

        if (!in_array($iso, $this->oney_allowed_iso_codes)) {
            $iso = $this->configuration->getValue('company_iso');
        }

        $available_oney_payments = $this->getOperations();

        foreach ($available_oney_payments as $oney_payment) {
            $with_fees = false !== (bool) strpos($oney_payment, 'with_fees');
            if (($use_fees && !$with_fees) || (!$use_fees && $with_fees)) {
                continue;
            }

            $this->name = 'oney_' . $oney_payment;
            $payment_options = parent::getPaymentOption($payment_options);

            if (!isset($payment_options[$this->name])) {
                continue;
            }

            $type = explode('_', $oney_payment);
            $split = (int) str_replace('x', '', $type[0]);

            $oneyLogo = $oney_payment . (!$use_fees ? '_side_' . $iso : '') . ($error ? '_alt' : '') . '.svg';
            $text = $use_fees
                ? $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.getPaymentOptions.payWithOney', 'oneypaymentmethod')
                : $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.getPaymentOptions.payWithOneyWithout', 'oneypaymentmethod');

            $oneyLabel = $error ? $err_label : sprintf($text, $split);

            if ($optimized) {
                $adapter = $this->dependencies->loadAdapterPresta();
                if ($adapter && method_exists($adapter, 'getPaymentOption')) {
                    $oneyData = $adapter->getPaymentOption();
                    $oneyLogo = $oneyData['oneyLogo'];
                    $oneyLabel = $oneyData['oneyCallToActionText'];
                }
            }

            $payment_options[$this->name]['name'] = 'oney';
            $payment_options[$this->name]['inputs']['method']['value'] = 'oney';
            $payment_options[$this->name]['is_optimized'] = $optimized;
            $payment_options[$this->name]['type'] = $oney_payment;
            $payment_options[$this->name]['amount'] = $cart_amount;
            $payment_options[$this->name]['iso_code'] = $this->dependencies
                ->configClass
                ->getIsoCodeByCountryId((int) $delivery_address->id_country);
            $payment_options[$this->name]['inputs']['oney_type'] = [
                'name' => $this->dependencies->name . 'Oney_type',
                'type' => 'hidden',
                'value' => $oney_payment,
            ];
            $payment_options[$this->name]['extra_classes'] = sprintf('oney%sx', $split);
            $payment_options[$this->name]['payment_controller_url'] = $this->context
                ->link
                ->getModuleLink($this->dependencies->name, 'payment', [
                    'type' => 'oney',
                    'io' => sprintf('%s', $split),
                ], true);
            $payment_options[$this->name]['logo'] = $this->img_path . 'oney/' . $oneyLogo;
            $payment_options[$this->name]['callToActionText'] = $oneyLabel;
            $payment_options[$this->name]['err_label'] = $err_label;
        }

        unset($payment_options['oney']);

        return $payment_options;
    }

    /**
     * @description Get cart page CTA
     *
     * @param false $active
     *
     * @return array
     */
    protected function getCartCallToAction($active = false)
    {
        if (!is_bool($active)) {
            $this->logger->addLog('OneyPaymentMethod::getCartCallToAction: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/oney/';

        return [
            'name' => 'oney_cart_animation',
            'image_url' => $img_path . $this->dependencies->name . '-cartOneyCta.jpg',
            'title' => $this->translation['oneyPopupCart']['title'],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description Get Product page CTA
     *
     * @param false $active
     *
     * @return array
     */
    protected function getProductCallToAction($active = false)
    {
        if (!is_bool($active)) {
            $this->logger->addLog('OneyPaymentMethod::getProductCallToAction: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/oney/';

        return [
            'name' => 'oney_product_animation',
            'image_url' => $img_path . $this->dependencies->name . '-productOneyCta.jpg',
            'title' => $this->translation['oneyPopupProduct']['title'],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description Get Oney schedule
     *
     * @param false $active
     *
     * @return array
     */
    protected function getSchedule($active = false)
    {
        if (!is_bool($active)) {
            $this->logger->addLog('OneyPaymentMethod::getSchedule: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/oney/';

        $iso_code = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->language->iso_code;

        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($iso_code);

        return [
            'name' => 'oney_schedule',
            'image_url' => $img_path . $this->dependencies->name . '-optimized.jpg',
            'title' => $this->translation['oneySchedule']['title'],
            'descriptions' => [[
                'description' => $this->translation['oneySchedule']['description'],
                'link_know_more' => [
                    'text' => $this->translation['link'],
                    'url' => $external_url['oney'] . '#h_2595dd3d-a281-43ab-a51a-4986fecde5ee',
                    'target' => '_blank',
                ],
            ]],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description Get Oney Treshold
     *
     * @param array $current_configuration
     *
     * @return array
     */
    protected function getThresholds($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            $this->logger->addLog('OneyPaymentMethod::getThresholds: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $default_configuration = [
            'oney_min_amounts' => $current_configuration['oney_min_amounts'],
            'oney_max_amounts' => $current_configuration['oney_max_amounts'],
            'oney_custom_min_amounts' => $current_configuration['oney_custom_min_amounts'],
            'oney_custom_max_amounts' => $current_configuration['oney_custom_max_amounts'],
        ];

        $thresholds = [];
        foreach ($default_configuration as $key => $config) {
            $amount_key = str_replace('oney_', '', str_replace('_amounts', '', $key));

            if (!$config) {
                $thresholds[$amount_key] = 0;

                continue;
            }

            $amount = explode(':', $config);
            $amount = (int) $amount[1];
            $amount = $this->dependencies->getHelpers()['amount']->formatOneyAmount($amount)['result'];
            $thresholds[$amount_key] = $amount;
        }

        return [
            'name' => 'thresholds',
            'image_url' => $this->img_path . 'oney/' . $this->dependencies->name . '-thresholds.jpg',
            'title' => $this->translation['thresholds']['title'],
            'descriptions' => [
                'description' => $this->translation['thresholds']['description'],
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => $thresholds['custom_min'],
                    'placeholder' => $thresholds['custom_min'],
                    'default' => $thresholds['min'],
                ],
                'inter' => $this->translation['thresholds']['inter'],
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => $thresholds['custom_max'],
                    'placeholder' => $thresholds['custom_max'],
                    'default' => $thresholds['max'],
                ],
                'error' => [
                    'text' => sprintf(
                        $this->translation['thresholds']['error']['default'],
                        $thresholds['min'],
                        $thresholds['max']
                    ),
                    'maxtext' => sprintf(
                        $this->translation['thresholds']['error']['max'],
                        $thresholds['min'],
                        $thresholds['max']
                    ),
                    'mintext' => sprintf(
                        $this->translation['thresholds']['error']['min'],
                        $thresholds['min'],
                        $thresholds['max']
                    ),
                ],
            ],
            'switch' => false,
        ];
    }

    /**
     * @description Get error label for given error type
     *
     * @param string $error
     *
     * @return string
     */
    protected function getErrorLabel($error = '')
    {
        if (!is_string($error) || !$error) {
            return $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l('payplug.getPaymentOptions.errorOccurred', 'oneypaymentmethod');
        }

        switch ($error) {
            case 'invalid_addresses':
                $err_label =
                    $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.getPaymentOptions.invalidAddresses', 'oneypaymentmethod');

                break;
            case 'invalid_amount_bottom':
            case 'invalid_amount_top':
                $limits = $this->dependencies->getPlugin()->getOney()->getOneyPriceLimit(true);
                $err_label = sprintf(
                    $this->dependencies
                        ->getPlugin()
                        ->getTranslationClass()
                        ->l('payplug.getPaymentOptions.invalidAmount', 'oneypaymentmethod'),
                    $this->dependencies->getHelpers()['amount']->formatOneyAmount($limits['min'])['result'],
                    $this->dependencies->getHelpers()['amount']->formatOneyAmount($limits['max'])['result']
                );

                break;
            case 'invalid_carrier':
                $err_label = $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.getPaymentOptions.invalidCarrier', 'oneypaymentmethod');

                break;
            case 'invalid_cart':
                $err_label = $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.getPaymentOptions.invalidCart', 'oneypaymentmethod');

                break;
            default:
                $err_label = $this->dependencies
                    ->getPlugin()
                    ->getTranslationClass()
                    ->l('payplug.getPaymentOptions.errorOccurred', 'oneypaymentmethod');

                break;
        }

        return $err_label;
    }

    /**
     * @description Set parameters for usage
     */
    protected function setParameters()
    {
        parent::setParameters();
        $this->translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getPaylaterTranslations();
    }

    /**
     * @description Hydrate Oney Payment Tab from Cookie Payment Data
     *
     * @param array $payment_tab
     * @param array $payment_data
     *
     * @return array
     */
    private function hydratePaymentTabFromPaymentData($payment_tab = [], $payment_data = [])
    {
        if (!is_array($payment_tab) || empty($payment_tab)) {
            return $payment_tab;
        }

        if (!is_array($payment_data) || empty($payment_data)) {
            return $payment_tab;
        }

        $country = $this->dependencies->getPlugin()->getCountry();

        foreach ($payment_data as $k => $field) {
            $keys = explode('-', $k);
            $type = $keys[0];
            $field_name = $keys[1];

            if (false != strpos($field_name, 'phone')) {
                switch ($type) {
                    case 'billing':
                        $id_country = $country->getByIso($payment_tab['billing']['country']);
                        $billing_country = $country->get((int) $id_country);
                        $field = $this->dependencies->configClass->formatPhoneNumber($field, $billing_country);

                        break;

                    case 'same':
                    case 'shipping':
                    default:
                        $id_country = $country->getByIso($payment_tab['shipping']['country']);
                        $shipping_country = $country->get((int) $id_country);
                        $field = $this->dependencies->configClass->formatPhoneNumber($field, $shipping_country);

                        break;
                }
            }

            if ('email' == $field_name) {
                $payment_tab['billing']['email'] = $field;
                $payment_tab['shipping']['email'] = $field;
            } elseif ('same' == $type) {
                $payment_tab['billing'][$field_name] = $field;
                $payment_tab['shipping'][$field_name] = $field;
            } else {
                $payment_tab[$type][$field_name] = $field;
            }
        }

        return $payment_tab;
    }
}
