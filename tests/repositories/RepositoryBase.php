<?php

namespace PayPlug\tests\repositories;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PayPlug\tests\mock\DependenciesMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class RepositoryBase extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $myLogPhp;
    protected $dependencies;
    protected $repo;

    protected $arrayCache;
    protected $arrayLogger;

    // adapter
    protected $assign;
    protected $address;
    protected $carrier;
    protected $cart;
    protected $config;
    protected $constant;
    protected $context;
    protected $country;
    protected $currency;
    protected $language;
    protected $media;
    protected $product;
    protected $shop;
    protected $tools;
    protected $translate;
    protected $validate;

    // repository
    protected $cache;
    protected $logger;
    protected $order_state;
    protected $order_state_entity;
    protected $query;
    protected $sql;

    // temporary classes
    //  protected $amountCurrencyClass;
//    protected $amountCurrencyClass_static;
    protected $configClass;

    public function setUp()
    {
        $this->myLogPhp = MockHelper::createMockFactory('PayPlug\classes\MyLogPHP');
        $this->myLogPhp
            ->shouldReceive('info')
            ->andReturn(true)
        ;

        $this->setAdapter();
        $this->setRepository();
        $this->setTemporariesClasses();
    }

    private function setAdapter()
    {
        $this->assign = MockHelper::createAssignMock('PayPlug\src\application\adapter\AssignAdapter');
        $this->address = MockHelper::createAddressMock('PayPlug\src\application\adapter\AddressAdapter');
        $this->carrier = MockHelper::createMockFactory('PayPlug\src\application\adapter\CarrierAdapter');
        $this->cart = MockHelper::createMockFactory('PayPlug\src\application\adapter\CartAdapter');
        $this->config = MockHelper::createMockFactory('PayPlug\src\application\adapter\ConfigurationAdapter');
        $this->constant = MockHelper::createMockFactory('PayPlug\src\application\adapter\ConstantAdapter');
        $this->context = MockHelper::createContextMock('PayPlug\src\application\adapter\ContextAdapter');
        $this->country = MockHelper::createMockFactory('PayPlug\src\application\adapter\CountryAdapter');
        $this->currency = MockHelper::createMockFactory('PayPlug\src\application\adapter\CurrencyAdapter');
        $this->language = MockHelper::createMockFactory('PayPlug\src\application\adapter\LanguageAdapter');
        $this->media = MockHelper::createMockFactory('PayPlug\src\application\adapter\MediaAdapter');
        $this->order_state_adapter = MockHelper::createMockFactory('PayPlug\src\application\adapter\OrderStateAdapter');
        $this->product = MockHelper::createMockFactory('PayPlug\src\application\adapter\ProductAdapter');
        $this->shop = MockHelper::createMockFactory('PayPlug\src\application\adapter\ShopAdapter');
        $this->tools = MockHelper::createToolsMock('PayPlug\src\application\adapter\ToolsAdapter');
        $this->translate = MockHelper::createTranslateMock('PayPlug\src\application\adapter\TranslationAdapter');
        $this->validate = MockHelper::createValidateMock('PayPlug\src\application\adapter\ValidateAdapter');
    }

    private function setRepository()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->logger = MockHelper::createMockFactory('PayPlug\src\repositories\LoggerRepository');
        $this->query = MockHelper::createQueryMock('PayPlug\src\repositories\QueryRepository');
        $this->sql = MockHelper::createMockFactory('PayPlug\src\repositories\SQLtableRepository');
    }

    private function setTemporariesClasses()
    {
        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            })
        ;
        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'oneyMinAmounts':
                        return 'PAYPLUG_ONEY_MIN_AMOUNTS';

                    case 'oneyMaxAmounts':
                        return 'PAYPLUG_ONEY_MAX_AMOUNTS';

                    case 'oneyCustomMinAmounts':
                        return 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS';

                    case 'oneyCustomMaxAmounts':
                        return 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS';

                    case 'oneyAllowedCountries':
                        return 'PAYPLUG_ONEY_ALLOWED_COUNTRIES';

                    default:
                        return true;
                }
            })
        ;
        $this->dependencies->name = DependenciesMock::get();

        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->amountCurrencyClass
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $to_cents = false) {
                if ($to_cents) {
                    return (float) ($amount / 100);
                }
                $amount = (float) ($amount * 1000);
                $amount = (float) ($amount / 10);

                return (int) ($this->tools->tool('ps_round', $amount));
            })
        ;

        $this->dependencies->apiClass = \Mockery::mock('alias:PayPlug\classes\ApiClass');
        $this->dependencies->paymentClass = \Mockery::mock('alias:PayPlug\classes\PaymentClass');
        $this->dependencies->configClass = \Mockery::mock('alias:PayPlug\classes\ConfigClass');
    }
}
