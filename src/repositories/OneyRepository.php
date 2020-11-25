<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use Payplug\Exception\ConfigurationNotSetException;
use Payplug\Exception\ConnectionException;
use Payplug\Exception\HttpException;
use Payplug\Exception\UnexpectedAPIResponseException;
use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\src\specific\ToolsSpecific;
use PayPlug\src\specific\ValidateSpecific;
use PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException;

class OneyRepository
{
    private $addressSpecific;
    private $cache;
    private $log;
    private $logger;
    private $configurationSpecific;
    private $contextSpecific;
    private $countrySpecific;
    private $payplug;
    private $toolsSpecific;
    private $validateSpecific;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
        $this->addressSpecific = new AddressSpecific();
        $this->cache = new CacheRepository();
        $this->configurationSpecific = new ConfigurationSpecific();
        $this->countrySpecific = new CountrySpecific();
        $this->logger = new LoggerRepository();
        $this->toolsSpecific = new ToolsSpecific();
        $this->validateSpecific = new ValidateSpecific();
        $this->log = new \Payplug\classes\MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
        $this->contextSpecific = new ContextSpecific();
    }

    /**
     * @description Assign Oney javascript variable
     */
    public function assignOneyJSVar()
    {
        $js_var = [
            'loading_msg' => $this->payplug->l('Loading'),
            'can_use_oney' => $this->payplug->getConfiguration('PAYPLUG_ONEY'),
        ];
        return \Media::addJsDef($js_var);
    }

    /**
     * ONLY PS 1.6
     * Assign Oney var
     *
     * @param $cart Cart
     * @return bool
     * @throws Exception
     */
    public function assignOneyPaymentOptions($cart)
    {
        if (!$this->payplug->getConfiguration('PAYPLUG_ONEY')) {
            return false;
        }

        // check if at least one carrier is available for this cart
        // get the available carrier
        $package_list = $cart->getPackageList();
        $carrier_ids = [];
        foreach ($package_list as $address) {
            foreach ($address as $package) {
                $carrier_ids = array_merge($carrier_ids, $package['carrier_list']);
            }
        }

        // only if we have carrier need for this cart
        // check the carrier type of each available carrier
        if ($carrier_ids) {
            $has_valid_carrier = false;
            foreach ($carrier_ids as $carrier_id) {
                if ($has_valid_carrier) {
                    continue;
                }

                $pc = new \PayPlugCarrier();
                $pc = $pc->getByIdCarrier($carrier_id);
                if ($pc->delivery_type) {
                    $has_valid_carrier = true;
                }
            }

            // if no carrier available for Oney, return false
            if (!$has_valid_carrier) {
                return false;
            }
        }

        if ($this->validateSpecific->validate('isLoadedObject', $cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
            $is_elligible = $this->isOneyElligible($cart);
        } else {
            $id_currency = $this->contextSpecific->getContext()->currency->id;
            $amount = $cart->getOrderTotal(true, \Cart::BOTH);
            $is_elligible = $this->isValidOneyAmount($amount, $id_currency);
        }

        $this->contextSpecific->getContext()->smarty->assign([
            'payplug_module_dir' => str_replace('payplug/payplug.php', '', $this->payplug->constantFile),
            'payplug_oney' => true,
            'payplug_oney_required_field' => $this->displayOneyRequiredFields(),
            'payplug_oney_allowed' => $is_elligible['result'],
            'payplug_oney_error' => $is_elligible['error'],
            'payplug_oney_loading_msg' => $this->payplug->l('Loading'),
        ]);
    }

    /**
     * @description Display Oney payment options
     *
     * @param $cart Cart
     * @param $amount
     * @param bool $country
     * @return void
     */
    public function assignOneyPriceAndPaymentOptions($cart, $amount, $country = false)
    {
        $tools = $this->toolsSpecific;

        if ($this->validateSpecific->validate('isLoadedObject', $cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
            $is_elligible = $this->isOneyElligible($cart, $amount, $country);
        } else {
            $id_currency = $this->contextSpecific->getContext()->currency->id;
            $is_elligible = $this->isValidOneyAmount($amount, $id_currency);
        }

        if ($is_elligible['result']) {
            $oney_payment_options = $this->getOneyPaymentOptionsList($amount, $country);
        } else {
            $oney_payment_options = false;
        }

        $error = $is_elligible['error'] ? $is_elligible['error'] : (
            $oney_payment_options ? false : $this->payplug->l('Oney is momentarily unavailable.')
        );

        $this->contextSpecific->getContext()->smarty->assign([
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $tools->tool('displayPrice', $amount),
            ],
            'payplug_oney_allowed' => $is_elligible['result'] && $oney_payment_options,
            'payplug_oney_error' => $error
        ]);

        if ($oney_payment_options) {
            $this->contextSpecific->getContext()->smarty->assign([
                'oney_payment_options' => $oney_payment_options,
            ]);
        }

        $limits = $this->getOneyPriceLimit();
        $min_amount = $this->payplug->convertAmount($limits['min'], true);
        $max_amount = $this->payplug->convertAmount($limits['max'], true);

        $legal_text = 'Offre de financement avec apport obligatoire, réservée aux particuliers et valable pour tout achat de %s à %s. ';
        $legal_text .= 'Sous réserve d\'acceptation par Oney Bank. ';
        $legal_text .= 'Vous disposez d\'un délai de 14 jours pour renoncer à votre crédit. ';
        $legal_text .= 'Oney Bank - SA au capital de 51 286 585€ - 34 Avenue de Flandre 59170 Croix - 546 380 197 RCS Lille Métropole - n° Orias 07 023 261 www.orias.fr ';
        $legal_text .= 'Correspondance : CS 60 006 - 59895 Lille Cedex - www.oney.fr';

        $this->contextSpecific->getContext()->smarty->assign([
            'legal_notice' => sprintf(
                $this->payplug->l($legal_text),
                $tools->tool('displayPrice', $min_amount),
                $tools->tool('displayPrice', $max_amount)
            )
        ]);
    }

    /**
     * @description Check Oney required fields in form
     *
     * todo: to clean or update
     * @return array
     */
    public function checkOneyRequiredFields($payment_data)
    {
        $tools = $this->toolsSpecific;
        $validate = $this->validateSpecific;
        $errors = [];

        if (!$payment_data) {
            return [$this->payplug->l('Please fill in the required fields')];
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
                    if ($tools->tool('strlen', $data) > 100 && $tools->tool('strpos', $data, '+') !== false) {
                        $text = $this->payplug->l('Your email address is too long and the + character is not valid, please change it to another address (max 100 characters).');
                        $errors[] = $text;
                    } elseif ($tools->tool('strlen', $data) > 100) {
                        $text = $this->payplug->l('Your email address is too long, please change it to a shorter one (max 100 characters).');
                        $errors[] = $text;
                    } elseif (strpos($data, '+') !== false) {
                        $text = $this->payplug->l('The + character is not valid. Please change your email address (100 characters max).');
                        $errors[] = $text;
                    }
                    break;
                case 'mobile_phone_number':
                    $id_address = $type == 'shipping' ? $this->contextSpecific->getContext()->cart->id_address_delivery : $this->contextSpecific->getContext()->cart->id_address_invoice;
                    $address = $this->addressSpecific->getAddress($id_address);
                    $country = $this->countrySpecific->getCountry($address->id_country);
                    $valid = $this->payplug->isValidMobilePhoneNumber($data, $country->iso_code);
                    if (!$valid) {
                        $errors[] = $this->payplug->l('Please enter your mobile phone number.');
                    }
                    break;
                case 'first_name':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ? $this->payplug->l('Please enter your shipping firstname.') : $this->payplug->l('Please enter your billing firstname.');
                        $errors[] = $text;
                    }
                    break;
                case 'last_name':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ? $this->payplug->l('Please enter your shipping lastname.') : $this->payplug->l('Please enter your billing lastname.');
                        $errors[] = $text;
                    }
                    break;
                case 'address1':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ? $this->payplug->l('Please enter your shipping address.') : $this->payplug->l('Please enter your billing address.');
                        $errors[] = $text;
                    }
                    break;
                case 'postcode':
                    if (!$validate->validate('isPostCode', $data)) {
                        $text = $type == 'shipping' ? $this->payplug->l('Please enter your shipping postcode.') : $this->payplug->l('Please enter your billing postcode.');
                        $errors[] = $text;
                    }
                    break;
                case 'city':
                    if (!$validate->validate('isCityName', $data)) {
                        $text = $type == 'shipping' ? $this->payplug->l('Please enter your shipping city.') : $this->payplug->l('Please enter your billing city.');
                        $errors[] = $text;
                    } elseif ($tools->tool('strlen', $data) > 32) {
                        $text = $this->payplug->l('Your city name is too long (max 32 characters). ')
                            . $this->payplug->l('Please change it to another one or select another payment method.');
                        $errors[] = $text;
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * @description Display Oney popin template
     *
     * @return mixed
     */
    public function displayOneyPopin()
    {
        $limits = $this->getOneyPriceLimit();
        $min_amount = $this->payplug->convertAmount($limits['min'], true);
        $max_amount = $this->payplug->convertAmount($limits['max'], true);

        $config = $this->configurationSpecific;
        $tools = $this->toolsSpecific;

        $legal_text = 'Offre de financement avec apport obligatoire, réservée aux particuliers et valable pour tout achat de %s à %s. ';
        $legal_text .= 'Sous réserve d\'acceptation par Oney Bank. ';
        $legal_text .= 'Vous disposez d\'un délai de 14 jours pour renoncer à votre crédit. ';
        $legal_text .= 'Oney Bank - SA au capital de 51 286 585€ - 34 Avenue de Flandre 59170 Croix - 546 380 197 RCS Lille Métropole - n° Orias 07 023 261 www.orias.fr ';
        $legal_text .= 'Correspondance : CS 60 006 - 59895 Lille Cedex - www.oney.fr';

        $tos_url = $config->get('PAYPLUG_ONEY_TOS_URL');
        if (strpos($tos_url, 'http://') === false && strpos($tos_url, 'https://') === false && $tos_url) {
            $tos_url = $tools->tool('getShopProtocol') . $tos_url;
        }

        $this->contextSpecific->getContext()->smarty->assign([
            'tos_active' => $config->get('PAYPLUG_ONEY_TOS'),
            'tos_url' => $tos_url,
            'legal_notice' => sprintf($this->payplug->l($legal_text), $tools->tool('displayPrice', $min_amount), $tools->tool('displayPrice', $max_amount))
        ]);

        return $this->payplug->display($this->payplug->constantFile, 'oney/popin.tpl');
    }

    /**
     * @description Display Oney Schedule
     * @param $oney_payment
     * @param $amount
     * @return string
     * @throws LocalizationException
     */
    public function displayOneySchedule($oney_payment, $amount)
    {
        $this->contextSpecific->getContext()->smarty->assign([
            'oney_payment_option' => $oney_payment,
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $this->toolsSpecific->tool('displayPrice', $amount),
            ],
        ]);
        return $this->payplug->display($this->payplug->constantFile, 'oney/schedule.tpl');
    }

    /**
     * @description Display Oney popin payment option
     *
     * @return mixed
     */
    public function displayOneyPaymentOptions()
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->contextSpecific->getContext()->smarty->assign([
                'payplug_module_dir' => str_replace('payplug/payplug.php', '', $this->payplug->constantFile),
                'payplug_oney_loading_msg' => $this->payplug->l('Loading'),
                'oney_required_fields' => $this->displayOneyRequiredFields(),
            ]);

            return $this->payplug->display($this->payplug->constantFile, 'oney/payment/payment.tpl');
        }
    }

    /**
     * @description Format Oney simulation from resource
     *
     * @param string $method
     * @param array $resource
     * @param bool $total_amount
     * @return array
     */
    public function formatOneyResource($method, $resource, $total_amount = false)
    {
        $tools = $this->toolsSpecific;

        $type = explode('_', $method);

        $resource['split'] = (int)str_replace('x', '', $type[0]);
        $resource['title'] = sprintf($this->payplug->l('Payment in %sx'), $resource['split']);

        // format price
        $total_cost = $this->payplug->convertAmount($resource['total_cost'], true);
        $resource['total_cost'] = [
            'amount' => $total_cost,
            'value' => $tools->tool('displayPrice', $total_cost),
        ];
        $down_payment_amount = $this->payplug->convertAmount($resource['down_payment_amount'], true);
        $resource['down_payment_amount'] = [
            'amount' => $down_payment_amount,
            'value' => $tools->tool('displayPrice', $down_payment_amount),
        ];
        foreach ($resource['installments'] as &$installment) {
            $amount = $this->payplug->convertAmount($installment['amount'], true);
            $installment['amount'] = $amount;
            $installment['value'] = $tools->tool('displayPrice', $amount);
        }

        $total_amount = $this->payplug->convertAmount($total_amount, true);
        $total_amount += $total_cost;
        $resource['total_amount'] = [
            'amount' => $total_amount,
            'value' => $tools->tool('displayPrice', $total_amount),
        ];
        return $resource;
    }

    /**
     * @description Temp get valid iso code for french overseas,
     * todo: remove when it's fix in API
     *
     * @param $iso_country
     * @return string
     */
    public function getOneyCountry($iso_country)
    {
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
     * @return mixed
     */
    public function getOneyCTA($env = null)
    {
        $this->contextSpecific->getContext()->smarty->assign([
            'this_path' => str_replace('payplug.php', '', $this->payplug->constantFile),
            'env' => $env,
            'payplug_module_dir' => str_replace('payplug/payplug.php', '', $this->payplug->constantFile),
            'payplug_oney_loading_msg' => $this->payplug->l('Loading')
        ]);

        return $this->payplug->display($this->payplug->constantFile, 'oney/cta.tpl');
    }

    /**
     * @description Get Oney Delivery Context
     *
     * @return array
     */
    public function getOneyDeliveryContext()
    {
        if ($this->contextSpecific->getContext()->cart->isVirtualCart()) {
            return [
                'delivery_label' => $this->configurationSpecific->get('PS_SHOP_NAME'),
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'edelivery',
            ];
        }

        $carrier = new \Carrier($this->contextSpecific->getContext()->cart->id_carrier);

        return [
            'delivery_label' => $carrier->name,
            'expected_delivery_date' => date('Y-m-d', strtotime('+' . \PayPlugCarrier::CARRIER_DEFAULT_DELAY . ' day')),
            'delivery_type' => \PayPlugCarrier::CARRIER_DEFAULT_DELIVERY_TYPE
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
        $products = $this->contextSpecific->getContext()->cart->getProducts();
        $delivery_context = $this->getOneyDeliveryContext();

        foreach ($products as $product) {
            $unit_price = $this->payplug->convertAmount($product['price_wt']);
            $item = [
                'merchant_item_id' => $product['id_product'],
                'name' => (string)$product['name'] . (isset($product['attributes']) ? ' - ' . $product['attributes'] : ''),
                'price' => (int)$unit_price,
                'quantity' => (int)$product['cart_quantity'],
                'total_amount' => (string)$unit_price * $product['cart_quantity'],
                'brand' => (isset($product['manufacturer_name']) && $product['manufacturer_name']) ? $product['manufacturer_name']  : $this->configurationSpecific->get('PS_SHOP_NAME')
            ];

            $cart_context[] = array_merge($item, $delivery_context);
        }

        return ['cart' => $cart_context];
    }

    /**
     * @description Get Oney payment options
     *
     * @param $amount
     * @param bool $country
     * @return array
     */
    public function getOneyPaymentOptionsList($amount, $country = false)
    {
        // get Oney resource
        $payment_list = [];
        $amount = $this->payplug->convertAmount($amount);

        if (!$country) {
            $iso_code_list = $this->configurationSpecific->get('PAYPLUG_ONEY_ALLOWED_COUNTRIES');
            $iso_list = explode(',', $iso_code_list);
            $country = reset($iso_list);
        }

        $country = $this->toolsSpecific->tool('strtoupper', $country);

        $oney_sims = $this->getOneySimulations($amount, $country, $this->payplug->available_oney_payments);

        if (!$oney_sims['result']) {
            return $payment_list;
        }

        foreach ($oney_sims['simulations'] as $method => $oney_sim) {
            if (isset($oney_sim['installments']) && $oney_sim['installments']) {
                $payment_list[$method] = $this->formatOneyResource($method, $oney_sim, $amount);
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
     * @return array
     */
    public function getOneyPriceAndPaymentOptions($cart, $amount, $country = false)
    {
        if ($this->validateSpecific->validate('isLoadedObject', $cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
            $is_elligible = $this->isOneyElligible($cart, $amount, $country);
        } else {
            $id_currency = $this->contextSpecific->getContext()->currency->id;
            $is_elligible = $this->isValidOneyAmount($amount, $id_currency);
        }

        $error = false;
        if ($is_elligible['result']) {
            $oney_payment_options = $this->getOneyPaymentOptionsList($amount, $country);
        } else {
            $oney_payment_options = false;
            $error = $is_elligible['error'] ? $is_elligible['error'] : $this->payplug->l('Oney is momentarily unavailable.');
        }

        $error = $is_elligible['error'] ? $is_elligible['error'] : (
            $oney_payment_options ? false : $this->payplug->l('Oney is momentarily unavailable.')
        );

        $this->contextSpecific->getContext()->smarty->assign([
            'payplug_oney_required_field' => $this->displayOneyRequiredFields(),
            'payplug_oney_amount' => [
                'amount' => $amount,
                'value' => $this->toolsSpecific->tool('displayPrice', $amount),
            ],
            'payplug_oney_allowed' => $is_elligible['result'] && $oney_payment_options,
            'payplug_oney_error' => $error
        ]);

        if ($oney_payment_options) {
            $this->contextSpecific->getContext()->smarty->assign([
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
     * @description Get Oney price limit
     *
     * @param boolean $id_currency
     * @return array
     */
    public function getOneyPriceLimit($id_currency = false)
    {
        $config = $this->configurationSpecific;
        $tools = $this->toolsSpecific;

        if ($this->validateSpecific->validate('isLoadedObject', $id_currency)) {
            $currency = $id_currency;
        } else {
            if (!is_int($id_currency) && $this->validateSpecific->validate('isLanguageIsoCode', $id_currency)) {
                $id_currency = $this->countrySpecific->getByIso($id_currency);
            }
            if (!$id_currency) {
                $id_currency = $config->get('PS_CURRENCY_DEFAULT');
            }
            $currency = new \Currency($id_currency);
        }

        $limits = [
            'min' => false,
            'max' => false
        ];

        if (!$this->validateSpecific->validate('isLoadedObject', $currency)) {
            return $limits;
        }

        $iso_code = $tools->tool('strtoupper', $currency->iso_code);

        $oney_min_amounts = explode(',', $tools->tool('strtoupper', $config->get('PAYPLUG_ONEY_MIN_AMOUNTS')));
        foreach ($oney_min_amounts as $min_amount) {
            $min = explode(':', $min_amount);
            if ($min[0] == $iso_code) {
                $limits['min'] = (int)$min[1];
                break;
            }
        }

        $oney_max_amounts = explode(',', $tools->tool('strtoupper', $config->get('PAYPLUG_ONEY_MAX_AMOUNTS')));
        foreach ($oney_max_amounts as $max_amount) {
            $max = explode(':', $max_amount);
            if ($max[0] == $iso_code) {
                $limits['max'] = (int)$max[1];
                break;
            }
        }

        return $limits;
    }

    /**
     * @description Get the Oney required fields from Context
     *
     * @return array
     */
    public function getOneyRequiredFields()
    {
        $tools = $this->toolsSpecific;
        $is_same = $this->contextSpecific->getContext()->cart->id_address_delivery == $this->contextSpecific->getContext()->cart->id_address_invoice;

        $fields = [];
        $shipping_fields = [];

        $shipping_address = new \Address($this->contextSpecific->getContext()->cart->id_address_delivery);
        $shipping_country = new \Country($shipping_address->id_country);

        // Validate email format
        if ($tools->tool('strlen', $this->contextSpecific->getContext()->customer->email) > 100 && $tools->tool('strpos', $this->contextSpecific->getContext()->customer->email, '+') !== false) {
            $text = $this->payplug->l('Your email address is too long and the + character is not valid,') .
                $this->payplug->l(' please change it to another address (max 100 characters).');
            $shipping_fields['email'] = [
                'text' => $text,
                'input' => [
                    [
                        'name' => 'email',
                        'value' => $this->contextSpecific->getContext()->customer->email,
                        'type' => 'text'
                    ]
                ],
            ];
        } elseif ($tools->tool('strlen', $this->contextSpecific->getContext()->customer->email) > 100) {
            $text = $this->payplug->l('Your email address is too long, please change it to a shorter one (max 100 characters).');
            $shipping_fields['email'] = [
                'text' => $text,
                'input' => [
                    [
                        'name' => 'email',
                        'value' => $this->contextSpecific->getContext()->customer->email,
                        'type' => 'text'
                    ]
                ],
            ];
        } elseif (strpos($this->contextSpecific->getContext()->customer->email, '+') !== false) {
            $text = $this->payplug->l('The + character is not valid. Please change your email address (100 characters max).');
            $shipping_fields['email'] = [
                'text' => $text,
                'input' => [
                    [
                        'name' => 'email',
                        'value' => $this->contextSpecific->getContext()->customer->email,
                        'type' => 'text'
                    ]
                ],
            ];
        }

        // Validate phone number
        $is_valid_mobile_phone_number = $this->payplug->isValidMobilePhoneNumber(
            $shipping_address->phone_mobile,
            $shipping_country->iso_code
        );
        if (!$is_valid_mobile_phone_number) {
            $shipping_fields['mobile_phone_number'] = [
                'text' => $this->payplug->l('Please enter your mobile phone number.'),
                'input' => [
                    [
                        'name' => 'mobile_phone_number',
                        'value' => $shipping_address->phone_mobile,
                        'type' => 'text'
                    ]
                ],
            ];
        }

        // Validate address
        if ($tools->tool('strlen', $shipping_address->city) > 32) {
            $text = $this->payplug->l('Your city name is too long (max 32 characters). ')
                . $this->payplug->l('Please change it to another one or select another payment method.');
            $shipping_fields['city'] = [
                'text' => $text,
                'input' => [
                    [
                        'name' => 'first_name',
                        'value' => $shipping_address->firstname,
                        'type' => 'text'
                    ],
                    [
                        'name' => 'last_name',
                        'value' => $shipping_address->lastname,
                        'type' => 'text'
                    ],
                    [
                        'name' => 'address1',
                        'value' => $shipping_address->address1,
                        'type' => 'text'
                    ],
                    [
                        'name' => 'postcode',
                        'value' => $shipping_address->postcode,
                        'type' => 'text'
                    ],
                    [
                        'name' => 'city',
                        'value' => $shipping_address->city,
                        'type' => 'text'
                    ],
                ],
            ];
        }


        if ($is_same && !empty($shipping_fields)) {
            $fields['same'] = $shipping_fields;
        } else {
            if (!empty($shipping_fields)) {
                $fields['shipping'] = $shipping_fields;
            }
            $billing_fields = [];
            $billing_address = new \Address($this->contextSpecific->getContext()->cart->id_address_invoice);
            $billing_country = new \Country($billing_address->id_country);

            $is_valid_mobile_phone_number = $this->payplug->isValidMobilePhoneNumber(
                $billing_address->phone_mobile,
                $billing_country->iso_code
            );
            if (!$is_valid_mobile_phone_number) {
                $billing_fields['mobile_phone_number'] = [
                    'text' => $this->payplug->l('Please enter your mobile phone number.'),
                    'input' => [
                        [
                            'name' => 'mobile_phone_number',
                            'value' => $shipping_address->phone_mobile,
                            'type' => 'text'
                        ]
                    ],
                ];
            }

            if ($tools->tool('strlen', $billing_address->city) > 32) {
                $text = $this->payplug->l('Your city name is too long (max 32 characters). ')
                    . $this->payplug->l('Please change it to another one or select another payment method.');
                $billing_fields['city'] = [
                    'text' => $text,
                    'input' => [
                        [
                            'name' => 'first_name',
                            'value' => $billing_address->firstname,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'last_name',
                            'value' => $billing_address->lastname,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'address1',
                            'value' => $billing_address->address1,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'postcode',
                            'value' => $billing_address->postcode,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'city',
                            'value' => $billing_address->city,
                            'type' => 'text'
                        ],
                    ],
                ];
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
     * @param int $amount
     * @param string $country
     * @param array $operation contain x3|4_with_fees or x3|4_without_fees
     * @return array
     * @throws BadParameterException
     * @throws ConfigurationNotSetException
     * @throws ConnectionException
     * @throws HttpException
     * @throws UnexpectedAPIResponseException
     */
    public function getOneySimulations($amount, $country, $operation)
    {
        $config = $this->configurationSpecific;
        $tools = $this->toolsSpecific;

        $cache_id = 'Payplug::OneySimulations_' .
            (int)$amount . '_' .
            (string)$country . '_' .
            (string)implode('_', $operation) . '_' .
            ($config->get('PAYPLUG_SANDBOX_MODE') ? 'test' : 'live');

        // Checks if the current simulation is already saved in the database
        // If not, we do a simulation for Oney, and we will store it to the DB
        $cache_from_bdd = $this->cache->getCacheByKey($cache_id);

        if ($cache_from_bdd) {
            return $tools->tool('jsonDecode', $cache_from_bdd[0]['cache_value'], true);
        }

        try {
            $data = [
                'amount' => $amount,
                'country' => $this->getOneyCountry($country),
                'operations' => $operation,
            ];

            $simulations = \Payplug\OneySimulation::getSimulations($data);

            if (isset($simulations['details']) && $simulations['details'] == 'Access to this feature is not available.') {
                $this->payplug->updatePermissions();
            } elseif (isset($simulations['object']) && $simulations['object'] == 'error') {
                return [
                    'result' => false,
                    'error' => $simulations['message']
                ];
            } else {
                if ($simulations) {
                    ksort($simulations);
                    $to_cache = [
                        'result' => true,
                        'simulations' => $simulations
                    ];

                    // $cache_id = cache_key in db
                    // $to_cache = cache_value in db
                    if (!$this->cache->setCache($cache_id, $to_cache)) {
                        $this->logger->setParams(['process' => '[Oney Repository] setCache']);
                        $error_message = 'Error during setting Oney Simulation in DB cache [payplug.php]';
                        $error_level = 'error';
                        $this->logger->addLog($error_message, $error_level);
                    }
                }
            }

            return [
                'result' => true,
                'simulations' => $simulations
            ];
        } catch (Exception $exception) {
            return [
                'result' => false,
                'error' => $exception->__toString()
            ];
        }
    }

    /**
     * @description Get the Oney required fields from Context
     *
     * @param array $payment_data
     * @return bool
     */
    public function hasOneyRequiredFields($payment_data = [])
    {
        if (!$payment_data) {
            return false;
        }

        $tools = $this->toolsSpecific;

        // Check the shipping fields
        $shipping = $payment_data['shipping'];

        // Validate email format
        if ($tools->tool('strlen', $shipping['email']) > 100 && $tools->tool('$shipping[\'email\']', '+') !== false) {
            return true;
        } elseif ($tools->tool('strlen', $shipping['email']) > 100) {
            return true;
        } elseif (strpos($shipping['email'], '+') !== false) {
            return true;
        }

        // Validate phone number
        $valid_shipping_mobile = $this->payplug->isValidMobilePhoneNumber(
            $shipping['mobile_phone_number'],
            $shipping['country']
        );
        if (!$valid_shipping_mobile) {
            return true;
        }

        // Validate address
        if ($tools->tool('strlen', $shipping['city']) > 32) {
            return true;
        }

        // Check the billing fields
        $billing = $payment_data['billing'];

        // Validate phone number
        $valid_billing_mobile = $this->payplug->isValidMobilePhoneNumber($billing['mobile_phone_number'], $billing['country']);
        if (!$valid_billing_mobile) {
            return true;
        }

        // Validate address
        if ($tools->tool('strlen', $billing['city']) > 32) {
            return true;
        }

        return false;
    }

    /**
     * ONLY PS 1.6
     * Display Oney required fields template
     *
     * @return mixed
     */
    public function displayOneyRequiredFields()
    {
        $fields = $this->getOneyRequiredFields();

        if (!$fields) {
            return false;
        }

        $this->contextSpecific->getContext()->smarty->assign([
            'oney_required_fields' => $fields
        ]);

        return $this->payplug->display($this->payplug->constantFile, 'oney/required.tpl');
    }

    /**
     * @description Install Oney feature
     */
    public function installOney()
    {
        $this->log->info('Starting to install.');
        return ($this->installOneyConfig()
            && $this->installOneyOrderStates()
            && (new SQLtableRepository())->installOneySql()
            && $this->installOneyCarriers());
    }

    /**
     * @description Install Oney Config
     *
     * @return boolean
     */
    public function installOneyConfig()
    {
        $this->log->info('Install Oney config');

        $config = $this->configurationSpecific;
        $flag = true;
        if (!$config->updateValue('PAYPLUG_ONEY', 0) ||
            !$config->updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', '') ||
            !$config->updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', 'EUR:2000') ||
            !$config->updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', 'EUR:150') ||
            !$config->updateValue('PAYPLUG_ONEY_TOS', 0) ||
            !$config->updateValue('PAYPLUG_ONEY_TOS_URL', '')
        ) {
            $this->log->error('Installation failed: Oney config');
            $flag = false;
        }
        return $flag;
    }

    /**
     * @description Check if Oney allow a given currency
     *
     * @param $id_currency
     * @return boolean
     */
    public function isOneyAllowedCurrency($id_currency)
    {
        if ($this->validateSpecific->validate('isLoadedObject', $id_currency)) {
            $currency = $id_currency;
        } elseif (is_int($id_currency)) {
            $currency = new \Currency($id_currency);
        } else {
            return false;
        }

        if (!$this->validateSpecific->validate('isLoadedObject', $currency)) {
            return false;
        }

        // we use the Oney limit to get allowed currencies
        $oney_min_amounts = $this->toolsSpecific->tool('strtoupper', $this->configurationSpecific->get('PAYPLUG_ONEY_MIN_AMOUNTS'));
        $iso_code = $this->toolsSpecific->tool('strtoupper', $currency->iso_code);

        return strpos($oney_min_amounts, $iso_code) !== false;
    }

    /**
     * @description Check if a valid Cart for Oney
     *
     * @param $cart Cart
     * @param boolean $amount
     * @param boolean $country
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
                'error' => $is_valid_cart['error']
            ];
        }

        // check if cart address is valid
        if ($country) {
            $is_valid_addresses = $this->isValidOneyAddresses($cart->id_address_delivery, $cart->id_address_invoice);
            if (!$is_valid_addresses['result']) {
                return [
                    'result' => false,
                    'error_type' => 'invalid_addresses',
                    'error' => $is_valid_addresses['error']
                ];
            }
        }

        // check if current amount is between min and max values
        $amount = $amount ? $amount : $cart->getOrderTotal(true, \Cart::BOTH);
        $is_valid_amount = $this->isValidOneyAmount($amount, $cart->id_currency);
        if (!$is_valid_amount['result']) {
            $limits = $this->getOneyPriceLimit($cart->id_currency);
            $converted_amount = $this->payplug->convertAmount($amount);
            $error_type = $converted_amount > $limits['min'] ? 'invalid_amount_top' : 'invalid_amount_bottom';

            return ['result' => false, 'error_type' => $error_type, 'error' => $is_valid_amount['error']];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Install Oney Order State
     */
    public function installOneyOrderStates()
    {
        $oney_order_state = [
            'oney_pg' => [
                'cfg' => null,
                'template' => null,

                // OS have to be "logable" to register transaction_id
                'logable' => false,
                'send_email' => false,
                'paid' => false,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#a1f8a1',
                'name' => [
                    'en' => 'Oney - Pending',
                    'fr' => 'Oney - En attente',
                    'es' => 'Oney - Pending',
                    'it' => 'Oney - Pending',
                ],
            ],
        ];

        $flag = true;

        foreach ($oney_order_state as $key => $state) {
            $flag = $flag && $this->payplug->createOrderState($key, $state, true) && $this->payplug->createOrderState($key, $state, false);
        }
        return $flag;
    }

    /**
     * @description Install Oney Carriers
     *
     * @return boolean
     */
    public function installOneyCarriers()
    {
        $carriers = \PayPlugCarrier::getCarriers($this->contextSpecific->getContext()->language->id, true);
        $flag = true;
        foreach ($carriers as $carrier) {
            $flag = $flag && $carrier->save();
        }
        return $flag;
    }

    /**
     * @description Check if Oney is allowed
     *
     * @return boolean
     */
    public function isOneyAllowed()
    {
        return $this->payplug->isAllowed()
            && $this->configurationSpecific->get('PAYPLUG_ONEY')
            && $this->isOneyAllowedCurrency($this->contextSpecific->getContext()->currency);
    }

    /**
     * @description Check if billing and shipping addresses are valid
     *
     * @param int $id_shipping
     * @param int $id_billing
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
     * @param boolean $id_currency
     * @return array
     */
    public function isValidOneyAmount($amount, $id_currency = false)
    {
        $limits = $this->getOneyPriceLimit($id_currency);
        $convert_amount = $this->payplug->convertAmount($amount);
        if (($limits['min'] > $convert_amount) || ($convert_amount > $limits['max'])) {
            $min_amount = $this->payplug->convertAmount($limits['min'], true);
            $max_amount = $this->payplug->convertAmount($limits['max'], true);

            return [
                'result' => false,
                'error' => sprintf(
                    $this->payplug->l('The total amount of your order should be between %s and %s to pay with Oney.'),
                    $this->toolsSpecific->tool('displayPrice', $min_amount),
                    $this->toolsSpecific->tool('displayPrice', $max_amount)
                )
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Check if carrier is valid for Oney
     * Try the current selected then all available carrier
     *
     * @param $cart
     * @return array
     */
    public function isValidOneyCarrier($cart)
    {
        if (!$this->validateSpecific->validate('isLoadedObject', $cart)) {
            return [
                'result' => false,
                'error' => $this->payplug->l('The cart is unvalid'),
                'error_type' => 'invalid_carrier',
            ];
        }

        $invalid_carrier_type = ['storepickup', 'networkpickup'];

        // check if current carrier is available
        $payplug_carrier = new \PayPlugCarrier();
        $payplug_carrier = $payplug_carrier->getByIdCarrier($cart->id_carrier);

        if (!$payplug_carrier->delivery_type) {
            $carrier = new \Carrier($cart->id_carrier);
            $error = $this->payplug->l('The carrier') . ' ' . $carrier->name . ' ' . $this->payplug->l('shipping is conflicting with this payment method. ');
            $error .= $this->payplug->l('Please change the shipping method chosen at the last step.');
            return [
                'result' => false,
                'error' => sprintf($error),
                'error_type' => 'invalid_carrier',
            ];
        } elseif ((bool)in_array($payplug_carrier->delivery_type, $invalid_carrier_type, true)) {
            switch ($payplug_carrier->delivery_type) {
                case 'networkpickup':
                    $delivery_type = $this->payplug->l('Network Pickup');
                    break;
                case 'storepickup':
                default:
                    $delivery_type = $this->payplug->l('Store Pickup');
                    break;
            }


            $error = $this->payplug->l('The ') . $delivery_type . $this->payplug->l(' shipping is conflicting with this payment method. ');
            $error .= $this->payplug->l('Please change the shipping method chosen at the last step.');

            return [
                'result' => false,
                'error' => $error,
                'error_type' => 'invalid_carrier',
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Check if cart is valid for Oney
     *
     * @param Cart $cart
     * @return array
     */
    public function isValidOneyCart($cart)
    {
        if (!$this->validateSpecific->validate('isLoadedObject', $cart)) {
            return [
                'result' => false,
                'error' => $this->payplug->l('The cart is unvalid')
            ];
        }

        $nb_products = $this->contextSpecific->getContext()->cart->nbProducts();

        // todo: set as a constant
        $max = 1000;

        if ($nb_products >= $max) {
            $error = 'The payment with Oney is not available because you have more than 1000 items in your cart.';
            return [
                'result' => false,
                'error' => $this->payplug->l($error)
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Check if billing and shipping addresses are valid
     *
     * @param string $shipping_iso
     * @param string $billing_iso
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
                'error' => $this->payplug->l($error)
            ];
        }

        // check if the shipping country are different then return false
        $iso_code = $this->toolsSpecific->tool('strtoupper', $shipping_iso);
        $allow_countries = $this->toolsSpecific->tool('strtoupper', $this->configurationSpecific->get('PAYPLUG_ONEY_ALLOWED_COUNTRIES'));
        if (!$allow_countries) {
            return [
                'result' => false,
                'type' => 'no_country',
                'error' => $this->payplug->l('No countries are configured to use oney.')
            ];
        }

        $iso_list = explode(',', $allow_countries);
        if (!in_array($iso_code, $iso_list, true)) {
            $list = [];
            foreach ($iso_list as $iso) {
                $id_country = \Country::getByIso($iso);
                $list[] = \Country::getNameById($this->contextSpecific->getContext()->language->id, $id_country);
            }
            return [
                'result' => false,
                'type' => 'invalid',
                'error' => sprintf(
                    $this->payplug->l('For a payment with Oney, delivery and billing addresses must be in %s'),
                    implode(', ', $list)
                )
            ];
        }

        return ['result' => true, 'error' => false];
    }

    /**
     * @description Install Oney feature
     */
    public function uninstallOney()
    {
        return $this->deleteOneyConfig() && (new SQLtableRepository())->uninstallOneySql();
    }

    /**
     * @description Delete basic configuration
     *
     * @return bool
     */
    public function deleteOneyConfig()
    {
        $config = $this->configurationSpecific;

        return ($config->deleteByName('PAYPLUG_ONEY')
            && $config->deleteByName('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            && $config->deleteByName('PAYPLUG_ONEY_MAX_AMOUNTS')
            && $config->deleteByName('PAYPLUG_ONEY_MIN_AMOUNTS')
            && $config->deleteByName('PAYPLUG_ONEY_TOS')
            && $config->deleteByName('PAYPLUG_ONEY_TOS_URL'));
    }
}
