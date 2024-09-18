<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\src\models\classes\paymentMethod\OneyPaymentMethod;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\accountValidator;
use PayPlug\tests\mock\AddressMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\CountryMock;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

// todo: Move Oney tests out of "tests/repositories" folder
class BaseOneyRepository extends RepositoryBase
{
    protected $account_validator;
    protected $address_adapter;
    protected $amount_helper;
    protected $assign_adapter;
    protected $arrayCache;
    protected $arrayLogger;
    protected $cacheAdapter;
    protected $constant;
    protected $contextAdapter;
    protected $countryAdapter;
    protected $dependencies;
    protected $helpers;
    protected $loggerRepository;
    protected $routes;
    protected $toolsAdapter;

    public function setUp()
    {
        parent::setUp();

        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->config
            ->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'PS_CURRENCY_DEFAULT':
                        return 1;
                    case 'PS_SHOP_NAME':
                        return 'Payplug';
                    default:
                        return true;
                }
            });

        $this->validators = [
            'account' => \Mockery::mock(accountValidator::class)->makePartial(),
            'payment' => \Mockery::mock(paymentValidator::class)->makePartial(),
        ];
        $this->amount_helper = \Mockery::mock('AmountHelper');
        $this->amount_helper->shouldReceive([
            'formatOneyAmount' => [
                'result' => 10,
                'message' => '$amount is formatted',
            ],
        ]);

        $this->address_adapter = \Mockery::mock('AddressAdapter');
        $this->address_adapter->shouldReceive([
            'get' => AddressMock::get(),
        ]);
        $this->assign_adapter = \Mockery::mock('AssignAdapter');
        $this->assign_adapter->shouldReceive([
            'assign' => '',
        ]);
        $this->cacheAdapter = \Mockery::mock('CacheAdapter');
        $this->contextAdapter = \Mockery::mock('ContextAdapter');
        $get_context = \Mockery::mock('GetContext');
        $get_context->shouldReceive([
            'getContext' => \Mockery::mock('GetContext'),
        ]);
        $get_context->shouldReceive([
            'getContext' => \Mockery::mock('GetContext'),
        ]);
        $this->contextAdapter->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->country->shouldReceive([
            'getCountry' => CountryMock::get(),
        ]);
        $this->constant = \Mockery::mock('Constant');
        $this->constant
            ->shouldReceive([
                'get' => '',
            ]);
        $this->routes = \Mockery::mock(Routes::class)->makePartial();
        $this->plugin
            ->shouldReceive([
                'getAddress' => $this->address_adapter,
                'getAssign' => $this->assign_adapter,
                'getCache' => $this->cacheAdapter,
                'getCarrier' => $this->carrier,
                'getCart' => $this->cart,
                'getConstant' => $this->constant,
                'getContext' => $this->contextAdapter,
                'getCountry' => $this->country,
                'getCurrency' => $this->currency,
                'getLogger' => $this->logger,
                'getRoutes' => $this->routes,
                'getTools' => $this->tools,
            ]);

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ]);
        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->configClass = $configClass;
        $this->dependencies->name = 'payplug';
        $this->dependencies
            ->shouldReceive([
                'getHelpers' => [
                    'user' => \Mockery::mock(UserHelper::class)->makePartial(),
                    'amount' => $this->amount_helper,
                ],
                'getPlugin' => $this->plugin,
                'getValidators' => $this->validators,
            ]);

        $this->logger->shouldReceive([
            'setProcess' => $this->logger,
        ]);

        $this->repo = \Mockery::mock(OneyPaymentMethod::class, [$this->dependencies])->makePartial();

        $this->arrayCache = [];
        $this->arrayLogger = [];

        MockHelper::createAddLogMock($this->logger, $this->arrayLogger);
    }
}
