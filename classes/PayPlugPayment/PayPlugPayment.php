<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugBackward.php');

class PayPlugPayment
{
    /** @var Context */
    protected $context;

    /** @var Cart */
    protected $cart;

    /** @var Address */
    protected $billing;

    /** @var Address */
    protected $shipping;

    /** @var Customer */
    protected $customer;

    /** @var string */
    public $card;

    /** @var array */
    public $payment_url = [];

    /** @var bool */
    public $is_valid = false;

    /** @var string */
    public $type;

    /** @var array */
    public $definition_tab = [
        'currency' => ['type' => 'string', 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 3],
        'shipping' => [
            'type' => 'array',
            'fields' => [
                'title' => ['type' => 'string', 'validate' => 'isName', 'required' => false, 'size' => 32],
                'first_name' => ['type' => 'string', 'validate' => 'isName', 'required' => true, 'size' => 32],
                'last_name' => ['type' => 'string', 'validate' => 'isName', 'required' => true, 'size' => 32],
                'company_name' => [
                    'type' => 'string',
                    'validate' => 'isCleanHtml',
                    'required' => false,
                    'size' => 128
                ],
                'email' => ['type' => 'string', 'validate' => 'isEmail', 'required' => true, 'size' => 128],
                'mobile_phone_number' => [
                    'type' => 'string',
                    'validate' => 'isPhoneNumber',
                    'required' => false,
                    'size' => 32
                ],
                'landline_phone_number' => [
                    'type' => 'string',
                    'validate' => 'isPhoneNumber',
                    'required' => false,
                    'size' => 32
                ],
                'address1' => ['type' => 'string', 'validate' => 'isAddress', 'required' => true, 'size' => 128],
                'address2' => ['type' => 'string', 'validate' => 'isAddress', 'required' => false, 'size' => 128],
                'postcode' => ['type' => 'string', 'validate' => 'isPostCode', 'required' => true, 'size' => 12],
                'city' => ['type' => 'string', 'validate' => 'isCityName', 'required' => true, 'size' => 64],
                'state' => ['type' => 'string', 'validate' => 'isCityName', 'required' => false, 'size' => 64],
                'country' => [
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'required' => true,
                    'size' => 2
                ],
                'language' => [
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'required' => true,
                    'size' => 2
                ],
                'delivery_type' => ['type' => 'string', 'validate' => 'isName', 'required' => true, 'size' => 16],
            ],
        ],
        'billing' => [
            'type' => 'array',
            'fields' => [
                'title' => ['type' => 'string', 'validate' => 'isName', 'required' => false, 'size' => 32],
                'first_name' => ['type' => 'string', 'validate' => 'isName', 'required' => true, 'size' => 32],
                'last_name' => ['type' => 'string', 'validate' => 'isName', 'required' => true, 'size' => 32],
                'company_name' => [
                    'type' => 'string',
                    'validate' => 'isCleanHtml',
                    'required' => true,
                    'size' => 128
                ],
                'email' => ['type' => 'string', 'validate' => 'isEmail', 'required' => true, 'size' => 128],
                'mobile_phone_number' => [
                    'type' => 'string',
                    'validate' => 'isPhoneNumber',
                    'required' => false,
                    'size' => 32
                ],
                'landline_phone_number' => [
                    'type' => 'string',
                    'validate' => 'isPhoneNumber',
                    'required' => false,
                    'size' => 32
                ],
                'address1' => ['type' => 'string', 'validate' => 'isAddress', 'required' => true, 'size' => 128],
                'address2' => ['type' => 'string', 'validate' => 'isAddress', 'required' => false, 'size' => 128],
                'postcode' => ['type' => 'string', 'validate' => 'isPostCode', 'required' => true, 'size' => 12],
                'city' => ['type' => 'string', 'validate' => 'isCityName', 'required' => true, 'size' => 64],
                'state' => ['type' => 'string', 'validate' => 'isCityName', 'required' => false, 'size' => 64],
                'country' => [
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'required' => true,
                    'size' => 2
                ],
                'language' => [
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'required' => true,
                    'size' => 2
                ],
            ],
        ],
        'hosted_payment' => [
            'type' => 'array',
            'fields' => [
                'return_url' => [
                    'type' => 'string',
                    'validate' => 'isCleanHtml',
                    'required' => true,
                    'size' => 255
                ],
                'cancel_url' => [
                    'type' => 'string',
                    'validate' => 'isCleanHtml',
                    'required' => true,
                    'size' => 255
                ]
            ],
        ],
        'notification_url' => ['type' => 'string', 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255],
        'metadata' => [
            'type' => 'array',
            'fields' => [
                'Client' => ['type' => 'int', 'validate' => 'isUnsignedId', 'required' => false],
                'Cart' => ['type' => 'int', 'validate' => 'isUnsignedId', 'required' => true],
                'Website' => ['type' => 'string', 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255],
                'cms_billing_country' => [
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'required' => false,
                    'size' => 2
                ],
                'cms_shipping_country' => [
                    'type' => 'string',
                    'validate' => 'isLanguageIsoCode',
                    'required' => false,
                    'size' => 2
                ],
            ]
        ]
    ];

