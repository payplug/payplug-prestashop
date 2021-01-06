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

namespace PayPlug\src\entities;

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

    // Specific classes
    private $configuration;
    private $context;
    private $country;
    private $product;
    private $tools;
    private $query;
    private $validate;

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    /**
     * @param mixed $api_url
     * @return PluginEntity
     */
    public function setApiUrl($api_url)
    {
        $this->api_url = $api_url;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @param mixed $api_version
     * @return PluginEntity
     */
    public function setApiVersion($api_version)
    {
        $this->api_version = $api_version;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param mixed $card
     * @return PluginEntity
     */
    public function setCard($card)
    {
        $this->card = $card;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param mixed $logger
     * @return PluginEntity
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOney()
    {
        return $this->oney;
    }

    /**
     * @param mixed $oney
     * @return PluginEntity
     */
    public function setOney($oney)
    {
        $this->oney = $oney;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param mixed $cache
     * @return PluginEntity
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     * @return PluginEntity
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     * @return PluginEntity
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return PluginEntity
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     * @return PluginEntity
     */
    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param mixed $tools
     * @return PluginEntity
     */
    public function setTools($tools)
    {
        $this->tools = $tools;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     * @return PluginEntity
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValidate()
    {
        return $this->validate;
    }

    /**
     * @param mixed $validate
     * @return PluginEntity
     */
    public function setValidate($validate)
    {
        $this->validate = $validate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderState()
    {
        return $this->order_state;
    }

    /**
     * @param mixed $order_state
     * @return PluginEntity
     */
    public function setOrderState($order_state)
    {
        $this->order_state = $order_state;
        return $this;
    }
}
