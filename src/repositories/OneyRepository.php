<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use PayPlug\src\application\dependencies\BaseClass;
use PayPlug\src\exceptions\BadParameterException;
use PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException;

class OneyRepository extends BaseClass
{
    private $addressAdapter;
    private $amountCurrencyClass;
    private $cache;
    private $dependencies;
    private $log;
    private $logger;
    private $configurationAdapter;
    private $contextAdapter;
    private $countryAdapter;
    private $currencyAdapter;
    private $toolsAdapter;
    private $validateAdapter;
    private $assign;

    public function __construct(
        $addressAdapter,
        $assign,
        $cache,
        $carrierAdapter,
        $cartAdapter,
        $configurationAdapter,
        $contextAdapter,
        $countryAdapter,
        $currencyAdapter,
        $dependencies,
        $logger,
        $myLogPHP,
        $oneyEntity,
        $toolsAdapter,
        $validateAdapter
    ) {
        $this->dependencies = $dependencies;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->addressAdapter = $addressAdapter;
        $this->cartAdapter = $cartAdapter;
        $this->carrierAdapter = $carrierAdapter;
        $this->configurationAdapter = $configurationAdapter;
        $this->contextAdapter = $contextAdapter;
        $this->countryAdapter = $countryAdapter;
        $this->currencyAdapter = $currencyAdapter;
        $this->toolsAdapter = $toolsAdapter;
        $this->validateAdapter = $validateAdapter;
        $this->oneyEntity = $oneyEntity;
        $this->log = $myLogPHP;
        $this->assign = $assign;

        $this->setParams();
    }

    /**
     * @description Assign Oney javascript variable
     */
    public function assignOneyJSVar()
    {
        $js_var = [
            'loading_msg' => $this->dependencies->l('Loading', 'oneyrepository'),
            'can_use_oney' => $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oney')
            ),
        ];

