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

use PayPlug\src\entities\PluginEntity;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\src\specific\ProductSpecific;
use PayPlug\src\specific\ToolsSpecific;
use PayPlug\src\specific\ValidateSpecific;

class PluginRepository extends Repository
{
    private $cache;
    private $card;
    private $logger;
    private $oney;
    private $plugin;
    private $order_state;
    private $payment;
    private $translate;

    // Specific classes
    private $configuration;
    private $context;
    private $country;
    private $product;
    private $query;
    private $tools;
    private $validate;

    public function __construct($payplug = null)
    {
        $this->cache    = new CacheRepository();
        $this->card     = new CardRepository();
        $this->card->setPayplug($payplug);
        $this->configuration = new ConfigurationSpecific();
        $this->context  = new ContextSpecific();
        $this->country  = new CountrySpecific();
        $this->logger   = new LoggerRepository();
        $this->oney     = new OneyRepository($payplug);
        $this->payment  = new PaymentRepository($payplug);
        $this->plugin   = new PluginEntity();
        $this->product  = new ProductSpecific();
        $this->query    = new QueryRepository();
        $this->tools    = new ToolsSpecific();
        $this->translate = new TranslationsRepository();
        $this->translate->setPayplug($payplug);
        $this->validate = new ValidateSpecific();
        $this->order_state = new OrderStateRepository();
        $this->plugin
            ->setApiVersion('2019-08-06')
            ->setCache($this->cache)
            ->setCard($this->card)
            ->setConfiguration($this->configuration)
            ->setContext($this->context)
            ->setCountry($this->country)
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
