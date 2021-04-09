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

namespace PayPlug\src\repositories;

use PayPlug\classes\MyLogPHP;
use PayPlug\src\entities\OneyEntity;
use PayPlug\src\entities\PluginEntity;
use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\CarrierSpecific;
use PayPlug\src\specific\CartSpecific;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\src\specific\CurrencySpecific;
use PayPlug\src\specific\ProductSpecific;
use PayPlug\src\specific\ToolsSpecific;
use PayPlug\src\specific\ValidateSpecific;
use PayPlug\src\specific\AssignSpecific;

class PluginRepository extends Repository
{
    // Entities
    private $oneyEntity;

    // Repositories & necessary classes
    private $cache;
    private $card;
    private $logger;
    private $myLogPhp;
    private $oney;
    private $plugin;
    private $order_state;
    private $payment;
    private $translate;

    // Specific classes
    private $address;
    private $assign;
    private $carrier;
    private $cart;
    private $configuration;
    private $context;
    private $country;
    private $currency;
    private $product;
    private $query;
    private $tools;
    private $validate;

    public function __construct($payplug = null)
    {
        $this->address  = new AddressSpecific();
        $this->cache    = new CacheRepository();
        $this->card     = new CardRepository($payplug);
        $this->carrier  = new CarrierSpecific();
        $this->cart     = new CartSpecific();
        $this->configuration = new ConfigurationSpecific();
        $this->context  = new ContextSpecific();
        $this->country  = new CountrySpecific();
        $this->currency  = new CurrencySpecific();
        $this->logger   = new LoggerRepository();
        $this->myLogPhp = new MyLogPHP();
        $this->oneyEntity = new OneyEntity();
        $this->plugin   = new PluginEntity();
        $this->product  = new ProductSpecific();
        $this->query    = new QueryRepository();
        $this->tools    = new ToolsSpecific();
        $this->translate = new TranslationsRepository($payplug);
        $this->order_state = new OrderStateRepository();
        $this->validate = new ValidateSpecific();
        $this->assign = new AssignSpecific();

        $this->oney = new OneyRepository(
            $this->cache,
            $this->logger,
            $this->address,
            $this->cart,
            $this->carrier,
            $this->configuration,
            $this->context,
            $this->country,
            $this->currency,
            $this->tools,
            $this->validate,
            $this->oneyEntity,
            $this->myLogPhp,
            $payplug,
            $this->assign
        );

        $this->plugin
            ->setApiVersion('2019-08-06')
            ->setAddress($this->address)
            ->setCache($this->cache)
            ->setCard($this->card)
            ->setCarrier($this->carrier)
            ->setCart($this->cart)
            ->setConfiguration($this->configuration)
            ->setContext($this->context)
            ->setCountry($this->country)
            ->setCurrency($this->currency)
            ->setLogger($this->logger)
            ->setPayment($this->payment)
            ->setProduct($this->product)
            ->setOney($this->oney)
            ->setQuery($this->query)
            ->setTools($this->tools)
            ->setTranslate($this->translate)
            ->setValidate($this->validate)
            ->setOrderState($this->order_state)
        ;
        $this->setEntity($this->plugin);
    }
}