        return \Media::addJsDef($js_var);
    }

    /**
     * ONLY PS 1.6
     * Assign Oney var
     *
     * @param $cart Cart
     *
     * @throws Exception
     *
     * @return bool
     */
    public function assignOneyPaymentOptions($cart)
    {
        if (!$this->configurationAdapter->get(
            $this->dependencies->getConfigurationKey('oney')
        )) {
            return false;
        }

        if ($this->validateAdapter->validate('isLoadedObject', $cart)
            && $cart->id_address_invoice
            && $cart->id_address_delivery) {
            $is_elligible = $this->isOneyElligible($cart);
        } else {
            $amount = $cart->getOrderTotal(true, \Cart::BOTH);
            $is_elligible = $this->isValidOneyAmount($amount);
        }
        $this->assign->assign([
            'payplug_oney' => true,
            'payplug_oney_required_field' => $this->displayOneyRequiredFields(),
            'payplug_oney_allowed' => $is_elligible['result'],
            'payplug_oney_error' => $is_elligible['error'],
            'payplug_oney_loading_msg' => $this->dependencies->l('Loading', 'oneyrepository'),
        ]);
    }

    /**
     * @description Display Oney payment options
     *
     * @param $cart Cart
     * @param $amount
     * @param bool $country
     *
     * @throws BadParameterException
     * @throws ConfigurationNotSetException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function assignOneyPriceAndPaymentOptions($cart, $amount, $country = false)
    {
        $tools = $this->toolsAdapter;

        if ($this->validateAdapter->validate('isLoadedObject', $cart)
            && $cart->id_address_invoice
            && $cart->id_address_delivery) {
            $is_elligible = $this->isOneyElligible($cart, $amount, $country);
        } else {
            $is_elligible = $this->isValidOneyAmount($amount);
        }

        if ($is_elligible['result']) {
            $oney_payment_options = $this->getOneyPaymentOptionsList($amount, $country);
        } else {
            $oney_payment_options = false;
        }

        $error = $is_elligible['error'] ? $is_elligible['error'] : (
            $oney_payment_options
                ? false
                : $this->dependencies->l('oney.assignOneyPriceAndPaymentOptions.unavailable', 'oneyrepository')
        );

        $this->assign->assign([
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $tools->tool('displayPrice', $amount),
            ],
            'payplug_oney_allowed' => $is_elligible['result'] && $oney_payment_options,
            'payplug_oney_error' => $error,
        ]);

        if ($oney_payment_options) {
            $this->assign->assign([
                'oney_payment_options' => $oney_payment_options,
            ]);
        }

        $this->assignLegalNotice();
    }

    /**
     * @description Assign Oney Legal Notice
     */
    public function assignLegalNotice()
    {
        $limits = $this->getOneyPriceLimit();
        $learnMoreLink = $this->configurationAdapter->get(
            $this->dependencies->getConfigurationKey('companyIso')
        ) == 'IT'
        && $this->toolsAdapter->tool('strtolower', $this->contextAdapter->getContext()->language->iso_code) == 'it';
        $this->assign->assign([
            'learnMoreLink' => (bool) $learnMoreLink,
            'oneyWithFees' => (bool) $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oneyFees')
            ),
            'oneyMinAmounts' => $this->toolsAdapter->tool('displayPrice', $limits['min']),
            'oneyMaxAmounts' => $this->toolsAdapter->tool('displayPrice', $limits['max']),
            'oneyUrl' => 'https://www.oney.' . $this->contextAdapter->getContext()->language->iso_code,
        ]);
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
        $tools = $this->toolsAdapter;
        $validate = $this->validateAdapter;
        $errors = [];

        if (!$payment_data || !is_array($payment_data)) {
            return [$this->dependencies->l('Please fill in the required fields', 'oneyrepository')];
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

                case 'mobile_phone_number':
                    $id_address = $type == 'shipping' ?
                        $this->contextAdapter->getContext()->cart->id_address_delivery :
                        $this->contextAdapter->getContext()->cart->id_address_invoice;
                    $address = $this->addressAdapter->get((int) $id_address);
                    $country = $this->countryAdapter->getCountry($address->id_country);
                    $valid = $this->dependencies->configClass->isValidMobilePhoneNumber($country->iso_code, $data);
                    if (!$valid) {
                        $errors[] = $this->dependencies->l('Please enter your mobile phone number.', 'oneyrepository');
                    }

                    break;

                case 'first_name':
                    if (!$validate->validate('isName', $data)) {
                        $text = $type == 'shipping' ?
                            $this->dependencies->l('Please enter your shipping firstname.', 'oneyrepository') :
                            $this->dependencies->l('Please enter your billing firstname.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'last_name':
                    if (!$validate->validate('isName', $data)) {
                        $text = $type == 'shipping' ?
                            $this->dependencies->l('Please enter your shipping lastname.', 'oneyrepository') :
                            $this->dependencies->l('Please enter your billing lastname.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'address1':
                    if (!$validate->validate('isAddress', $data)) {
                        $text = $type == 'shipping' ?
                            $this->dependencies->l('Please enter your shipping address.', 'oneyrepository') :
                            $this->dependencies->l('Please enter your billing address.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'postcode':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ?
                            $this->dependencies->l('Please enter your shipping postcode.', 'oneyrepository') :
                            $this->dependencies->l('Please enter your billing postcode.', 'oneyrepository');
                        $errors[] = $text;
                    }

                    break;

                case 'city':
                    if (!$validate->validate('isCityName', $data)) {
                        $text = $type == 'shipping' ?
                            $this->dependencies->l('Please enter your shipping city.', 'oneyrepository') :
                            $this->dependencies->l('Please enter your billing city.', 'oneyrepository');
                        $errors[] = $text;
                    } elseif ($tools->tool('strlen', $data, 'UTF-8') > 32) {
                        $text = $this->dependencies->l('Your city name is too long (max 32 characters). ', 'oneyrepository')
                            . $this->dependencies->l('Please change it to another one or select another payment method.', 'oneyrepository');
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
     * @description Delete basic configuration
     *
     * @return bool
     */
    public function deleteOneyConfig()
    {
        $config = $this->configurationAdapter;

        return $config->deleteByName(
            $this->dependencies->getConfigurationKey('oney')
        ) && $config->deleteByName(
            $this->dependencies->getConfigurationKey('oneyAllowedCountries')
        ) && $config->deleteByName(
            $this->dependencies->getConfigurationKey('oneyMaxAmounts')
        ) && $config->deleteByName(
            $this->dependencies->getConfigurationKey('oneyMinAmounts')
        );
    }

    /**
     * @description Display Oney popin template
     *
     * @return mixed
     */
    public function displayOneyPopin()
    {
        $this->assignLegalNotice();
        $this->assign->assign([
            'use_fees' => (bool) $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oneyFees')
            ),
            'iso_code' => $this->toolsAdapter->tool(
                'strtoupper',
                $this->contextAdapter->getContext()->language->iso_code
            ),
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/popin.tpl');
    }

    /**
     * @description Display Oney Schedule
     *
     * @param $oney_payment
     * @param $amount
     *
     * @throws LocalizationException
     *
     * @return string
     */
    public function displayOneySchedule($oney_payment, $amount)
    {
        $withFirstSchedule = $this->contextAdapter->getContext()->language->iso_code == 'it';
        $vars = [
            'use_fees' => (bool) $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oneyFees')
            ),
            'oney_payment_option' => $oney_payment,
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $this->toolsAdapter->tool('displayPrice', $amount),
            ],
            'withFirstSchedule' => $withFirstSchedule,
            'iso_code' => $this->toolsAdapter->tool(
                'strtoupper',
                $this->contextAdapter->getContext()->language->iso_code
            ),
            'merchant_company_iso' => $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('companyIso')
            ),
        ];
        $this->assign->assign($vars);

        return $this->dependencies->configClass->fetchTemplate('oney/schedule.tpl');
    }

    /**
     * @description Display Oney popin payment option
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @return mixed
     */
    public function displayOneyPaymentOptions()
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $cart = $this->contextAdapter->getContext()->cart;
            if ($this->validateAdapter->validate('isLoadedObject', $cart)
                && $cart->id_address_invoice && $cart->id_address_delivery) {
                $is_elligible = $this->isOneyElligible($cart);
            } else {
                if ($this->validateAdapter->validate('isLoadedObject', $cart)) {
                    $amount = $cart->getOrderTotal(true);
                } else {
                    $amount = 0;
                }
                $is_elligible = $this->isValidOneyAmount($amount);
            }

            $oneyImageOptimized = '/modules/' . $this->dependencies->name . '/views/img/oney/x3x4_with';
            $oneyImagex3 = '/modules/' . $this->dependencies->name . '/views/img/oney/x3_with';
            $oneyImagex4 = '/modules/' . $this->dependencies->name . '/views/img/oney/x4_with';
            $oneyImage = '';

            $use_fees = (bool) $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oneyFees')
            );
            if (!$use_fees) {
                $oneyImage .= 'out';
            }

            $oneyImage .= '_fees';

            $iso = $this->toolsAdapter->tool('strtoupper', $this->contextAdapter->getContext()->language->iso_code);
            $merchant_company_iso = (string) $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('companyIso')
            );
            if ($use_fees === false) {
                if ($iso != 'IT' && $iso != 'FR') {
                    $iso = $merchant_company_iso;
                }

                $oneyImage .= '_' . $iso;
            }

            if ($is_elligible['result'] !== true) {
                $oneyImage .= '_alt';
            }

            $oneyImage .= '.svg';
            $oneyImageUrls = [
                'optimized' => $oneyImageOptimized . $oneyImage,
                'x3' => $oneyImagex3 . $oneyImage,
                'x4' => $oneyImagex4 . $oneyImage,
            ];

            $this->assign->assign([
                'use_fees' => $use_fees,
                'payplug_oney_loading_msg' => $this->dependencies->l('Loading', 'oneyrepository'),
                'oney_required_fields' => $this->getOneyRequiredFields(),
                'iso_code' => $iso,
                'merchant_company_iso' => $merchant_company_iso,
                'oney_image' => $oneyImageUrls,
            ]);

            return $this->dependencies->configClass->fetchTemplate('oney/payment/payment.tpl');
        }
    }

    /**
     * ONLY PS 1.6
     * Display Oney required fields template
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @return mixed
     */
    public function displayOneyRequiredFields()
    {
        $fields = $this->getOneyRequiredFields();

        if (!$fields) {
            return false;
        }

        $this->assign->assign([
            'oney_required_fields' => $fields,
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/required.tpl');
    }

    /**
     * @description Format Oney simulation from resource
     *
     * @param bool  $operation
     * @param array $resource
     * @param bool  $total_amount
     *
     * @return array
     */
    public function formatOneyResource($operation = false, $resource = [], $total_amount = false)
    {
        $tools = $this->toolsAdapter;

        if (!in_array($operation, $this->oneyEntity->getOperations()) || !$operation) {
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
        $resource['title'] = sprintf($this->dependencies->l('Payment in %sx', 'oneyrepository'), $resource['split']);

        // format price
        $total_cost = $this->dependencies->amountCurrencyClass->convertAmount($resource['total_cost'], true);
        $resource['total_cost'] = [
            'amount' => number_format($total_cost, 2),
            'value' => $tools->tool('displayPrice', $total_cost),
        ];
        $down_payment_amount =
            $this->dependencies->amountCurrencyClass->convertAmount($resource['down_payment_amount'], true);
        $resource['down_payment_amount'] = [
            'amount' => number_format($down_payment_amount, 2),
            'value' => $tools->tool('displayPrice', $down_payment_amount),
        ];
        foreach ($resource['installments'] as &$installment) {
            $amount = $this->dependencies->amountCurrencyClass->convertAmount($installment['amount'], true);
            $installment['amount'] = number_format($amount, 2);
            $installment['value'] = $tools->tool('displayPrice', $amount);
        }

        $total_amount = $this->dependencies->amountCurrencyClass->convertAmount($total_amount, true);
        $total_amount += $total_cost;
        $resource['total_amount'] = [
            'amount' => number_format($total_amount, 2),
            'value' => $tools->tool('displayPrice', $total_amount),
        ];

        return $resource;
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

    /**
     * @description Get Oney call to action
     *
     * @param string $env
     *
     * @return mixed
     */
    public function getOneyCTA($env = null)
    {
        $use_taxes = (bool) $this->configurationAdapter->get('PS_TAX');
        $amount = $this->contextAdapter->getContext()->cart->getOrderTotal($use_taxes);
        $is_elligible = $this->isValidOneyAmount($amount);
        $is_elligible = $is_elligible['result'];

        $this->assign->assign([
            'env' => $env,
            'payplug_oney_loading_msg' => $this->dependencies->l('Loading', 'oneyrepository'),
            'payplug_is_oney_elligible' => $is_elligible,
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @description Get Oney Delivery Context
     *
     * @return array
     */
    public function getOneyDeliveryContext()
    {
        $cart = $this->cartAdapter->get((int) $this->contextAdapter->getContext()->cart->id);

        if ($this->cartAdapter->isVirtualCart($cart)) {
            return [
                'delivery_label' => $this->configurationAdapter->get('PS_SHOP_NAME'),
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'edelivery',
            ];
        }

        $carrier = $this->carrierAdapter->get((int) $cart->id_carrier);

        if ($this->validateAdapter->validate('isLoadedObject', $carrier)) {
            return [
                'delivery_label' => $carrier->name ? $carrier->name : $this->configurationAdapter->get('PS_SHOP_NAME'),
                'expected_delivery_date' => date(
                    'Y-m-d',
                    strtotime('+' . $this->carrierAdapter->getDefaultDelay() . ' day')
                ),
                'delivery_type' => $this->carrierAdapter->getDefaultDeliveryType(),
            ];
        }

        return [
            'delivery_label' => $this->configurationAdapter->get('PS_SHOP_NAME'),
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
        $cart_context = [];
        $cart = $this->cartAdapter->get((int) $this->contextAdapter->getContext()->cart->id);
        if (!$this->validateAdapter->validate('isLoadedObject', $cart)) {
            return ['cart' => $cart_context];
        }

        $products = $this->cartAdapter->getProducts($cart);
        $delivery_context = $this->getOneyDeliveryContext();

        foreach ($products as $product) {
            $unit_price = $this->dependencies->amountCurrencyClass->convertAmount($product['price_wt']);
            $productName = (string) $product['name'] . (isset($product['attributes'])
                    ? ' - ' . $product['attributes']
                    : '');

            $item = [
                'merchant_item_id' => (string) $product['id_product'],
                'name' => $this->toolsAdapter->substr($productName, 0, 250),
                'price' => (int) $unit_price,
                'quantity' => (int) $product['cart_quantity'],
                'total_amount' => (string) $unit_price * $product['cart_quantity'],
                'brand' => (isset($product['manufacturer_name']) && $product['manufacturer_name']) ?
                    $this->toolsAdapter->substr($product['manufacturer_name'], 0, 250) :
                    $this->configurationAdapter->get('PS_SHOP_NAME'),
            ];

            $cart_context[] = array_merge($item, $delivery_context);
        }

        return ['cart' => $cart_context];
    }

    /**
     * @description Get Oney payment options
     *
     * @param int  $amount
     * @param bool $country
     *
     * @throws BadParameterException
     * @throws ConfigurationNotSetException
     *
     * @return array
     */
    public function getOneyPaymentOptionsList($amount = 0, $country = false)
    {
        // get Oney resource
        $payment_list = [];
        if (!is_numeric($amount) || !$amount) {
            return $payment_list;
        }

        $amount = $this->dependencies->amountCurrencyClass->convertAmount($amount);

        if (!$country) {
            $iso_code_list = $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oneyAllowedCountries')
            );
            if (!$iso_code_list) {
                return $payment_list;
            }

            $iso_list = explode(',', $iso_code_list);
            if (isset($iso_list)) {
                $country = reset($iso_list);
            }
        }
        $country = $this->toolsAdapter->tool('strtoupper', $country);

        $available_oney_payments = $this->oneyEntity->getOperations();
        $oney_simulations = $this->getOneySimulations($amount, $country, $available_oney_payments);

        $use_fees = (bool) $this->configurationAdapter->get(
            $this->dependencies->getConfigurationKey('oneyFees')
        );
        foreach (array_keys($oney_simulations['simulations']) as $key) {
            $with_fees = (bool) strpos($key, 'with_fees') !== false;
            if (($use_fees && !$with_fees) || (!$use_fees && $with_fees)) {
                unset($oney_simulations['simulations'][$key]);
            }
        }

        if (!$oney_simulations['result']) {
            return $payment_list;
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
     * @description Display Oney payment options
     *
     * @param $cart Cart
     * @param $amount
     * @param bool $country
     *
     * @throws BadParameterException
     * @throws ConfigurationNotSetException
     *
     * @return array
     */
    public function getOneyPriceAndPaymentOptions($cart, $amount, $country = false)
    {
        if ($this->validateAdapter->validate('isLoadedObject', $cart)
            && $cart->id_address_invoice
            && $cart->id_address_delivery) {
            $is_elligible = $this->isOneyElligible($cart, $amount, $country);
        } else {
            $is_elligible = $this->isValidOneyAmount($amount);
        }

        if ($is_elligible['result']) {
            $oney_payment_options = $this->getOneyPaymentOptionsList($amount, $country);
        } else {
            $oney_payment_options = false;
        }

        $error = $is_elligible['error'] ? $is_elligible['error'] : (
            $oney_payment_options
                ? false
                : $this->dependencies->l('oney.getOneyPriceAndPaymentOptions.unavailable', 'oneyrepository')
        );

        $withFirstSchedule = $this->contextAdapter->getContext()->language->iso_code == 'it';

        $this->assign->assign([
            'payplug_oney_required_field' => $cart ? $this->displayOneyRequiredFields() : false,
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $this->toolsAdapter->tool('displayPrice', $amount),
            ],
            'payplug_oney_allowed' => $is_elligible['result'] && $oney_payment_options,
            'payplug_oney_error' => $error,
            'withFirstSchedule' => $withFirstSchedule,
        ]);

        if ($oney_payment_options) {
            $this->assign->assign([
                'oney_payment_options' => $oney_payment_options,
            ]);
        }

        $popin_tpl = $this->displayOneyPopin();
        $payment_tpl = $this->displayOneyPaymentOptions();

        return [
            'result' => $is_elligible['result'] && $oney_payment_options,
            'error' => $error,
            'popin' => $popin_tpl,
            'payment' => $payment_tpl,
        ];
    }

    /**
     * @description   get custom oney ammount from BO form
     *
     * @param $custom_oney_amount
     *
     * @return string
     */
    public function setCustomOneyLimit($custom_oney_amount)
    {
        $config = $this->configurationAdapter;
        $tools = $this->toolsAdapter;

        $id_currency = $config->get('PS_CURRENCY_DEFAULT');
        $currency = $this->currencyAdapter->get((int) $id_currency);

        $iso_code = $tools->tool('strtoupper', $currency->iso_code);

        $oneyAmount = [
            'currency' => $iso_code . ':',
            'ammount' => $custom_oney_amount,
        ];

        return implode($oneyAmount);
    }

    /**
     * @description Get Oney price limit
     *
     * @param bool  $id_currency
     * @param mixed $custom
     *
     * @return array
     */
    public function getOneyPriceLimit($custom = true, $id_currency = false)
    {
        $config = $this->configurationAdapter;
        $tools = $this->toolsAdapter;

        if ($this->validateAdapter->validate('isLoadedObject', $id_currency)) {
            $currency = $id_currency;
        } else {
            if (!is_int($id_currency) && $this->validateAdapter->validate('isLanguageIsoCode', $id_currency)) {
                $id_currency = $this->countryAdapter->getByIso($id_currency);
            }
            if (!$id_currency) {
                $id_currency = $config->get('PS_CURRENCY_DEFAULT');
            }

            $currency = $this->currencyAdapter->get((int) $id_currency);
        }

        $limits = [
            'min' => false,
            'max' => false,
        ];

        if (!$this->validateAdapter->validate('isLoadedObject', $currency)) {
            return $limits;
        }

        $iso_code = $tools->tool('strtoupper', $currency->iso_code);

        if ($custom == true) {
            $oney_min_amounts = explode(
                ',',
                $tools->tool('strtoupper', $config->get(
                    $this->dependencies->getConfigurationKey('oneyCustomMinAmounts')
                ))
            );
        } else {
            $oney_min_amounts = explode(
                ',',
                $tools->tool('strtoupper', $config->get(
                    $this->dependencies->getConfigurationKey('oneyMinAmounts')
                ))
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
            $oney_max_amounts = explode(
                ',',
                $tools->tool('strtoupper', $config->get(
                    $this->dependencies->getConfigurationKey('oneyCustomMaxAmounts')
                ))
            );
        } else {
            $oney_max_amounts = explode(
                ',',
                $tools->tool('strtoupper', $config->get(
                    $this->dependencies->getConfigurationKey('oneyMaxAmounts')
                ))
            );
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
     * @description Get the Oney required fields from Context
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @return array
     */
    public function getOneyRequiredFields()
    {
        $fields = [];
        $customer = $this->contextAdapter->getContext()->customer;
        if (!$this->validateAdapter->validate('isLoadedObject', $customer)) {
            return $fields;
        }
        $id_address_delivery = $this->contextAdapter->getContext()->cart->id_address_delivery;
        $id_address_invoice = $this->contextAdapter->getContext()->cart->id_address_invoice;
        $is_same = $id_address_delivery == $id_address_invoice;

        $shipping_fields = [];
        $shipping_address = $this->addressAdapter->get((int) $id_address_delivery);

        if (!$this->validateAdapter->validate('isLoadedObject', $shipping_address)) {
            return $fields;
        }
        $shipping_data = [
            'email' => $this->contextAdapter->getContext()->customer->email,
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
            $billing_address = $this->addressAdapter->get((int) $id_address_invoice);

            if (!$this->validateAdapter->validate('isLoadedObject', $billing_address)) {
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
     * @description Get Oney Payment Simulations
     *
     * @param int    $amount
     * @param string $country
     * @param array  $operation contain x3|4_with_fees or x3|4_without_fees
     *
     * @throws BadParameterException
     * @throws ConfigurationNotSetException
     *
     * @return array
     */
    public function getOneySimulations($amount, $country, $operation)
    {
        $tools = $this->toolsAdapter;
        $cache_key = $this->cache->setCacheKey($amount, $country, $operation);

        if (!$cache_key['result']) {
            return [
                'result' => false,
                'error' => $cache_key['message'],
            ];
        }

        // Checks if the current simulation is already saved in the database
        // If not, we do a simulation for Oney, and we will store it to the DB
        $cache = $this->cache->getCacheByKey($cache_key['result']);

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
            $this->logger->setParams(['process' => '[Oney Repository] OneySimulation::getSimulations']);
            $this->logger->addLog($simulations['message'], 'error');

            return [
                'result' => false,
                'error' => $simulations['message'],
            ];
        }

        $simulations = $simulations['resource'];
        if (isset($simulations['object']) && $simulations['object'] == 'error') {
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
            if (!$this->cache->setCache($cache_key['result'], $to_cache)) {
                $this->logger->setParams(['process' => '[Oney Repository] getOneySimulations']);
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

        $tools = $this->toolsAdapter;

        // Check the shipping fields
        $shipping = $payment_data['shipping'];

        // Validate email format
        $is_valid_email = $this->isValidOneyEmail($shipping['email']);
        if (!$is_valid_email['result']) {
            return true;
        }

        // Validate phone number
        $valid_shipping_mobile = $this->dependencies->configClass->isValidMobilePhoneNumber(
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
        $valid_billing_mobile = $this->dependencies->configClass->isValidMobilePhoneNumber(
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
        if ($this->validateAdapter->validate('isLoadedObject', $id_currency)) {
            $currency = $id_currency;
        } elseif (is_int($id_currency)) {
            $currency = new \Currency($id_currency);
        } else {
            return false;
        }

        if (!$this->validateAdapter->validate('isLoadedObject', $currency)) {
            return false;
        }

        // we use the Oney limit to get allowed currencies
        $oney_min_amounts = $this->toolsAdapter->tool(
            'strtoupper',
            $this->configurationAdapter->get($this->dependencies->getConfigurationKey('oneyMinAmounts'))
        );
        $iso_code = $this->toolsAdapter->tool('strtoupper', $currency->iso_code);

        return strpos($oney_min_amounts, $iso_code) !== false;
    }

    /**
     * @description Check if a valid Cart for Oney
     *
     * @param $cart Cart
     * @param bool $amount
     * @param bool $country
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @return array
     */
    public function isOneyElligible($cart, $amount = false, $country = false)
    {
        // check if cart is valid
        $is_valid_cart = $this->isValidOneyCart($cart);
        if (!$is_valid_cart['result']) {
            return [
                'result' => false,
                'error_type' => 'invalid_cart',
                'error' => $is_valid_cart['error'],
            ];
        }

        // check if cart address is valid
        if ($country) {
            $is_valid_addresses = $this->isValidOneyAddresses($cart->id_address_delivery, $cart->id_address_invoice);
            if (!$is_valid_addresses['result']) {
                return [
                    'result' => false,
                    'error_type' => 'invalid_addresses',
                    'error' => $is_valid_addresses['error'],
                ];
            }
        }

        // check if current amount is between min and max values
        $amount = $amount ? $amount : $cart->getOrderTotal(true, \Cart::BOTH);
        $is_valid_amount = $this->isValidOneyAmount($amount);
        if (!$is_valid_amount['result']) {
            $limits = $this->getOneyPriceLimit(true, $cart->id_currency);
            $converted_amount = $this->dependencies->amountCurrencyClass->convertAmount($amount);
            $error_type = $converted_amount > $limits['min'] ? 'invalid_amount_top' : 'invalid_amount_bottom';

            return ['result' => false, 'error_type' => $error_type, 'error' => $is_valid_amount['error']];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Check if Oney is allowed
     *
     * @return bool
     */
    public function isOneyAllowed()
    {
        return $this->dependencies->configClass->isAllowed()
            && $this->configurationAdapter->get($this->dependencies->getConfigurationKey('oney'))
            && $this->isOneyAllowedCurrency($this->contextAdapter->getContext()->currency);
    }

    /**
     * @description Check if billing and shipping addresses are valid
     *
     * @param int $id_shipping
     * @param int $id_billing
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @return array
     */
    public function isValidOneyAddresses($id_shipping, $id_billing)
    {
        $shipping = new \Address($id_shipping);
        $shipping_country = new \Country($shipping->id_country);

        $billing = new \Address($id_billing);
        $billing_country = new \Country($billing->id_country);

        return $this->isValidOneyCountry($shipping_country->iso_code, $billing_country->iso_code);
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
        $limits = $this->getOneyPriceLimit();
        $convert_amount = ($this->dependencies->amountCurrencyClass->convertAmount($amount)) / 100;
        if (($limits['min'] > $convert_amount) || ($convert_amount > $limits['max'])) {
            return [
                'result' => false,
                'error' => sprintf(
                    $this->dependencies->l('The total amount of your order should be between %s and %s to pay with Oney.', 'oneyrepository'),
                    $this->toolsAdapter->tool('displayPrice', $limits['min']),
                    $this->toolsAdapter->tool('displayPrice', $limits['max'])
                ),
            ];
        }

        return ['result' => true, 'error' => false];
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
        if (!$this->validateAdapter->validate('isLoadedObject', $cart)) {
            return [
                'result' => false,
                'error' => $this->dependencies->l('The cart is unvalid', 'oneyrepository'),
            ];
        }

        $nb_products = $this->cartAdapter->nbProducts($cart);

        // todo: set as a constant
        $max = 1000;

        if ($nb_products >= $max) {
            $error = 'The payment with Oney is not available because you have more than 1000 items in your cart.';

            return [
                'result' => false,
                'error' => $this->dependencies->l($error, 'oneyrepository'),
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Check if billing and shipping addresses are valid
     *
     * @param string $shipping_iso
     * @param string $billing_iso
     *
     * @return array
     */
    public function isValidOneyCountry($shipping_iso, $billing_iso)
    {
        // check if the billing country and the shipping country are different then return false
        if ($shipping_iso != $billing_iso) {
            $error = 'Delivery and billing addresses must be in the same country to pay with Oney.';

            return [
                'result' => false,
                'type' => 'different',
                'error' => $this->dependencies->l($error, 'oneyrepository'),
            ];
        }

        // check if the shipping country are different then return false
        $iso_code = $this->toolsAdapter->tool('strtoupper', $shipping_iso);
        $allow_countries = $this->toolsAdapter->tool(
            'strtoupper',
            $this->configurationAdapter->get(
                $this->dependencies->getConfigurationKey('oneyAllowedCountries')
            )
        );
        if (!$allow_countries) {
            return [
                'result' => false,
                'type' => 'no_country',
                'error' => $this->dependencies->l('No countries are configured to use oney.', 'oneyrepository'),
            ];
        }

        $iso_list = explode(',', $allow_countries);
        if (!in_array($iso_code, $iso_list, true)) {
            /*
             * We first used Prestashop country list but translation was not ok so we had to write countries
             * directly in the code. Maybe later it will be ok and dynamic.
             */
            /*
            $list = [];
            foreach ($iso_list as $iso) {
                $id_country = $this->countryAdapter->getByIso($iso);
                $list[] = $this->countryAdapter->getNameById(
                    $this->contextAdapter->getContext()->language->id,
                    $id_country
                );
            }
            */
            $str_list = $this->dependencies->l('France, Martinique, Guadeloupe, La Reunion, Mayotte or French Guiana', 'oneyrepository');
            if (in_array('IT', $iso_list)) {
                $str_list = $this->dependencies->l('Italy', 'oneyrepository');
            }

            return [
                'result' => false,
                'type' => 'invalid',
                'error' => $this->dependencies->l('For a payment with Oney, delivery and billing addresses must be in', 'oneyrepository') . ' ' .
                $str_list,
            ];
        }

        return ['result' => true, 'error' => false];
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
        $tools = $this->toolsAdapter;
        $validate = $this->validateAdapter;
        $error = false;

        if (!is_string($email) || empty($email) || !$validate->validate('isEmail', $email)) {
            $error = $this->dependencies->l('Your email address is not a valid email', 'oneyrepository');
        } elseif ($tools->tool('strlen', $email, 'UTF-8') > 100
            && $tools->tool('strpos', $email, '+') !== false) {
            $error = $this->dependencies->l('Your email address is too long and the + character is not valid', 'oneyrepository');
            $error .= $this->dependencies->l(' please change it to another address (max 100 characters).', 'oneyrepository');
        } elseif ($tools->tool('strlen', $email, 'UTF-8') > 100) {
            $error = $this->dependencies->l('Your email address is too long. Please change your email address (100 characters max).', 'oneyrepository');
        } elseif (strpos($email, '+') !== false) {
            $error = $this->dependencies->l('The + character is not valid. Please change your email address (100 characters max).', 'oneyrepository');
        }

        return [
            'result' => $error ? false : true,
            'message' => $error,
        ];
    }

    protected function setParams()
    {
        $this->oneyEntity->setOperations([
            'x3_with_fees',
            'x4_with_fees',
            'x3_without_fees',
            'x4_without_fees',
        ]);
    }
}