    const DELIVERY_TYPE_BILLING = 'BILLING';
    const DELIVERY_TYPE_USED = 'VERIFIED';
    const DELIVERY_TYPE_UNUSED = 'NEW';
    const DELIVERY_TYPE_DEFAULT = 'OTHER';

    /** @var array */
    public $payment_tab = [];

    /** @var bool */
    public $debug = false;

    /** @var bool */
    public $is_allowed = true;

    /** @var array */
    public $errors = [];

    /** @var bool */
    public $is_deferred = false;

    /** @var array */
    private $metadatas = [];

    /** @var Module Payplug */
    protected $module = null;

    /**
     * Constructor
     *
     * @param string $id_card
     * @param Array $options
     * @param Context $context
     * @return PayplugPayment
     */
    public function __construct($id_card = null, $options = [], Context $context = null)
    {
        $this->context = $context ? $context : Context::getContext();

        $this->cart = $this->context->cart;
        if (!Validate::isLoadedObject($this->cart)) {
            // todo: log failure customer
            return false;
        } else {
            $this->setMetaDatas('Cart', (int)$this->cart->id);
        }

        $this->customer = new Customer($this->cart->id_customer);
        if (!Validate::isLoadedObject($this->customer)) {
            // todo: log failure customer
            return false;
        } else {
            $this->setMetaDatas('Client', (int)$this->customer->id);
        }

        $this->billing = new Address($this->cart->id_address_invoice);
        $this->shipping = new Address($this->cart->id_address_delivery);

        // We don't need to validate shipping address because it can be null
        if (!Validate::isLoadedObject($this->billing)) {
            // todo: log failure billing address
            return false;
        }

        // todo: set an object rather than a integer
        $this->card = $id_card;

        $this->module = new Payplug();

        $this->debug = $this->module->getConfiguration('PAYPLUG_DEBUG_MODE');

        $this->setPaymentUrl();

        $this->generatePaymentTab();
        $this->validatePaymentTab();

        if (isset($options) && isset($options['deferred'])) {
            $this->is_deferred = true;
        }
        return $this;
    }

