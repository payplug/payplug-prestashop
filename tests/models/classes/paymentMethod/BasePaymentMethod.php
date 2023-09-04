<?php

namespace PayPlug\tests\models\classes\paymentMethod;

use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\browserValidator;
use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\AddressMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BasePaymentMethod extends TestCase
{
    use FormatDataProvider;

    protected $address;
    protected $classe;
    protected $configuration;
    protected $constant;
    protected $context;
    protected $dependencies;
    protected $helpers;
    protected $logger;
    protected $plugin;
    protected $route;
    protected $translation;
    protected $validators;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            })
        ;

        $this->routes = \Mockery::mock(Routes::class)->makePartial();

        $this->constant = \Mockery::mock('Constant');
        $this->constant
            ->shouldReceive([
                'get' => '',
            ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();

        $this->context = \Mockery::mock('Context');
        $context = ContextMock::get();
        $context->cart = \Mockery::mock('Cart');
        $context->cart->id = 1;
        $context->cart->id_address_delivery = 2;
        $context->cart->id_address_invoice = 3;
        $context->cart
            ->shouldReceive('getOrderTotal')
            ->andReturn(42.42);

        $link = \Mockery::mock('Link');
        $link->shouldReceive([
            'getModuleLink' => 'link',
        ]);
        $context->link = $link;
        $this->context
            ->shouldReceive([
                'get' => $context,
            ]);

        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration
            ->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{"default":{"min":"EUR:99","max":"EUR:2000000"}}');

        $this->address = \Mockery::mock('Address');
        $this->address->shouldReceive([
            'get' => AddressMock::get(),
        ]);

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getAddress' => $this->address,
                'getConfigurationClass' => $this->configuration,
                'getConstant' => $this->constant,
                'getContext' => $this->context,
                'getLogger' => $this->logger,
                'getRoutes' => $this->routes,
                'getTranslation' => $this->translation,
            ]);

        $this->helpers = [
            'amount' => \Mockery::mock(AmountHelper::class, [$this->dependencies])->makePartial(),
        ];
        $this->helpers['amount']
            ->shouldReceive('isValidAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);

        $this->validators = [
            'browser' => \Mockery::mock(browserValidator::class)->makePartial(),
            'payment' => \Mockery::mock(paymentValidator::class)->makePartial(),
        ];

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getHelpers' => $this->helpers,
                'getValidators' => $this->validators,
            ]);

        $this->classe = \Mockery::mock(PaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
