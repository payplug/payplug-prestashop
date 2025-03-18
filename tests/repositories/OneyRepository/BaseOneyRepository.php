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
    protected $address_adapter;
    protected $amount_helper;
    protected $api_service;
    protected $assign_adapter;
    protected $cache_adapter;
    protected $constant;
    protected $context_adapter;
    protected $dependencies;
    protected $helpers;
    protected $loggerRepository;
    protected $routes;
    protected $module;
    protected $module_adapter;

    public function setUp()
    {
        parent::setUp();

        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->config->shouldReceive('get')
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
        $this->api_service = \Mockery::mock('ApiService');
        $this->assign_adapter = \Mockery::mock('AssignAdapter');
        $this->assign_adapter->shouldReceive([
            'assign' => '',
        ]);
        $this->cache_adapter = \Mockery::mock('CacheAdapter');
        $this->context_adapter = \Mockery::mock('ContextAdapter');
        $get_context = \Mockery::mock('GetContext');
        $get_context->shouldReceive([
            'getContext' => \Mockery::mock('GetContext'),
        ]);
        $get_context->shouldReceive([
            'getContext' => \Mockery::mock('GetContext'),
        ]);
        $this->context_adapter->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->country->shouldReceive([
            'getCountry' => CountryMock::get(),
        ]);
        $this->constant = \Mockery::mock('Constant');
        $this->constant->shouldReceive([
            'get' => '',
        ]);
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);
        $this->routes = \Mockery::mock(Routes::class)->makePartial();

        $this->module = \Mockery::mock('Module');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($this->api_service);

        $this->plugin->shouldReceive([
            'getAddress' => $this->address_adapter,
            'getAssign' => $this->assign_adapter,
            'getCache' => $this->cache_adapter,
            'getCarrier' => $this->carrier,
            'getCart' => $this->cart,
            'getConstant' => $this->constant,
            'getContext' => $this->context_adapter,
            'getCountry' => $this->country,
            'getCurrency' => $this->currency,
            'getLogger' => $this->logger,
            'getRoutes' => $this->routes,
            'getTools' => $this->tools,
            'getModule' => $this->module_adapter,
        ]);

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->configClass = $configClass;
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
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
    }
}