    /**
     * Create payment from PayPlug lib
     *
     * @return array
     */
    public function create()
    {
        if (!$this->is_valid) {
            // todo: add log failure create
            return [
                'resource' => null,
                'error' => true,
                'message' => 'Cannot create payment, invalid payment method',
            ];
        }

        $this->register();

        if ($this->debug) {
            $log = new MyLogPHP(_PS_MODULE_DIR_ . '/payplug/log/prepare_payment.csv');
            $log->info('Starting payment.');
            foreach ($this->payment_tab as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $n_key => $n_value) {
                        $log->info($n_key . ' : ' . (is_array($n_value) ? json_encode($n_value) : $n_value));
                    }
                } else {
                    $log->info($key . ' : ' . $value);
                }
            }
        }

        try {
            $payment = \Payplug\Payment::create($this->payment_tab);
            return [
                'resource' => $payment,
                'error' => false,
                'message' => null,
            ];
        } catch (Exception $e) {
            return [
                'resource' => null,
                'error' => true,
                'message' => $e->__toString(),
            ];
        }
    }

    /**
     * Get payment from PayPlug lib
     *
     * @param string $pay_id
     * @return PayplugPayment $payment
     */
    public function get($pay_id)
    {
        try {
            $payment = \Payplug\Payment::retrieve($pay_id);
        } catch (Exception $e) {
            // todo: add log
            $payment = false;
        }

        return $payment;
    }

    /**
     * Get payment from PayPlug lib
     *
     * @param string $pay_id
     * @param Array $data
     * @return PayplugPayment $payment
     */
    public function update($pay_id, $data)
    {
        $payment = $this->get($pay_id);
        return $payment ? $payment->update($data) : false;
    }

    /**
     * Generate the tab to create the payment in Payplug API
     */
    public function generatePaymentTab()
    {
        $payment_tab = [];

        // hydrate addresses tab
        $payment_tab['billing'] = $this->generateBillingTab();
        $payment_tab['shipping'] = $this->generateShippingTab();

        // hydrate cart datas
        $payment_tab['currency'] = $this->getCartCurrency();

        // hydrate url
        $payment_tab['notification_url'] = $this->payment_url['notification'];
        $payment_tab['hosted_payment'] = [
            'return_url' => $this->payment_url['return'],
            'cancel_url' => $this->payment_url['cancel'],
        ];

        // hydrate metadata
        $payment_tab['metadata'] = $this->getAllMetaDatas();

        $this->payment_tab = $payment_tab;
    }

    /**
     * Check if payment tab is valid
     *
     * @return bool
     */
    protected function validatePaymentTab()
    {
        $this->errors = [];

        foreach ($this->definition_tab as $key => $tab) {
            if (!isset($this->payment_tab[$key])) {
                continue;
            }
            $value = $this->payment_tab[$key];
            $this->checkField($key, $value, $tab);
        }

        $this->is_valid = empty($this->errors) && $this->is_allowed;
    }

    /**
     * Check if the given field is valid
     *
     * @param string $name
     * @param mixed $value
     * @param Array $field
     * @param bool $is_child
     * @return bool
     */
    private function checkField($name, $value, $field, $parent = null)
    {
        if ($field['type'] == 'array' && isset($field['fields'])) {
            foreach ($field['fields'] as $key => $child_field) {
                $child_value = isset($this->payment_tab[$name][$key]) ? $this->payment_tab[$name][$key] : null;
                $this->checkField($key, $child_value, $child_field, $name);
            }
        } elseif ($field['type'] == 'iterable') {
            $list = $parent ? $this->payment_tab[$parent][$name] : $this->payment_tab[$name];
            foreach ($list as $row) {
                foreach ($field['fields'] as $key => $child_field) {
                    $child_value = $row[$key];
                    $this->checkField($key, $child_value, $child_field, $name);
                }
            }
        } else {
            if (isset($field['allowed']) && !empty($field['allowed']) && !in_array($value, $field['allowed'], true)) {
                $this->errors[] = 'Invalid value for field ' . $name . ': ' . $value
                    . ' / allowed: ' . implode('|', $field['allowed']);
            } elseif (!$this->isValidField($field['validate'], $value)) {
                if ($field['required']) {
                    $this->errors[] = 'Invalid required field ' . $name . ': ' . $value
                        . ' / method: ' . $field['validate'];
                }
                return false;
            } elseif (!$value && $field['validate'] != 'isBool') {
                if ($parent && (isset($this->payment_tab[$parent][$name]) || isset($field['default']))) {
                    $this->payment_tab[$parent][$name] = isset($field['default']) ? $field['default'] : null;
                } elseif (isset($this->payment_tab[$name]) || isset($field['default'])) {
                    $this->payment_tab[$name] = isset($field['default']) ? $field['default'] : null;
                }
            }
        }

        return true;
    }

    /**
     * Simule Validate::$field['validate']($value) for updated php version
     *
     * @param $method
     * @param $value
     * @return bool
     */
    public function isValidField($method, $value)
    {
        switch ($method) {
            case 'isAddress':
                return (bool)Validate::isAddress($value);
            case 'isBool':
                return (bool)Validate::isBool($value);
            case 'isCleanHtml':
                return (bool)Validate::isCleanHtml($value);
            case 'isCityName':
                return (bool)Validate::isCityName($value);
            case 'isDate':
                return (bool)Validate::isDate($value);
            case 'isEmail':
                return (bool)Validate::isEmail($value);
            case 'isInt':
                return (bool)Validate::isInt($value);
            case 'isLanguageIsoCode':
                return (bool)Validate::isLanguageIsoCode($value);
            case 'isName':
                return (bool)Validate::isName($value);
            case 'isPhoneNumber':
                return (bool)Validate::isPhoneNumber($value);
            case 'isPostCode':
                return (bool)Validate::isPostCode($value);
            case 'isUnsignedId':
                return (bool)Validate::isUnsignedId($value);
            default:
                return false;
        }

        return true;
    }

    /**
     * Generate billing tab
     *
     * @return array    from generateAddressTab
     */
    public function generateBillingTab()
    {
        $address_fields = $this->definition_tab['billing']['fields'];
        return $this->generateAddressTab($this->billing, $address_fields);
    }

    /**
     * Generate billing tab
     *
     * @return array    from generateAddressTab
     */
    public function generateShippingTab()
    {
        $address_fields = $this->definition_tab['shipping']['fields'];
        return $this->generateAddressTab($this->shipping, $address_fields);
    }

    /**
     * Generate a address tab
     *
     * @param object Address
     * @param array $fields
     * @return array
     */
    protected function generateAddressTab(Address $address, $fields = [])
    {
        if (!is_object($address)) {
            $address = new Address((int)$address);
        }
        if (!is_array($fields) || empty($fields)) {
            return false;
        }

        if (!Validate::isLoadedObject($address)) {
            // todo: log failure
            return false;
        }

        unset($address->country);
        $address_tab = [];

        foreach ($fields as $key => $field) {
            $value = $this->getField($address, $key);
            if ($value === null) {
                $method = $this->getFieldMethod($key, 'address');
                $value = method_exists($this, $method) ? $this->$method($address) : null;
            }
            $address_tab[$key] = $value;
        }

        return $address_tab;
    }

    /**
     * Get all Meta Datas
     *
     * @return array
     */
    private function getAllMetaDatas()
    {
        return $this->metadatas;
    }

    /**
     * Set Meta Datas
     *
     * @param $key
     * @param $value
     */
    private function setMetaDatas($key, $value)
    {
        $this->metadatas[$key] = $value;
    }

    /**
     * Set CMS Billing/Shipping Iso code from a given Address
     * @param Address $address
     * @return bool
     */
    private function setMetaDatasAddressIso(Address $address)
    {
        if (!Validate::isLoadedObject($address)) {
            // todo: log errors
            return false;
        }

        $country = new Country($address->id_country);
        if (!Validate::isLoadedObject($country)) {
            // todo: log errors
            return false;
        }

        if ($this->billing->id == $this->shipping->id) {
            $this->setMetaDatas('cms_billing_country', $country->iso_code);
            $this->setMetaDatas('cms_shipping_country', $country->iso_code);
        } elseif ($this->shipping->id == $address->id) {
            $this->setMetaDatas('cms_shipping_country', $country->iso_code);
        } elseif ($this->billing->id == $address->id) {
            $this->setMetaDatas('cms_billing_country', $country->iso_code);
        }
    }

    /**
     * Get the field method for a given field
     *
     * @param string $field
     * @param string $type
     * @return string|null
     */
    private function getFieldMethod($field, $type = null)
    {
        $field = preg_replace('/[^a-z0-9]+/i', ' ', $field);
        $field = $type . ' ' . $field;
        $field = ucwords($field);
        $field = str_replace(" ", "", $field);
        return 'get' . $field;
    }

    /**
     * Get field from given object
     *
     * @param object Address|Customer
     * @param string $field
     * @return string|null
     */
    private function getField($object, $field)
    {
        return isset($object->$field) ? $object->$field : null;
    }

    /**
     * Get Address title form Customer gender
     *
     * @param Address $address
     * @return string
     */
    private function getAddressTitle(Address $address)
    {
        return null;
//
//        // todo: send later the real gender
//        $id_lang = isset($this->customer->id_lang) ? $this->customer->id_lang : _PS_LANG_DEFAULT_;
//        return PayplugBackward::getCustomerGender($this->customer->id_gender, $id_lang);
    }

    /**
     * Get Address firstname
     *
     * @param Address $address
     * @return string
     */
    private function getAddressFirstName(Address $address)
    {
        return $address->firstname;
    }

    /**
     * Get Address lastname
     *
     * @param Address $address
     * @return string
     */
    private function getAddressLastName(Address $address)
    {
        return $address->lastname;
    }

    /**
     * Get Address email from customer
     *
     * @param Address $address
     * @return string
     */
    private function getAddressEmail(Address $address)
    {
        return $this->customer->email;
    }

    /**
     * Get Address company name
     *
     * @param Address $address
     * @return string
     */
    private function getAddressCompanyName(Address $address)
    {
        return $address->company;
    }

    /**
     * Get Address landline phone number
     *
     * @param Address $address
     * @return string
     */
    private function getAddressLandlinePhoneNumber(Address $address)
    {
        $address_country = new Country($address->id_country);
        return $address->phone ? $this->formatPhoneNumber($address->phone, $address_country) : null;
    }

    /**
     * Get Address mobile phone number
     *
     * @param Address $address
     * @return string
     */
    private function getAddressMobilePhoneNumber(Address $address)
    {
        $address_country = new Country($address->id_country);
        return $address->phone_mobile ? $this->formatPhoneNumber($address->phone_mobile, $address_country) : null;
    }

    /**
     * Get Address state
     *
     * @param Address $address
     * @return string
     */
    private function getAddressState(Address $address)
    {
        return $address->id_state ? State::getNameById($address->id_state) : null;
    }

    /**
     * Get Address country
     *
     * @param Address $address
     * @return string
     */
    private function getAddressCountry(Address $address)
    {
        $iso_code = $this->getIsoCodeByCountryId((int)$address->id_country);

        if (!$iso_code) {
            $this->setMetaDatasAddressIso($address);

            $iso_code_list = $this->getIsoCodeList();
            $language = new Language($this->module->getConfiguration('PS_LANG_DEFAULT'));
            if (in_array(Tools::strtoupper($language->iso_code), $iso_code_list, true)) {
                $iso_code = $language->iso_code;
            } else {
                $iso_code = 'FR';
            }
        }

        return Tools::strtoupper($iso_code);
    }

    /**
     * Get Address language isocode form context
     *
     * @return string
     */
    private function getAddressLanguage(Address $address)
    {
        return $this->getIsoFromLanguageCode($this->context->language);
    }

    /**
     * Get Address language isocode form context
     *
     * @return string BILLING|VERIFIED|NEW
     */
    private function getAddressDeliveryType(Address $address)
    {
        if ($this->cart->id_address_delivery == $this->cart->id_address_invoice) {
            return PayPlugPayment::DELIVERY_TYPE_BILLING;
        }
        if ($address->isUsed()) {
            return PayPlugPayment::DELIVERY_TYPE_USED;
        } else {
            return PayPlugPayment::DELIVERY_TYPE_UNUSED;
        }
    }

    /**
     * Get the cart currency
     *
     * @return string
     */
    public function getCartCurrency()
    {
        $currency = $this->cart->id_currency;
        $result_currency = Currency::getCurrency($currency);
        $supported_currencies = explode(';', Tools::strtoupper($this->module->getConfiguration('PAYPLUG_CURRENCIES')));

        if (!in_array(Tools::strtoupper($result_currency['iso_code']), $supported_currencies, true)) {
            return false;
        }

        return Tools::strtoupper($result_currency['iso_code']);
    }

    /**
     * Get the cart amount
     *
     * @param string $currency
     * @return int
     */
    public function getCartAmount($currency)
    {
        $amount = $this->cart->getOrderTotal(true, Cart::BOTH);
        $amount = $this->module->convertAmount($amount);
        $current_amounts = $this->getAmountsByCurrency($currency);
        $current_min_amount = $current_amounts['min_amount'];
        $current_max_amount = $current_amounts['max_amount'];

        if ($amount < $current_min_amount || $amount > $current_max_amount) {
            return false;
        }

        return $amount;
    }

    /**
     * Return url
     */
    public function setPaymentUrl()
    {
        $this->payment_url = [
            'return' => PayplugBackward::getModuleLink(
                'payplug',
                'validation',
                [
                    'ps' => 1,
                    'cartid' => (int)$this->cart->id],
                true
            ),
            'cancel' => PayplugBackward::getModuleLink(
                'payplug',
                'validation',
                [
                    'ps' => 2,
                    'cartid' => (int)$this->cart->id],
                true
            ),
            'notification' => PayplugBackward::getModuleLink(
                'payplug',
                'ipn',
                [],
                true
            ),
        ];

        $this->setMetaDatas('Website', Tools::getShopDomainSsl(true));
    }

    /**
     * Get all country iso-code of ISO 3166-1 alpha-2 norm
     * Source: DB PayPlug
     *
     * @return array | null
     */
    public function getIsoCodeList()
    {
        $country_list_path = _PS_MODULE_DIR_ . 'payplug/lib/iso_3166-1_alpha-2/data.csv';
        $iso_code_list = [];
        if (($handle = fopen($country_list_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $iso_code_list[] = Tools::strtoupper($data[0]);
            }
            fclose($handle);
            return $iso_code_list;
        } else {
            return null;
        }
    }

    /**
     * Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm
     *
     * @param int $country_id
     * @return int | false
     */
    public function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = $this->getIsoCodeList();

        if (!is_array($iso_code_list) || empty($iso_code_list) || !count($iso_code_list)) {
            return false;
        }
        if (!Validate::isInt($country_id)) {
            return false;
        }
        $country = new Country((int)$country_id);
        if (!Validate::isLoadedObject($country)) {
            return false;
        }
        if (!in_array(Tools::strtoupper($country->iso_code), $iso_code_list, true)) {
            return false;
        } else {
            return Tools::strtoupper($country->iso_code);
        }
    }

    /**
     * Get amounts with the right currency
     *
     * @param string $iso_code
     * @return array
     */
    public function getAmountsByCurrency($iso_code)
    {
        $min_amounts = [];
        $max_amounts = [];
        foreach (explode(';', Tools::strtoupper(
            $this->module->getConfiguration('PAYPLUG_MIN_AMOUNTS')
        )) as $amount_cur
            ) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $min_amounts[$cur[1]] = (int)$cur[2];
        }
        foreach (explode(
            ';',
            Tools::strtoupper(
                $this->module->getConfiguration('PAYPLUG_MAX_AMOUNTS')
            )
        ) as $amount_cur
            ) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $max_amounts[$cur[1]] = (int)$cur[2];
        }
        $current_min_amount = $min_amounts[Tools::strtoupper($iso_code)];
        $current_max_amount = $max_amounts[Tools::strtoupper($iso_code)];

        return ['min_amount' => $current_min_amount, 'max_amount' => $current_max_amount];
    }

    /**
     * Register payment for later use
     *
     * @param string $pay_id
     * @return bool
     */
    public function register($pay_id = 'pending')
    {
        if ($inst_id = $this->getInstallmentCart()) {
            $this->deleteInstallmentCart($inst_id);
        }

        $sql = '
            SELECT * 
            FROM ' . _DB_PREFIX_ . 'payplug_payment_cart ppc  
            WHERE ppc.id_cart = ' . (int)$this->cart->id;
        $exists = Db::getInstance()->getRow($sql);
        $date_upd = date('Y-m-d H:i:s');
        if (!$exists) {
            //insert
            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'payplug_payment_cart (id_payment, id_cart, is_pending, date_upd)
                    VALUES (\'' . pSQL($pay_id) . '\', ' . (int)$this->cart->id . ', 0, \'' . pSQL($date_upd) . '\')';
        } else {
            //update
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'payplug_payment_cart ppc  
                    SET ppc.id_payment = \'' . pSQL($pay_id) . '\', ppc.date_upd = \'' . pSQL($date_upd) . '\'
                    WHERE ppc.id_cart = ' . (int)$this->cart->id;
        }

        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Retrieve installment stored
     *
     * @return int OR bool
     */
    public function getInstallmentCart()
    {
        $sql = 'SELECT `id_installment` 
                FROM `' . _DB_PREFIX_ . 'payplug_installment_cart`
                WHERE `id_cart` = ' . (int)$this->cart->id;
        $installment_cart = Db::getInstance()->getValue($sql);
        return $installment_cart ? $installment_cart : false;
    }

    /**
     * Delete stored installment
     *
     * @param string $inst_id
     * @return bool
     */
    public function deleteInstallmentCart($inst_id)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'payplug_installment_cart` 
                WHERE `id_cart` = ' . (int)$this->cart->id . ' 
                AND `id_installment` = \'' . pSQL($inst_id) . '\'';
        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Delete stored installment
     *
     * @param string $inst_id
     * @return bool
     */
    public function deleteInstallment($inst_id)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'payplug_installment_cart` 
                WHERE `id_cart` = ' . (int)$this->cart->id . ' 
                AND `id_installment` = \'' . pSQL($inst_id) . '\'';
        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Retrieve installment stored
     *
     * @return int OR bool
     */
    public function getInstallment()
    {
        $sql = 'SELECT `id_installment` 
                FROM `' . _DB_PREFIX_ . 'payplug_installment_cart`
                WHERE `id_cart` = ' . (int)$this->cart->id;
        $installment_cart = Db::getInstance()->getValue($sql);
        return $installment_cart ? $installment_cart : false;
    }

    /**
     * Delete stored payment
     *
     * @param string $pay_id
     * @return bool
     */
    public function deletePaymentCart($pay_id)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'payplug_payment_cart` 
                WHERE `id_cart` = ' . (int)$this->cart->id . ' 
                AND `id_payment` = \'' . pSQL($pay_id) . '\'';
        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Retrieve payment stored
     *
     * @return int OR bool
     */
    public function getPaymentCart()
    {
        $sql = 'SELECT `id_payment` 
                FROM `' . _DB_PREFIX_ . 'payplug_payment_cart`
                WHERE `id_cart` = ' . (int)$this->cart->id;
        $installment_cart = Db::getInstance()->getValue($sql);
        return $installment_cart ? $installment_cart : false;
    }

    /**
     * Retrieve installment stored
     *
     * @return int OR bool
     */
    public function isPaidInstallment()
    {
        $req_installment_cart = '
            SELECT pic.id_installment 
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic 
            WHERE pic.id_cart = ' . (int)$this->cart->id;
        $res_installment_cart = Db::getInstance()->getValue($req_installment_cart);
        if (!$res_installment_cart) {
            return false;
        }

        return $res_installment_cart;
    }

    /**
     * Check if payment method is valid for given id
     *
     * @param PayplugPayment $payment
     * @return bool
     */
    public function isValidPayment($payment)
    {
        if (!is_object($payment)) {
            $payment = $this->get($payment);
        }
        return !$payment->failure;
    }

    /**
     * Return international formated phone number (norm E.164)
     *
     * @param $phone_number
     * @param $country
     * @return string|null
     */
    public function formatPhoneNumber($phone_number, Country $country)
    {
        if (!Validate::isLoadedObject($country)) {
            return null;
        }

        try {
            //load libphonenumber
            if (!class_exists('libphonenumber\PhoneNumberUtil')) {
                include_once(_PS_MODULE_DIR_ . 'payplug/lib/libphonenumber/init.php');
            }

            // then format code
            $iso_code = $this->getIsoCodeByCountryId($country->id);
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            return $phone_util->isValidNumber($parsed) ?
                $phone_util->format($parsed, \libphonenumberlight\PhoneNumberFormat::E164) : null;
        } catch (Exception $e) {
            // todo: add log
            return null;
        }
    }

    /**
     * @description Get iso code from language code
     * Language code is like 'fr-be', we explode it in array (0 => 'fr', 1 => 'be')
     * then we use array[0] witch is the language while array[1] is the localization.
     *
     * @param Language $language
     * @return string
     */
    public function getIsoFromLanguageCode(Language $language)
    {
        if (!Validate::isLoadedObject($language)) {
            return false;
        }
        $parse = explode('-', $language->language_code);
        return Tools::strtolower($parse[0]);
    }
}
