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
    protected $dependencies;
    /** @var array */
    protected $external_url;
    /** @var string */
    protected $img_path;
    /** @var string */
    protected $iso_code;
    /** @var object */
    protected $link;
    /** @var string */
    protected $name;
    /** @var array */
    protected $translation;

    /** @var string */
    private $action;
    /** @var string */
    private $callToActionText;
    /** @var int */
    private $cart;
    /** @var bool */
    private $checked;
    /** @var array */
    private $description;
    /** @var array */
    private $inputs;
    /** @var string */
    private $label;
    /** @var string */
    private $logo;
    /** @var object */
    private $resource;
    /** @var string */
    private $title;
    /** @var string */
    private $tpl;
    /** @var bool */
    private $use_sandbox;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

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
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/svg/payment/';
        $this->link = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->link;
        $this->translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaymentMethodsTranslations();
    }

    public function cache()
    {
    }

    public function getAvailableOptions()
    {
    }

    public function get($key)
    {
        return $this->{$key};
    }

    public function getOption($current_configuration = [])
    {
        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!is_array($current_configuration)) {
            $logger->addLog('ApiRest::getPaymentMethodsSection: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        if (!isset($this->name) || !$this->name) {
            $logger->addLog('ApiRest::getPaymentMethodsSection: Can not load option the name is missing.');

            return [];
        }

        return [
            'type' => 'payment_method',
            'name' => $this->name,
            'title' => $this->translation[$this->name]['title'],
            'image' => $this->img_path . $this->name . '.svg',
            'checked' => $current_configuration[$this->name],
            'available_test_mode' => true,
            'descriptions' => [
                'live' => [
                    'description' => $this->translation[$this->name]['descriptions']['live'],
                    'link_know_more' => [
                        'text' => $this->translation[$this->name]['link'],
                        'url' => $this->external_url[$this->name],
                        'target' => '_blank',
                    ],
                ],
                'sandbox' => [
                    'description' => $this->translation[$this->name]['descriptions']['live'],
                    'link_know_more' => [
                        'text' => $this->translation[$this->name]['link'],
                        'url' => $this->external_url[$this->name],
                        'target' => '_blank',
                    ],
                ],
            ],
        ];
    }

    public function getPaymentOption()
    {
    }

    public function getReturnUrl()
    {
    }

    public function getState()
    {
    }

    public function hydrate()
    {
    }

    public function restoreCached()
    {
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
}
