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

use PayPlug\src\exceptions\BadParameterException;

class PaymentMethod
{
    /** @var bool */
    public $cancellable = true;

    /** @var bool */
    public $force_resource = false;

    /** @var bool */
    public $refundable;
    /** @var object */
    protected $api_service;

    /** @var object */
    protected $configuration;

    /** @var object */
    protected $configuration_adapter;

    /** @var object */
    protected $context;

    /** @var object */
    protected $country_adapter;

    /** @var object */
    protected $currency_adapter;

    /** @var object */
    protected $dependencies;

    /** @var array */
    protected $external_url;

    /** @var string */
    protected $img_path;

    /** @var string */
    protected $iso_code;

    /** @var object */
    protected $link;

    /** @var object */
    protected $logger;

    /** @var string */
    protected $name;

    /** @var string */
    protected $order_name;

    /** @var array */
    protected $translation;

    /** @var object */
    protected $tools;

    /** @var object */
    protected $validate_adapter;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->name = '';
        $this->order_name = '';
        $this->refundable = true;
    }

    /**
     * @description Abort an resource from a given resource id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function abort($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::retrieve - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);

        if (!$stored_resource) {
            $this->logger->addLog('PaymentMethod::retrieve - Can\'t find stored payment from given resource id.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t find stored payment from given resource id',
            ];
        }

        // We retrieve the payment from the stored payment configuration
        $is_live = isset($stored_resource['is_live']) && (bool) $stored_resource['is_live'];
        $this->api_service->initialize((bool) $is_live);
        $is_installment = 'installment' == $stored_resource['method'];
        $abort = $is_installment
            ? $this->api_service->abortInstallment($resource_id)
            : $this->api_service->abortPayment($resource_id);

        // If we don't find the payment, for retrocompatibility we switch the mode then try again
        // Keep this code as long as the retrocompatibility is needed
        if (!$abort['result']) {
            $this->api_service->initialize(!(bool) $is_live);
            $abort = $is_installment
                ? $this->api_service->abortInstallment($resource_id)
                : $this->api_service->abortPayment($resource_id);
        }

        // Then retrieve the current mode from configuration
        $is_live = !(bool) $this->configuration->getValue('sandbox_mode');
        if ($stored_resource['is_live'] != $is_live) {
            $this->api_service->initialize((bool) $is_live);
        }

        return $abort;
    }

    /**
     * @description Capture an resource from a given resource id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function capture($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::retrieve - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);

        if (!$stored_resource) {
            $this->logger->addLog('PaymentMethod::retrieve - Can\'t find stored payment from given resource id.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t find stored payment from given resource id',
            ];
        }

        // We retrieve the payment from the stored payment configuration
        $is_live = isset($stored_resource['is_live']) && (bool) $stored_resource['is_live'];
        $this->api_service->initialize((bool) $is_live);
        $capture = $this->api_service->capturePayment($resource_id);

        // If we don't find the payment, for retrocompatibility we switch the mode then try again
        // Keep this code as long as the retrocompatibility is needed
        if (!$capture['result']) {
            $this->api_service->initialize(!(bool) $is_live);
            $capture = $this->api_service->capturePayment($resource_id);
        }

        // Then retrieve the current mode from configuration
        $is_live = !(bool) $this->configuration->getValue('sandbox_mode');
        if ($stored_resource['is_live'] != $is_live) {
            $this->api_service->initialize((bool) $is_live);
        }

        return $capture;
    }

    /**
     * @description Get an object property
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->{$key};
    }

    /**
     * @description Get the available payment methods
     *
     * @return string[]
     */
    public function getAvailablePaymentMethod()
    {
        return [
            'one_click',
            'standard',
            'installment',
            'amex',
            'applepay',
            'bancontact',
            'satispay',
            'mybank',
            'ideal',
            'oney',
            'email_link',
            'sms_link',
        ];
    }

    /**
     * @description Get collection of payment method objects
     *
     * @return array
     */
    public function getAvailablePaymentMethodsObject()
    {
        $payment_methods = $this->getAvailablePaymentMethod();
        $payment_methods_obj = [];
        if ($payment_methods) {
            foreach ($payment_methods as $name) {
                $class_name = '\PayPlug\src\models\classes\paymentMethod\\';
                $class_name .= str_replace('_', '', ucwords($name, '_')) . 'PaymentMethod';
                if (class_exists($class_name)) {
                    $payment_methods_obj[$name] = new $class_name($this->dependencies);
                }
            }
        }

        return $payment_methods_obj;
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
            $this->logger->addLog('PaymentMethod::getOption: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        if (!is_string($this->name) || '' == $this->name) {
            $this->logger->addLog('PaymentMethod::getOption: Can\'t load option the name is missing.');

            return [];
        }

        // If no configuration given, get the default one
        if (!isset($current_configuration[$this->name])) {
            $default_payment_method = json_decode($this->configuration->getDefault('payment_methods'), true);

            $current_configuration[$this->name] = $default_payment_method[$this->name];
        }

        return [
            'type' => 'payment_method',
            'name' => $this->name,
            'title' => $this->translation[$this->name]['title'],
            'image' => $this->img_path . 'svg/payment/' . $this->name . '.svg',
            'checked' => (bool) $current_configuration[$this->name],
            'available_test_mode' => true,
            'descriptions' => [
                'live' => [
                    'description' => $this->translation[$this->name]['descriptions']['live'],
                    'link_know_more' => isset($this->external_url[$this->name]) ? [
                        'text' => $this->translation[$this->name]['link'],
                        'url' => $this->external_url[$this->name],
                        'target' => '_blank',
                    ] : [],
                ],
                'sandbox' => [
                    'description' => isset($this->translation[$this->name]['descriptions']['sandbox']) ? $this->translation[$this->name]['descriptions']['sandbox'] : $this->translation[$this->name]['descriptions']['live'],
                    'link_know_more' => isset($this->external_url[$this->name]) ? [
                        'text' => $this->translation[$this->name]['link'],
                        'url' => $this->external_url[$this->name],
                        'target' => '_blank',
                    ] : [],
                ],
            ],
        ];
    }

    /**
     * @description Get collection of options for given configuration
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getOptionCollection($current_configuration = [])
    {
        $this->setParameters();

        if (!is_array($current_configuration)) {
            $this->logger->addLog('PaymentMethod::getOptionCollection: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $available_payment_methods = $this->getAvailablePaymentMethod();
        $options = [];

        if ($available_payment_methods) {
            foreach ($available_payment_methods as $payment_method) {
                if ($this->dependencies->configClass->isValidFeature('feature_' . $payment_method)) {
                    $obj = $this->getPaymentMethod($payment_method);
                    if (is_object($obj)) {
                        $option = $obj->getOption($current_configuration);
                        if (!empty($option)) {
                            $options[$payment_method] = $option;
                        }
                    }
                }
            }
        }

        return $options;
    }

    /**
     * @description Get order tab for given resource to create the order
     *
     * @param array $retrieve
     *
     * @return array
     */
    public function getOrderTab($retrieve = [])
    {
        $this->setParameters();

        if (!is_array($retrieve) || empty($retrieve)) {
            $this->logger->addLog('PaymentMethod::getOrderTab() - Invalid argument given, $retrieve must be a non empty array.');

            return [];
        }

        $resource = $retrieve['resource'];
        if (!is_object($resource) || null == $resource) {
            $this->logger->addLog('PaymentMethod::getOrderTab() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        $amount = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($resource->amount, true);

        $state_addons = $resource->is_live ? '' : '_test';
        $state = (bool) $resource->is_paid ? 'order_state_paid' : 'order_state_pending';
        $order_state = $this->configuration->getValue($state . $state_addons);

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();

        return [
            'order_state' => $order_state,
            'amount' => $amount,
            'module_name' => $translation['module_name'][$this->order_name ?: 'default'],
        ];
    }

    /**
     * @description Get payment method object for a given name
     *
     * @param string $name
     *
     * @return array|mixed
     */
    public function getPaymentMethod($name = '')
    {
        $this->setParameters();

        if (!is_string($name) || !$name) {
            $this->logger->addLog('PaymentMethod::getPaymentMethod: Can\'t load option the name is missing.');

            return [];
        }

        $payment_methods = $this->getAvailablePaymentMethodsObject();
        if (!array_key_exists($name, $payment_methods)) {
            $this->logger->addLog('PaymentMethod::getPaymentMethod: Can\'t load option the name is missing.');

            return [];
        }

        return $payment_methods[$name];
    }

    /**
     * @description Generate hash for a payment method from the current context
     *
     * @param array $payment_tab
     * @param bool $is_live
     *
     * @return string
     */
    public function getPaymentMethodHash($payment_tab = [], $is_live = true)
    {
        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentMethod::getPaymentMethodHash() - Invalid argument given, $payment_tab must be a non empty array.');

            return '';
        }

        if (!is_bool($is_live)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentMethod::getPaymentMethodHash() - Invalid argument given, $is_live must be a valid boolean.', 'error');

            return '';
        }

        return hash('sha256', $this->name . json_encode($payment_tab) . ($is_live ? 'live' : 'test'));
    }

    /**
     * @description Get payment option availability
     *
     * @return array
     */
    public function getPaymentOptionsAvailability()
    {
        $this->setParameters();

        $available_payment_methods = $this->getAvailablePaymentMethod();
        if (empty($available_payment_methods)) {
            return [];
        }

        $payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);
        if (empty($payment_methods)) {
            return [];
        }

        $options = [];
        foreach ($available_payment_methods as $available_payment_method) {
            $options[$available_payment_method] = isset($payment_methods[$available_payment_method])
                && (bool) $payment_methods[$available_payment_method];
        }

        return $options;
    }

    /**
     * @description Get collection of payment options
     *
     * @return array
     */
    public function getPaymentOptionCollection()
    {
        $this->setParameters();

        $options = $this->getPaymentOptionsAvailability();
        if (empty($options)) {
            return [];
        }

        $payment_options = [];
        foreach ($options as $payment_method => $enabled) {
            $allowed_feature = $this->dependencies->configClass->isValidFeature('feature_' . $payment_method);
            if ($enabled && $allowed_feature) {
                $obj = $this->getPaymentMethod($payment_method);
                if (is_object($obj)) {
                    $payment_options = $obj->getPaymentOption($payment_options);
                }
            }
        }

        return $payment_options;
    }

    /**
     * @description Get the current payment status
     *
     * @param object $resource
     *
     * @return array
     * @todo: add coverage to this method
     */
    public function getPaymentStatus($resource = null)
    {
        $this->setParameters();

        if (null == $resource) {
            $this->logger->addLog('PaymentMethod::getPaymentStatus() - Invalid argument given, $resource must be a non null object.');

            return [];
        }

        if ((bool) $resource->failure) {
            if ('aborted' == $resource->failure->code) {
                return [
                    'id_status' => 7,
                    'code' => 'cancelled',
                ];
            } elseif ('timeout' == $resource->failure->code) {
                return [
                    'id_status' => 11,
                    'code' => 'abandoned',
                ];
            }

            return [
                'id_status' => 3,
                'code' => 'failed',
            ];
        }

        if ((bool) $resource->is_refunded) {
            return [
                'id_status' => 5,
                'code' => 'refunded',
            ];
        }

        if (0 < (int) $resource->amount_refunded) {
            return [
                'id_status' => 4,
                'code' => 'partially_refunded',
            ];
        }

        // todo : we should use OneyPaymentMethod::getPaymentStatus() to defined this state
        if (isset($resource->payment_method['is_pending'])
            && (bool) $resource->payment_method['is_pending']) {
            return [
                'id_status' => 10,
                'code' => 'oney_pending',
            ];
        }

        if ((bool) $resource->is_paid) {
            return [
                'id_status' => 2,
                'code' => 'paid',
            ];
        }

        return [
            'id_status' => 1,
            'code' => 'not_paid',
        ];
    }

    /**
     * @description Get the payment tab required to generate a resource payment.
     *
     * @return array
     */
    public function getPaymentTab()
    {
        $this->setParameters();

        if (!is_string($this->name) || '' == $this->name) {
            $this->logger->addLog('PaymentMethod::getPaymentTab() - Invalid object prop, $name must be a non empty string.');

            return [];
        }
        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->cart)) {
            $this->logger->addLog('PaymentMethod::getPaymentTab() - Context Cart object must be a valid object.');

            return [];
        }

        $valid_customer = $this->validate_adapter->validate('isLoadedObject', $this->context->customer);

        // Check currency
        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->currency)) {
            $this->logger->addLog('PaymentMethod::getPaymentTab() - Context Currency object must be a valid object.');

            return [];
        }

        $supported_currencies = explode(';', $this->configuration->getValue('currencies'));
        if (!in_array($this->context->currency->iso_code, $supported_currencies, true)) {
            $this->logger->addLog('PaymentMethod::getPaymentTab() - Context Currency object is not supported.');

            return [];
        }

        // Check amount
        $payplug_amounts = json_decode($this->configuration->getValue('amounts'), true);
        $price_limit = isset($payplug_amounts[$this->name]) ? $payplug_amounts[$this->name] : $payplug_amounts['default'];
        $cart_amount = $this->context->cart->getOrderTotal(true);
        $is_valid_amount = $this->dependencies
            ->getHelpers()['amount']
            ->validateAmount($price_limit, (float) $cart_amount);
        if (!$is_valid_amount['result']) {
            $this->logger->addLog('PaymentMethod::getPaymentTab() - Current Context Cart amount is not compatible with payment method.');

            return [];
        }

        $payment_tab = [
            'amount' => $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($cart_amount),
            'currency' => $this->context->currency->iso_code,
            'notification_url' => $this->context->link->getModuleLink($this->dependencies->name, 'ipn', [], true),
            'force_3ds' => false,
            'hosted_payment' => [
                'return_url' => $this->context->link->getModuleLink(
                    $this->dependencies->name,
                    'validation',
                    ['ps' => 1, 'cartid' => (int) $this->context->cart->id],
                    true
                ),
                'cancel_url' => $this->context->link->getModuleLink(
                    $this->dependencies->name,
                    'validation',
                    ['ps' => 2, 'cartid' => (int) $this->context->cart->id],
                    true
                ),
            ],
            'metadata' => [
                'ID Client' => (bool) $valid_customer ? (int) $this->context->customer->id : 0,
                'ID Cart' => (int) $this->context->cart->id,
                'Website' => $this->tools->tool('getShopDomainSsl', true, false),
            ],
            'allow_save_card' => false,
        ];

        // Set addresses if user is logged and has delivery/billing addresses
        if ((bool) $valid_customer
            && (bool) $this->context->cart->id_address_delivery) {
            $billing_address = $this->dependencies
                ->getPlugin()
                ->getAddress()
                ->get((int) $this->context->cart->id_address_invoice);
            $billing_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $billing_address->id_country);
            $shipping_address = $this->dependencies
                ->getPlugin()
                ->getAddress()
                ->get((int) $this->context->cart->id_address_delivery);
            $shipping_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $shipping_address->id_country);

            if (!$shipping_iso || !$billing_iso) {
                $default_language = $this->dependencies
                    ->getPlugin()
                    ->getLanguage()
                    ->get(
                        (int) $this->dependencies
                            ->getPlugin()
                            ->getConfiguration()
                            ->get('PS_LANG_DEFAULT')
                    );
                $iso_code_list = $this->dependencies
                    ->getPlugin()
                    ->getCountryClass()
                    ->getIsoCodeList();
                if (in_array($this->tools->tool('strtoupper', $default_language->iso_code), $iso_code_list, true)) {
                    $iso_code = $this->tools->tool('strtoupper', $default_language->iso_code);
                } else {
                    $iso_code = 'FR';
                }
                if (!$shipping_iso) {
                    $metadata['cms_shipping_country'] = $this
                        ->dependencies
                        ->configClass
                        ->getIsoCodeByCountryId((int) $shipping_address->id_country);
                    $shipping_iso = $iso_code;
                }
                if (!$billing_iso) {
                    $metadata['cms_billing_country'] = $this
                        ->dependencies
                        ->configClass
                        ->getIsoCodeByCountryId((int) $billing_address->id_country);
                    $billing_iso = $iso_code;
                }
            }

            // Set billing informations
            $billing = [
                'title' => null,
                'first_name' => !empty($billing_address->firstname) ? $billing_address->firstname : null,
                'last_name' => !empty($billing_address->lastname) ? $billing_address->lastname : null,
                'company_name' => !empty($billing_address->company) ? trim($billing_address->company) : null,
                'email' => $this->context->customer->email,
                'landline_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                    $billing_address->phone,
                    $billing_address->id_country
                ),
                'mobile_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                    $billing_address->phone_mobile,
                    $billing_address->id_country
                ),
                'address1' => !empty($billing_address->address1) ? $billing_address->address1 : null,
                'address2' => !empty($billing_address->address2) ? $billing_address->address2 : null,
                'postcode' => !empty($billing_address->postcode) ? $billing_address->postcode : null,
                'city' => !empty($billing_address->city) ? $billing_address->city : null,
                'country' => $billing_iso,
                'language' => $this->dependencies->configClass->getIsoFromLanguageCode($this->context->language),
            ];
            $billing['company_name'] = empty($billing['company_name']) || !is_string($billing['company_name'])
                ? $billing['first_name'] . ' ' . $billing['last_name']
                : $billing['company_name'];
            $billing['landline_phone_number'] = $billing['landline_phone_number'] ?: null;
            $billing['mobile_phone_number'] = $billing['mobile_phone_number'] ?: $billing['landline_phone_number'];

            // Set shipping informations
            $delivery_type = 'NEW';
            if ($this->context->cart->id_address_delivery == $this->context->cart->id_address_invoice) {
                $delivery_type = 'BILLING';
            } elseif ($shipping_address->isUsed()) {
                $delivery_type = 'VERIFIED';
            }
            $shipping = [
                'title' => null,
                'first_name' => !empty($shipping_address->firstname) ? $shipping_address->firstname : null,
                'last_name' => !empty($shipping_address->lastname) ? $shipping_address->lastname : null,
                'company_name' => !empty($shipping_address->company) ? trim($shipping_address->company) : null,
                'email' => $this->context->customer->email,
                'landline_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                    $shipping_address->phone,
                    $shipping_address->id_country
                ),
                'mobile_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                    $shipping_address->phone_mobile,
                    $shipping_address->id_country
                ),
                'address1' => !empty($shipping_address->address1) ? $shipping_address->address1 : null,
                'address2' => !empty($shipping_address->address2) ? $shipping_address->address2 : null,
                'postcode' => !empty($shipping_address->postcode) ? $shipping_address->postcode : null,
                'city' => !empty($shipping_address->city) ? $shipping_address->city : null,
                'country' => $shipping_iso,
                'language' => $this->dependencies->configClass->getIsoFromLanguageCode($this->context->language),
                'delivery_type' => $delivery_type,
            ];
            $shipping['company_name'] = empty($shipping['company_name']) || !is_string($shipping['company_name'])
                ? $shipping['first_name'] . ' ' . $shipping['last_name']
                : $shipping['company_name'];
            $shipping['landline_phone_number'] = $shipping['landline_phone_number'] ?: null;
            $shipping['mobile_phone_number'] = $shipping['mobile_phone_number'] ?: $shipping['landline_phone_number'];

            $payment_tab['shipping'] = $shipping;
            $payment_tab['billing'] = $billing;
        }

        return $payment_tab;
    }

    /**
     * @description Get refundable amount for a given resource id.
     *
     * @param string $resource_id
     *
     * @return int
     */
    public function getRefundableAmount($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::getRefundableAmount - Invalid argument, $resource_id must be a non empty string.', 'error');

            return 0;
        }

        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getRefundableAmount - Can\'t retrieve the resource for the given id', 'error');

            return 0;
        }

        $resource = $retrieve['resource'];

        return $resource->amount - $resource->amount_refunded;
    }

    /**
     * @description Get refunded amount for a given resource id.
     *
     * @param string $resource_id
     *
     * @return int
     */
    public function getRefundedAmount($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::getRefundedAmount - Invalid argument, $resource_id must be a non empty string.', 'error');

            return 0;
        }

        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getRefundedAmount - Can\'t retrieve the resource for the given id', 'error');

            return 0;
        }

        return $retrieve['resource']->amount_refunded;
    }

    /**
     * @description Get the resource detail for order admin display
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
            $this->logger->addLog('PaymentMethod::getResourceDetail - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [];
        }
        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Payment resource can\'t be retrieved.', 'error');

            return [];
        }

        $resource = $retrieve['resource'];

        $status = $this->getPaymentStatus($resource);
        if (empty($status)) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Cannot define the resource status', 'error');

            return [];
        }

        // If status is refunded but order state is paid then update the current state
        if ('refunded' == $status['code']) {
            $order_id = $this->tools->tool('getValue', 'id_order');
            $this->updateOrderStateFromPaidToRefund((int) $order_id, (bool) $resource->is_live);
        }

        // Define status translation
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOrderTranslations();
        $pay_status = $translation['detail']['status'][$status['code']];

        // Define class for templating
        switch ($status['id_status']) {
            case 1: // not paid
            case 5: // refunded
            case 8: // authorized
            case 11: // abandoned
                $status_class = 'pp_warning';

                break;

            case 2: // paid
                $status_class = 'pp_success';

                break;

            case 3: // failed
            case 7: // cancelled
            case 9: // authorization expired
                $status_class = 'pp_error';

                break;

            case 4: // partially refunded
            case 6: // on going
                // todo: this case should be treated in OneyPaymentMethod::getResourceDetail()
            case 10: // oney pending
                $status_class = 'pp_neutral';

                break;

            default:
                $status_class = 'pp_other';

                break;
        }

        // Get card details to order details (views/templates/admin/order/details.tpl)
        // Mask (last4), exp date...
        $card_details = [];
        if (isset($resource->card->last4) && (!empty($resource->card->last4))) {
            $card_details = $this->dependencies
                ->getPlugin()
                ->getCardAction()
                ->renderOrderDetail($resource);
        }

        // Card brand
        $card_brand = null;
        if ($card_details
            && isset($card_details['brand'])
            && !empty($card_details['brand'])
            && ('none' !== $card_details['brand'])) {
            $card_brand = $translation['detail']['card'] . ' ' . $card_details['brand'];
        }

        // Card Country
        $card_country = null;
        if ($card_details
            && isset($card_details['country'])
            && ('none' !== $card_details['country'])) {
            $card_country = $card_details['country'];
            $card_brand .= ' (' . $card_details['country'] . ')';
        }

        // Card mask
        $card_mask = null;
        if ($card_details && isset($card_details['last4']) && !empty($card_details['last4'])) {
            $card_mask = '**** **** **** ' . $card_details['last4'];
        }

        // Card exp. date
        $card_date = null;
        if ($card_details && (isset($card_details['exp_month']) && !empty($card_details['exp_month']))
            && (isset($card_details['exp_year']) && !empty($card_details['exp_year']))) {
            $card_date = $card_details['exp_month'] . '/' . $card_details['exp_year'];
        }

        $type = isset($resource->payment_method, $resource->payment_method['type'])
            ? $resource->payment_method['type']
            : '';

        $amount_available = $resource->amount - $resource->amount_refunded;
        $refund = [
            'refunded' => $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($resource->amount_refunded, true),
            'available' => 10 <= $amount_available
                ? $this->dependencies->getHelpers()['amount']->convertAmount($amount_available, true)
                : 0,
            'is_refunded' => (bool) $resource->is_refunded,
        ];
        $can_be_refund = $resource->is_paid
            && !$resource->is_refunded
            && \time() <= $resource->refundable_until;
        if (!(bool) $can_be_refund) {
            $refund['available'] = 0;
        }

        return [
            'id' => $resource->id,
            'status' => $pay_status,
            'status_code' => $status['code'],
            'status_class' => $status_class,
            'amount' => $this->dependencies->getHelpers()['amount']->convertAmount($resource->amount, true),
            'card_brand' => $card_brand,
            'card_mask' => $card_mask,
            'card_date' => $card_date,
            'card_country' => $card_country,
            'mode' => $resource->is_live ? $translation['detail']['mode']['live'] : $translation['detail']['mode']['test'],
            'paid' => (bool) $resource->is_paid,
            'authorization' => false,
            'date' => date('d/m/Y', $resource->created_at),
            'error' => $resource->failure ? '(' . $resource->failure->message . ')' : '',
            'tds' => null !== $resource->is_3ds ? $translation['detail'][$resource->is_3ds ? 'yes' : 'no'] : false,
            'type' => $type,
            'type_code' => $type,
            'refund' => $refund,
            'currency' => $resource->currency,
        ];
    }

    /**
     * @description Generate and return correct resource return url
     *
     * @return array
     */
    public function getReturnUrl()
    {
        $this->setParameters();
        if (!is_string($this->name) || '' == $this->name) {
            $this->logger->addLog('PaymentMethod::getReturnUrl() - Context Cart object must be a valid object.');

            return [];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $this->context->cart->id);
        if (!$stored_resource) {
            $this->logger->addLog('PaymentMethod::getReturnUrl() - No stored resource retrieve for current context cart id.');

            return [];
        }

        $retrieve = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method'])
            ->retrieve($stored_resource['resource_id']);

        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getReturnUrl() - Payment resource can\'t be retrieved for stored resource id.');

            return [];
        }

        $resource = $retrieve['resource'];
        $return_url = $resource->hosted_payment
            ? $resource->hosted_payment->payment_url
                ?: $resource->hosted_payment->return_url
            : '';

        return [
            'return_url' => $return_url,
            'resource_stored' => $stored_resource,
        ];
    }

    /**
     * @description Check if the stored resource is a non expired resource with no failure
     *
     * @return bool
     */
    public function isValidResource()
    {
        $this->setParameters();

        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->cart)) {
            $this->logger->addLog('PaymentMethod::isValidResource() - Context Cart object must be a valid object.');

            return false;
        }
        $id_cart = (int) $this->context->cart->id;

        // Get the resource from context cart id
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('id_cart', (int) $id_cart);
        if (empty($stored_resource)) {
            $this->logger->addLog('PaymentMethod::isValidResource() - No stored resource retrieve for current context cart id.');

            return false;
        }

        // Check if resource is expired
        $is_expired = $this->dependencies
            ->getValidators()['payment']
            ->isTimeoutCachedPayment($stored_resource['date_upd'])['result'];
        if (!$is_expired) {
            $this->logger->addLog('PaymentMethod::isValidResource() - Current resource is expired.');

            return false;
        }

        // Get the resource from API
        $retrieve = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod($stored_resource['method'])
            ->retrieve($stored_resource['resource_id']);

        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::isValidResource() - Payment resource can\'t be retrieved for stored resource id.');

            return false;
        }

        // Check if retrieved resource has failure
        if (isset($retrieve['resource']->failure->code) && $retrieve['resource']->failure->code) {
            $this->logger->addLog('PaymentMethod::isValidResource() - Retrieved Payment has failure');

            return false;
        }

        return true;
    }

    /**
     * @description Post process a given order from a resource retrieve
     *
     * @param array $retrieve
     * @param int $id_order
     *
     * @return bool
     */
    public function postProcessOrder($retrieve = [], $id_order = 0)
    {
        $this->setParameters();

        if (!is_array($retrieve) || empty($retrieve)) {
            $this->logger->addLog('PaymentMethod::postProcessOrder() - Invalid argument given, $retrieve must be a non empty array.');

            return false;
        }

        $resource = $retrieve['resource'];

        if (!is_object($resource) || null == $resource) {
            $this->logger->addLog('PaymentMethod::postProcessOrder() - Invalid argument given, $resource must be a non null object.');

            return false;
        }

        if (!is_int($id_order) || !$id_order) {
            $this->logger->addLog('PaymentMethod::postProcessOrder() - Invalid argument given, $id_order must be a non null integer.');

            return false;
        }

        $order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->get((int) $id_order);
        if (!$this->validate_adapter->validate('isLoadedObject', $order)) {
            $this->logger->addLog('PaymentMethod::postProcessOrder() - Retrieve Order object is not valid');

            return false;
        }

        $data = [];
        $data['metadata'] = $resource->metadata;
        $data['metadata']['Order'] = (int) $order->id;
        $data['metadata']['OrderRef'] = $order->reference;

        $patchPayment = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->patchPayment($resource->id, $data);
        if (!$patchPayment['result']) {
            $this->logger->addLog('PaymentMethod::postProcessOrder() - Payment resource can not be patch for given datas');

            return false;
        }

        return true;
    }

    /**
     * @description Reset the permission from current permission
     *
     * @param array $permissions
     */
    public function resetPaymentMethodFromPermission($permissions = [])
    {
        $this->setParameters();

        if (!is_array($permissions)) {
            $this->logger->addLog('PaymentMethod::resetPaymentMethodFromPermission: Invalid parameter given, $permissions must be an array.');

            return false;
        }

        $payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);
        foreach ($payment_methods as $payment_method => $active) {
            if ($active
                && isset($permissions[$payment_method])
                && !$permissions[$payment_method]
            ) {
                $payment_methods[$payment_method] = false;
            }
        }

        return $this->configuration->set('payment_methods', json_encode($payment_methods));
    }

    /**
     * @description Create resource from a given tab
     *
     * @param array $payment_tab
     *
     * @return array
     */
    public function saveResource($payment_tab = [])
    {
        $this->setParameters();

        if (!is_string($this->name)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentMethod::saveResource - Invalid argument, the method name must be defined.', 'error');

            return [
                'result' => false,
            ];
        }
        if (!is_array($payment_tab) || empty($payment_tab)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('PaymentMethod::saveResource - Invalid argument, $payment_tab must be a non empty array.', 'error');

            return [
                'result' => false,
            ];
        }

        $payment = $this->api_service->createPayment($payment_tab);

        // If the payment resource can\'t be created due to to bad permission, we update the feature activation
        if (403 == (int) $payment['code']) {
            $cart = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get()->cart;
            $permissions = $this->dependencies->configClass->getAvailableOptions($cart);
            $this->resetPaymentMethodFromPermission($permissions);
        }

        // If the payment resource can\'t be created due to bad credential, we log out the merchand
        if (401 == (int) $payment['code']) {
            $this->dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->logoutAction();
        }

        return $payment;
    }

    /**
     * @description Set object property
     *
     * @param string $key
     * @param null $value
     *
     * @return $this
     */
    public function set($key = '', $value = null)
    {
        if (!is_string($key) || !$key) {
            $this->logger->addLog('PaymentMethod::set - Can\'t load option the name is missing.');

            return $this;
        }

        if (is_null($value)) {
            throw new BadParameterException('Invalid argument, $value must be a non null');
        }
        $this->{$key} = $value;

        return $this;
    }

    /**
     * @description Refund the resource for a given resource id and amount
     *
     * @param string $resource_id
     * @param int $amount
     * @param array $metadata
     *
     * @return array
     */
    public function refund($resource_id = '', $amount = 0, $metadata = [])
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::refund - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        if (!is_numeric($amount) || !$amount) {
            $this->logger->addLog('PaymentMethod::refund - Invalid argument, $amount must be a non null integer.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $amount must be a non null integer.',
            ];
        }

        if (!is_array($metadata) || empty($metadata)) {
            $this->logger->addLog('PaymentMethod::refund - Invalid argument, $metadata must be a non empty array.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $metadata must be a non empty array.',
            ];
        }

        // Retrieve the resource
        $retrieve = $this->retrieve($resource_id);
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::refund - The resource can\'t be retrieve.', 'error');

            return $retrieve;
        }

        $resource = $retrieve['resource'];

        // then check if the refund is paid
        if (!$resource->is_paid) {
            $this->logger->addLog('PaymentMethod::refund - Payment resource is not paid.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Payment resource is not paid.',
            ];
        }

        // then check if the refund is already refund
        if ($resource->is_refunded) {
            $this->logger->addLog('PaymentMethod::refund - Payment resource is fully refund.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Payment resource is fully refund.',
            ];
        }

        // then got the truly refundable amount
        $refundable_amount = (int) ($resource->amount - $resource->amount_refunded);
        $truly_refundable_amount = min($amount, $refundable_amount);

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $is_live = !(bool) $configuration->getValue('sandbox_mode');
        $api_service = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api');

        if ($resource->is_live != $is_live) {
            $api_service->initialize((bool) $resource->is_live);
        }

        // then we do the refund of the resource
        $refund = $api_service->refundPayment(
            $resource_id,
            [
                'amount' => $truly_refundable_amount,
                'metadata' => $metadata,
            ]
        );

        // then we reset the initial mode from configuration
        if ($resource->is_live != $is_live) {
            $api_service->initialize((bool) $is_live);
        }

        return $refund;
    }

    /**
     * @description Retrieve the resource for a given resource id.
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function retrieve($resource_id = '')
    {
        $this->setParameters();

        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::retrieve - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $resource_id);
        if (!$stored_resource) {
            $this->logger->addLog('PaymentMethod::retrieve - Can\'t find stored payment from given resource id.', 'error');

            return [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t find stored payment from given resource id',
            ];
        }

        // We retrieve the payment from the stored payment configuration
        $is_live = isset($stored_resource['is_live']) && (bool) $stored_resource['is_live'];
        $this->api_service->initialize((bool) $is_live);

        $is_installment = 'installment' == $stored_resource['method'];
        $retrieve = $is_installment
            ? $this->api_service->retrieveInstallment($resource_id)
            : $this->api_service->retrievePayment($resource_id);

        // If we don't find the payment, for retrocompatibility we switch the mode then try again
        // This section could be removed for highter module version
        if (!$retrieve['result']) {
            $this->api_service->initialize(!(bool) $is_live);
            $retrieve = $is_installment
                ? $this->api_service->retrieveInstallment($resource_id)
                : $this->api_service->retrievePayment($resource_id);
        }

        // Then retrieve the current mode from configuration
        if (!$is_installment) {
            $is_live = !(bool) $this->configuration->getValue('sandbox_mode');
            if ($stored_resource['is_live'] != $is_live) {
                $this->api_service->initialize((bool) $is_live);
            }
        }

        return $is_installment
            ? $this
                ->getPaymentMethod($stored_resource['method'])
                ->retrieveSchedules($retrieve)
            : $retrieve;
    }

    /**
     * @description Update the order state if current state is refunded
     *
     * todo: add coverage to this method
     *
     * @param int $order_id
     * @param bool $is_live
     *
     * @return bool
     */
    protected function updateOrderStateFromPaidToRefund($order_id = 0, $is_live = true)
    {
        $this->setParameters();

        if (!is_int($order_id) || !$order_id) {
            $this->logger->addLog('PaymentMethod::updateOrderState() - Invalid argument given, $order_id must be a non null integer.', 'error');

            return false;
        }

        if (!is_bool($is_live)) {
            $this->logger->addLog('PaymentMethod::updateOrderState() - Invalid argument given, $is_live must be a valid boolean.', 'error');

            return false;
        }

        $order = $this->dependencies->getPlugin()
            ->getOrder()
            ->get((int) $order_id);

        if (!$this->validate_adapter->validate('isLoadedObject', $order)) {
            $this->logger->addLog('PaymentMethod::updateOrderState() - Invalid argument given, $order got must be a valid object.', 'error');

            return false;
        }

        $state_addons = $is_live ? '' : '_test';
        $paid_os = $this->configuration->getValue('order_state_paid' . $state_addons);

        if ($order->getCurrentState() != $paid_os) {
            return true;
        }

        $refund_os = $this->configuration->getValue('order_state_refund' . $state_addons);
        $update_order_history = $this->dependencies
            ->getPlugin()
            ->getOrderClass()
            ->updateOrderState($order, (int) $refund_os);

        // If order history well update, then we force the reload
        if (!$update_order_history) {
            $this->logger->addLog('PaymentMethod::updateOrderState() - Failed to update order state', 'error');

            return false;
        }

        $parameters = ['vieworder' => 1, 'id_order' => (int) $order->id];
        $link_order = $this->context->link->getAdminLink('AdminOrders', true, [], $parameters);

        return $this->tools->tool('redirectAdmin', $link_order);
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
        if (!is_string($this->name) || '' == $this->name) {
            $this->logger->addLog('PaymentMethod::getPaymentOption: Can\'t load option the name is missing.');

            return [];
        }

        $payplug_countries = json_decode($this->configuration->getValue('countries'), true);

        if (isset($payplug_countries[$this->name])) {
            $address = $this->dependencies
                ->getPlugin()
                ->getAddress()
                ->get((int) $this->context->cart->id_address_invoice);
            $iso = $this->dependencies
                ->configClass
                ->getIsoCodeByCountryId((int) $address->id_country);

            if (!$this->dependencies
                ->getValidators()['payment']
                ->isAllowedCountry(implode(',', $payplug_countries[$this->name]), $iso)['result']) {
                return $payment_options;
            }
        }

        $payplug_amounts = json_decode($this->configuration->getValue('amounts'), true);
        $price_limit = isset($payplug_amounts[$this->name]) ? $payplug_amounts[$this->name] : $payplug_amounts['default'];
        $cart_amount = $this->context->cart->getOrderTotal(true);
        if (false === strpos($this->name, 'oney')) {
            if (!$this->dependencies
                ->getHelpers()['amount']
                ->validateAmount($price_limit, (float) $cart_amount)['result']) {
                return $payment_options;
            }
        }

        $payment_options[$this->name] = [
            'name' => $this->name,
            'inputs' => [
                'pc' => [
                    'name' => 'pc',
                    'type' => 'hidden',
                    'value' => 'new_card',
                ],
                'pay' => [
                    'name' => 'pay',
                    'type' => 'hidden',
                    'value' => '1',
                ],
                'id_cart' => [
                    'name' => 'id_cart',
                    'type' => 'hidden',
                    'value' => (int) $this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => $this->name,
                ],
            ],
            'extra_classes' => $this->name,
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => $this->name]
            ),
            'logo' => $this->img_path . 'svg/checkout/' . $this->name . '.svg',
            'callToActionText' => isset($this->translation[$this->name]['call_to_action'])
                ? $this->translation[$this->name]['call_to_action']
                : '',
            'action' => $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', [], true),
            'moduleName' => $this->dependencies->name,
        ];

        return $payment_options;
    }

    /**
     * @description Set parameters for usage
     */
    protected function setParameters()
    {
        if (null == $this->api_service) {
            $this->api_service = $this->dependencies
                ->getPlugin()
                ->getModule()
                ->getInstanceByName($this->dependencies->name)
                ->getService('payplug.utilities.service.api');
        }
        if (null == $this->configuration) {
            $this->configuration = $this->dependencies
                ->getPlugin()
                ->getConfigurationClass();
        }
        if (null == $this->configuration_adapter) {
            $this->configuration_adapter = $this->dependencies
                ->getPlugin()
                ->getConfiguration();
        }
        if (null == $this->context) {
            $this->context = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get();
        }
        if (null == $this->country_adapter) {
            $this->country_adapter = $this->dependencies->getPlugin()->getCountry();
        }
        if (null == $this->currency_adapter) {
            $this->currency_adapter = $this->dependencies
                ->getPlugin()
                ->getCurrency();
        }
        if (!is_string($this->iso_code)) {
            $this->iso_code = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get()->language->iso_code;
        }
        if (empty($this->external_url)) {
            $this->external_url = $this->dependencies
                ->getPlugin()
                ->getRoutes()
                ->getExternalUrl($this->iso_code);
        }
        if (!is_string($this->img_path)) {
            $this->img_path = $this->dependencies
                ->getPlugin()
                ->getConstant()
                ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/';
        }
        if (null == $this->link) {
            $this->link = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get()->link;
        }
        if (null == $this->logger) {
            $this->logger = $this->dependencies
                ->getPlugin()
                ->getLogger();
        }
        if (null == $this->tools) {
            $this->tools = $this->dependencies
                ->getPlugin()
                ->getTools();
        }
        if (empty($this->translation)) {
            $this->translation = $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->getPaymentMethodsTranslations();
        }
        if (null == $this->validate_adapter) {
            $this->validate_adapter = $this->dependencies
                ->getPlugin()
                ->getValidate();
        }
    }
}
