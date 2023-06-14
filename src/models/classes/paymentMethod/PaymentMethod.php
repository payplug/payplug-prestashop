<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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
    /** @var array */
    protected $translation;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function get($key)
    {
        return $this->{$key};
    }

    public function getPaymentMethod($name = '')
    {
        $this->setParameters();

        if (!isset($name) || !$name) {
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

    public function getAvailablePaymentMethod()
    {
        return [
            'amex',
            'applepay',
            'bancontact',
            'giropay',
            'ideal',
            'inst',
            'mybank',
            'one_click',
            'oney',
            'satispay',
            'sofort',
            'standard',
        ];
    }

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

        return [
            'type' => 'payment_method',
            'name' => $this->name,
            'title' => $this->translation[$this->name]['title'],
            'image' => $this->img_path . 'svg/payment/' . $this->name . '.svg',
            'checked' => $current_configuration[$this->name],
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
                    'description' => $this->translation[$this->name]['descriptions']['live'],
                    'link_know_more' => isset($this->external_url[$this->name]) ? [
                        'text' => $this->translation[$this->name]['link'],
                        'url' => $this->external_url[$this->name],
                        'target' => '_blank',
                    ] : [],
                ],
            ],
        ];
    }

    public function getPaymentOptions()
    {
        $this->setParameters();

        $options = $this->dependencies->configClass->getAvailableOptions($this->context->cart);
        $available_payment_methods = $this->getAvailablePaymentMethod();
        $payment_options = [];

        foreach ($available_payment_methods as $payment_method) {
            $allowed_feature = $this->dependencies->configClass->isValidFeature('feature_' . $payment_method);
            if (isset($options[$payment_method]) && $options[$payment_method] && $allowed_feature) {
                $obj = $this->getPaymentMethod($payment_method);
                if (is_object($obj)) {
                    $payment_options[] = $obj->getPaymentOption();
                }
            }
        }

        return $payment_options;
    }

    public function set($key, $value)
    {
        if (is_null($value)) {
            throw (new BadParameterException('Invalid argument, $value must be a non null'));
        }
        switch ($key) {
            case 'description':
            case 'inputs':
                if (!is_array($value)) {
                    throw (new BadParameterException('Invalid argument, $value must be an array'));
                }

                break;
            case 'checked':
            case 'use_sandbox':
                if (!is_bool($value)) {
                    throw (new BadParameterException('Invalid argument, $value must be a boolean'));
                }

                break;
            case 'cart':
                if (!is_int($value)) {
                    throw (new BadParameterException('Invalid argument, $value must be an integer'));
                }

                break;
            case 'resource':
                if (!is_object($value)) {
                    throw (new BadParameterException('Invalid argument, $value must be an object'));
                }

                break;
            case 'action':
            case 'callToActionText':
            case 'label':
            case 'logo':
            case 'name':
            case 'title':
            case 'tpl':
                if (!is_string($value)) {
                    throw (new BadParameterException('Invalid argument, $value must be a string'));
                }

                break;
            default:
                throw (new BadParameterException('Invalid argument, $key is not recognize key'));
        }
        $this->{$key} = $value;

        return $this;
    }

    protected function setParameters()
    {
        $this->context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();
        $this->iso_code = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->language->iso_code;
        $this->external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($this->iso_code);
        $this->img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/';
        $this->link = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->link;
        $this->logger = $this->dependencies
            ->getPlugin()
            ->getLogger();
        $this->translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaymentMethodsTranslations();
    }

    protected function getPaymentOption()
    {
        $this->setParameters();

        if (!isset($this->name) || !$this->name) {
            $this->logger->addLog('PaymentMethod::getPaymentOption: Can not load option the name is missing.');

            return [];
        }

        return [
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
            'tpl' => $this->name . '.tpl',
            'extra_classes' => $this->name,
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => $this->name]
            ),
            'logo' => $this->img_path . 'svg/checkout/' . $this->name . '.svg',
            'callToActionText' => $this->translation[$this->name]['call_to_action'],
            'action' => $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', [], true),
            'moduleName' => $this->dependencies->name,
        ];
    }
}
