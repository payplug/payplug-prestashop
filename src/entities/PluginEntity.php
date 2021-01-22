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

namespace PayPlug\src\entities;

use PayPlug\src\exceptions\BadParameterException;

class PluginEntity
{
    // Vars
    private $api_url;
    private $api_version;

    // Our classes
    private $cache;
    private $card;
    private $logger;
    private $oney;
    private $order_state;
    private $translate;

    // Specific classes
    private $configuration;
    private $context;
    private $country;
    private $product;
    private $tools;
    private $query;
    private $validate;

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    /**
     * @param string $api_url
     * @return PluginEntity
     */
    public function setApiUrl($api_url)
    {
        if (!is_string($api_url) || !preg_match('/http(s?):\/\/api(-\w+)?.payplug.(com|test)/', $api_url)) {
            throw (
            new BadParameterException(
                'Invalid url format, param $api_url must be a a valid api url format'
            )
            );
        } else {
            $this->api_url = $api_url;
            return $this;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @param string $api_version
     * @return PluginEntity
     */
    public function setApiVersion($api_version)
    {
        if (!is_string($api_version) || !preg_match('/(\d{4})-(\d{2})-(\d{2})/', $api_version)) {
            throw (
            new BadParameterException(
                'Invalid url format, param $api_url must be a a valid api url format'
            )
            );
        } else {
            $this->api_version = $api_version;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param object $card
     * @return PluginEntity
     */
    public function setCard($card)
    {
        if (!is_object($card)) {
            throw (
            new BadParameterException(
                'Invalid Card object, param $card must be a CardRepository'
            )
            );
        } else {
            $this->card = $card;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param object $logger
     * @return PluginEntity
     */
    public function setLogger($logger)
    {
        if (!is_object($logger)) {
            throw (
            new BadParameterException(
                'Invalid Logger object, param $card must be a LoggerRepository'
            )
            );
        } else {
            $this->logger = $logger;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getOney()
    {
        return $this->oney;
    }

    /**
     * @param object $oney
     * @return PluginEntity
     */
    public function setOney($oney)
    {
        if (!is_object($oney)) {
            throw (
            new BadParameterException(
                'Invalid Oney object, param $card must be a OneyRepository'
            )
            );
        } else {
            $this->oney = $oney;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param object $cache
     * @return PluginEntity
     */
    public function setCache($cache)
    {
        if (!is_object($cache)) {
            throw (
            new BadParameterException(
                'Invalid Cache object, param $card must be a CacheRepository'
            )
            );
        } else {
            $this->cache = $cache;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param object $configuration
     * @return PluginEntity
     */
    public function setConfiguration($configuration)
    {
        if (!is_object($configuration)) {
            throw (
            new BadParameterException(
                'Invalid Configuration object, param $card must be a ConfigurationSpecific'
            )
            );
        } else {
            $this->configuration = $configuration;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param object $context
     * @return PluginEntity
     */
    public function setContext($context)
    {
        if (!is_object($context)) {
            throw (
            new BadParameterException(
                'Invalid Context object, param $card must be a ContextSpecific'
            )
            );
        } else {
            $this->context = $context;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param object $country
     * @return PluginEntity
     */
    public function setCountry($country)
    {
        if (!is_object($country)) {
            throw (
            new BadParameterException(
                'Invalid Country object, param $card must be a ContextSpecific'
            )
            );
        } else {
            $this->country = $country;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param object $product
     * @return PluginEntity
     */
    public function setProduct($product)
    {
        if (!is_object($product)) {
            throw (
            new BadParameterException(
                'Invalid Product object, param $card must be a ProductSpecific'
            )
            );
        } else {
            $this->product = $product;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param object $tools
     * @return PluginEntity
     */
    public function setTools($tools)
    {
        if (!is_object($tools)) {
            throw (
            new BadParameterException(
                'Invalid Tools object, param $card must be a ToolsSpecific'
            )
            );
        } else {
            $this->tools = $tools;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param object $query
     * @return PluginEntity
     */
    public function setQuery($query)
    {
        if (!is_object($query)) {
            throw (
            new BadParameterException(
                'Invalid Query object, param $card must be a QueryRepository'
            )
            );
        } else {
            $this->query = $query;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getValidate()
    {
        return $this->validate;
    }

    /**
     * @param object $validate
     * @return PluginEntity
     */
    public function setValidate($validate)
    {
        if (!is_object($validate)) {
            throw (
            new BadParameterException(
                'Invalid Validate object, param $card must be a ValidateSpecific'
            )
            );
        } else {
            $this->validate = $validate;
            return $this;
        }
    }

    /**
     * @return object
     */
    public function getOrderState()
    {
        return $this->order_state;
    }

    /**
     * @param object $order_state
     * @return PluginEntity
     */
    public function setOrderState($order_state)
    {
        $this->order_state = $order_state;
        return $this;
    }

    /**
     * @return object
     */
    public function getTranslate()
    {
        return $this->translate;
    }

    /**
     * @param object $translate
     * @return PluginEntity
     */
    public function setTranslate($translate)
    {
        $this->translate = $translate;
        return $this;
    }
}
