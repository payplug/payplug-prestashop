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
use PayPlug\src\entities\PaymentEntity;
use PayPlug\src\entities\PluginEntity;
use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\CarrierSpecific;
use PayPlug\src\specific\CartSpecific;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\specific\ConstantSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\src\specific\LanguageSpecific;
use PayPlug\src\specific\ProductSpecific;
use PayPlug\src\specific\ShopSpecific;
use PayPlug\src\specific\ToolsSpecific;
use PayPlug\src\specific\ValidateSpecific;

class PluginRepository extends Repository
{
    protected $payplug;

    // Entities
    private $oneyEntity;
    private $paymentEntity;
    private $plugin;

    // Repositories & necessary classes
    private $cache;
    private $card;
    private $hookRepo;
    private $install;
    private $logger;
    private $myLogPhp;
    private $oney;
    private $order_state;
    private $payment;
    private $query;
    private $sql;
    private $translate;

    // Specific classes
    private $address;
    private $carrier;
    private $cart;
    private $configuration;
    private $constant;
    private $context;
    private $country;
    private $language;
    private $product;
    private $shop;
    private $tools;
    private $validate;

    public function __construct($payplug = null)
    {
        $this->payplug = $payplug;

        $this->setEntities();
        $this->setSpecific();
        $this->setRepositories();

        $this->myLogPhp = new MyLogPHP();

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
            ->setHook($this->hookRepo)
            ->setInstall($this->install)
            ->setLogger($this->logger)
            ->setPayment($this->payment)
            ->setProduct($this->product)
            ->setOney($this->oney)
            ->setQuery($this->query)
            ->setSql($this->sql)
            ->setTools($this->tools)
            ->setTranslate($this->translate)
            ->setValidate($this->validate)
            ->setOrderState($this->order_state);
        $this->setEntity($this->plugin);
    }

    private function setEntities()
    {
        $this->oneyEntity = new OneyEntity();
        $this->paymentEntity = new PaymentEntity();
        $this->plugin = new PluginEntity();
    }

    private function setRepositories()
    {
        $this->cache = new CacheRepository();
        $this->card = new CardRepository($this->payplug);
        $this->logger = new LoggerRepository();
        $this->query = new QueryRepository();
        $this->translate = new TranslationsRepository($this->payplug);

        $this->sql = new SQLtableRepository(
            $this->query
        );

        $this->hookRepo = new HookRepository(
            $this->payplug,
            $this->constant
        );

        $this->oney = new OneyRepository(
            $this->cache,
            $this->logger,
            $this->address,
            $this->cart,
            $this->carrier,
            $this->configuration,
            $this->context,
            $this->country,
            $this->tools,
            $this->validate,
            $this->oneyEntity,
            $this->myLogPhp,
            $this->payplug
        );

        $this->order_state = new OrderStateRepository(
            $this->configuration,
            $this->language,
            $this->query,
            $this->tools
        );

        $this->payment = new PaymentRepository(
            $this->payplug,
            $this->cart,
            $this->logger,
            $this->paymentEntity,
            $this->query,
            $this->constant
        );

        $this->install = new InstallRepository(
            $this->configuration,
            $this->constant,
            $this->context,
            $this->order_state,
            $this->shop,
            $this->sql,
            $this->tools,
            $this->payplug
        );
    }

    private function setSpecific()
    {
        $this->address = new AddressSpecific();
        $this->carrier = new CarrierSpecific();
        $this->cart = new CartSpecific();
        $this->configuration = new ConfigurationSpecific();
        $this->constant = new ConstantSpecific();
        $this->context = new ContextSpecific();
        $this->country = new CountrySpecific();
        $this->language = new LanguageSpecific();
        $this->product = new ProductSpecific();
        $this->shop = new ShopSpecific();
        $this->tools = new ToolsSpecific();
        $this->validate = new ValidateSpecific();
    }
}
