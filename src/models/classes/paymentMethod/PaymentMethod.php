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

use PayPlug\src\exceptions\BadParameterException;

class PaymentMethod
{
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
    /** @var array */
    protected $translation;

    /** @var string */
    protected $name;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
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
            $this->logger->addLog('PaymentMethod::getOption: Can not load option the name is missing.');

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
            $this->logger->addLog('PaymentMethod::getPaymentMethod: Can not load option the name is missing.');

            return [];
        }

        $payment_methods = $this->getAvailablePaymentMethodsObject();
        if (!array_key_exists($name, $payment_methods)) {
            $this->logger->addLog('PaymentMethod::getPaymentMethod: Can not load option the name is missing.');

            return [];
        }

        return $payment_methods[$name];
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
     * @description Set object property
     *
     * @param string $key
     * @param null   $value
     *
     * @throws BadParameterException
     *
     * @return $this
     */
    public function set($key = '', $value = null)
    {
        if (!is_string($key) || !$key) {
            $this->logger->addLog('PaymentMethod::getPaymentMethod: Can not load option the name is missing.');

            return $this;
        }

        if (is_null($value)) {
            throw (new BadParameterException('Invalid argument, $value must be a non null'));
        }
        $this->{$key} = $value;

        return $this;
    }

    /**
     * @description Reset the permission from current permission
     *
     * @param array $permissions
     */
    public function resetPaymentMethodFromPermission($permissions = [])
    {
        $this->setParameters();
        $payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);

        foreach ($payment_methods as $payment_method => $active) {
            if ($active
                && isset($permissions[$payment_method])
                && !$permissions[$payment_method]
            ) {
                $payment_methods[$payment_method] = false;
            }
        }

        $this->configuration->set('payment_methods', json_encode($payment_methods));
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
        if (!$this->translation) {
            $this->translation = $this->dependencies
                ->getPlugin()
                ->getTranslation()
                ->getPaymentMethodsTranslations();
        }
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
            $this->logger->addLog('PaymentMethod::getPaymentOption: Can not load option the name is missing.');

            return [];
        }

        $payplug_countries = json_decode($this->configuration->getValue('countries'), true);
        if (isset($payplug_countries[$this->name])) {
            $shipping_address = $this->dependencies
                ->getPlugin()
                ->getAddress()
                ->get((int) $this->context->cart->id_address_delivery);
            $shipping_iso = $this->dependencies
                ->configClass
                ->getIsoCodeByCountryId((int) $shipping_address->id_country);

            if (!$this->dependencies
                ->getValidators()['payment']
                ->isAllowedCountry(implode(',', $payplug_countries[$this->name]), $shipping_iso)['result']) {
                return $payment_options;
            }
        }

        $payplug_amounts = json_decode($this->configuration->getValue('amounts'), true);
        $price_limit = isset($payplug_amounts[$this->name]) ? $payplug_amounts[$this->name] : $payplug_amounts['default'];
        $cart_amount = $this->context->cart->getOrderTotal(true);
        if (false === strpos('oney', $this->name)) {
            if (!$this->dependencies
                ->getHelpers()['amount']
                ->isValidAmount($price_limit, (float) $cart_amount)['result']) {
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
}
