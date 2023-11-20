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
    public $force_resource = true;

    /** @var bool */
    public $refundable;

    /** @var object */
    protected $configuration;

    /** @var object */
    protected $context;

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
            'giropay',
            'sofort',
            'ideal',
            'oney',
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

        if (!isset($this->name) || !$this->name) {
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
     * @description Get order tab for given resource
     *
     * @param null $resource
     *
     * @return array
     */
    public function getOrderTab($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            // todo: add error log
            return [];
        }

        $amount = $this->dependencies
            ->getHelpers()['amount']
            ->convertAmount($resource->amount, true);

        $state_addons = $resource->is_live ? '' : '_test';
        $order_state = $this->configuration->getValue('order_state_paid' . $state_addons);

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
     * @return string
     */
    public function getPaymentMethodHash()
    {
        $this->setParameters();
        $cartToHash = [];
        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->cart)) {
            // todo: add error log
            return '';
        }

        $products = $this->context->cart->getProducts();
        if (!$products) {
            // todo: add error log
            return '';
        }

        foreach ($products as $product) {
            $product = array_map('json_encode', $product);
            $cartToHash[] = array_map('strval', $product);
        }

        // Adding cart informationObjectModel
        $cartToHash[] = 'Cart $id_address_delivery: ' . $this->context->cart->id_address_delivery;
        $cartToHash[] = 'Cart $id_address_invoice: ' . $this->context->cart->id_address_invoice;
        $cartToHash[] = 'Cart $id_currency: ' . $this->context->cart->id_currency;
        $cartToHash[] = 'Cart $id_customer: ' . $this->context->cart->id_customer;
        $cartToHash[] = 'Cart $delivery_option: ' . $this->context->cart->delivery_option;

        // Adding cart amount to hash
        $cartToHash[] = 'Cart amount: ' . (float) $this->context->cart->getOrderTotal(true);

        return hash('sha256', $this->name . json_encode($cartToHash));
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
     * @description Get the payment tab required to generate a resource payment.
     *
     * @return array
     */
    public function getPaymentTab()
    {
        $this->setParameters();

        if (!isset($this->name) || !$this->name) {
            // todo: add error log
            return [];
        }
        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->cart)) {
            // todo: add error log
            return [];
        }
        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->customer)) {
            // todo: add error log
            return [];
        }

        // Check currency
        if (!$this->validate_adapter->validate('isLoadedObject', $this->context->currency)) {
            // todo: add error log
            return [];
        }

        $supported_currencies = explode(';', $this->configuration->getValue('currencies'));
        if (!in_array($this->context->currency->iso_code, $supported_currencies, true)) {
            // todo: add error log
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
            // todo: add error log
            return [];
        }

        // Set addresses
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
                ->getHelpers()['country']::getIsoCodeList();
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
        $billing['company_name'] = empty($billing['company_name']) || !$billing['company_name']
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
        $shipping['company_name'] = empty($shipping['company_name']) || !$shipping['company_name']
            ? $shipping['first_name'] . ' ' . $shipping['last_name']
            : $shipping['company_name'];
        $shipping['landline_phone_number'] = $shipping['landline_phone_number'] ?: null;
        $shipping['mobile_phone_number'] = $shipping['mobile_phone_number'] ?: $shipping['landline_phone_number'];

        return [
            'amount' => $this->dependencies
                ->getHelpers()['amount']
                ->convertAmount($cart_amount),
            'currency' => $this->context->currency->iso_code,
            'shipping' => $shipping,
            'billing' => $billing,
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
                'ID Client' => (int) $this->context->customer->id,
                'ID Cart' => (int) $this->context->cart->id,
                'Website' => $this->tools->tool('getShopDomainSsl', true, false),
            ],
            'allow_save_card' => false,
        ];
    }

    // todo: add coverage to this method
    public function getResourceDetail($resource_id = '')
    {
        $this->setParameters();
        if (!is_string($resource_id) || !$resource_id) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Invalid argument, $resource_id must be a non empty string.', 'error');

            return [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ];
        }

        $sandbox = (bool) $this->configuration->getValue('sandbox_mode');
        $retrieve = $this->dependencies->apiClass->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            if ($sandbox) {
                $this->dependencies->apiClass->setSecretKey((string) $this->configuration->getValue('live_api_key'));
                $retrieve = $this->dependencies->apiClass->retrievePayment($resource_id);
            } else {
                $this->dependencies->apiClass->setSecretKey((string) $this->configuration->getValue('test_api_key'));
                $retrieve = $this->dependencies->apiClass->retrievePayment($resource_id);
            }
        }
        if (!$retrieve['result']) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Cannot retrieve the resource.', 'error');

            return [];
        }

        $resource = $retrieve['resource'];

        $status = $this->getPaymentStatus($resource);
        if (empty($status)) {
            $this->logger->addLog('PaymentMethod::getResourceDetail - Cannot define the resource status', 'error');

            return [];
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
        if (!isset($this->name) || !$this->name) {
            // todo: add error log
            return [];
        }

        $resource_stored = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $this->context->cart->id);
        if (!$resource_stored) {
            // todo: add error log
            return [];
        }

        $resource = $this->dependencies->apiClass->retrievePayment($resource_stored['resource_id']);
        if (!$resource['result']) {
            // todo: add error log
            return [];
        }

        $resource = $resource['resource'];
        $return_url = $resource->hosted_payment
            ? $resource->hosted_payment->payment_url
                ?: $resource->hosted_payment->return_url
            : '';

        return [
            'return_url' => $return_url,
            'resource_stored' => $resource_stored,
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
            // todo: Add error log
            return false;
        }
        $id_cart = (int) $this->context->cart->id;

        // Get the resource from context cart id
        $stored_resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $id_cart);
        if (empty($stored_resource)) {
            // todo: Add error log
            return false;
        }

        // Check if resource is expired
        $is_expired = $this->dependencies
            ->getValidators()['payment']
            ->isTimeoutCachedPayment($stored_resource['date_upd'])['result'];
        if (!$is_expired) {
            // todo: Add error log
            return false;
        }

        // Get the resource from API
        $retrieved_resource = $this->dependencies->apiClass->retrievePayment($stored_resource['resource_id']);
        if (!$retrieved_resource['result']) {
            // todo: Add error log
            return false;
        }

        // Check if retrieved resource has failure
        if (isset($retrieved_resource['resource']->failure->code) && $retrieved_resource['resource']->failure->code) {
            // todo: Add error log
            return false;
        }

        return true;
    }

    // todo: add coverage to this method
    public function postProcessOrder($resource = null, $order = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            // todo: add error log
            return false;
        }

        if (!is_object($order) || !$order) {
            // todo: add error log
            return false;
        }

        $data = [];
        $data['metadata'] = $resource->metadata;
        $data['metadata']['Order'] = $order->id;

        $patchPayment = $this->dependencies->apiClass->patchPayment($resource->id, $data);
        if (!$patchPayment['result']) {
            // todo: add error log
            return false;
        }

        $create_order_payment = $this->dependencies
            ->getPlugin()
            ->getOrderPaymentRepository()
            ->createOrderPayment([
                'id_order' => (int) $order->id,
                'id_payment' => $resource->id,
            ]);
        if (!$create_order_payment) {
            // todo: add error log
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

        if (!isset($this->name) || !$this->name) {
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

        $payment = $this->dependencies->apiClass->createPayment($payment_tab);

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
            $this->logger->addLog('PaymentMethod::getPaymentMethod: Can\'t load option the name is missing.');

            return $this;
        }

        if (is_null($value)) {
            throw new BadParameterException('Invalid argument, $value must be a non null');
        }
        $this->{$key} = $value;

        return $this;
    }

    // todo: add coverage to this method
    public function getPaymentStatus($resource = null)
    {
        $this->setParameters();

        if (!is_object($resource) || !$resource) {
            // todo: add error log
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
        if (!isset($this->name) || !$this->name) {
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
        if (!$this->configuration) {
            $this->configuration = $this->dependencies
                ->getPlugin()
                ->getConfigurationClass();
        }
        if (!$this->context) {
            $this->context = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get();
        }
        if (!$this->iso_code) {
            $this->iso_code = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get()->language->iso_code;
        }
        if (!$this->external_url) {
            $this->external_url = $this->dependencies
                ->getPlugin()
                ->getRoutes()
                ->getExternalUrl($this->iso_code);
        }
        if (!$this->img_path) {
            $this->img_path = $this->dependencies
                ->getPlugin()
                ->getConstant()
                ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/';
        }
        if (!$this->link) {
            $this->link = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get()->link;
        }
        if (!$this->logger) {
            $this->logger = $this->dependencies
                ->getPlugin()
                ->getLogger();
        }
        if (!$this->tools) {
            $this->tools = $this->dependencies
                ->getPlugin()
                ->getTools();
        }
        if (!$this->translation) {
            $this->translation = $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->getPaymentMethodsTranslations();
        }
        if (!$this->validate_adapter) {
            $this->validate_adapter = $this->dependencies
                ->getPlugin()
                ->getValidate();
        }
    }
}
