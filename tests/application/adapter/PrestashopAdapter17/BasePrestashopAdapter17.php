<?php

namespace PayPlug\tests\application\adapter\PrestashopAdapter17;

use PayPlug\src\application\adapter\PrestashopAdapter17;
use PayPlug\src\models\classes\Configuration;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/PaymentOptionStub.php';

abstract class BasePrestashopAdapter17 extends TestCase
{
    protected $adapter;
    protected $dependencies;
    protected $config_class;
    protected $configuration;
    protected $constant;
    protected $context;
    protected $plugin;
    protected $routes;
    protected $translation;

    public function setUp(): void
    {
        $this->adapter = \Mockery::mock(PrestashopAdapter17::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->config_class = \Mockery::mock('ConfigClass');
        $this->config_class->shouldReceive('fetchTemplate')->andReturn('')->byDefault();
        $this->configuration = \Mockery::mock(Configuration::class);
        $this->constant = \Mockery::mock('Constant');
        $this->routes = \Mockery::mock('Routes');
        $this->translation = \Mockery::mock('Translation');
        $this->translation->shouldReceive('l')
            ->andReturnUsing(function ($string) {
                return $string;
            });

        $this->context = ContextMock::get();
        $this->context->currency->iso_code = 'EUR';
        $this->context->smarty = \Mockery::mock('Smarty');
        $this->context->smarty->shouldReceive('assign')->byDefault();
        $this->context->link = \Mockery::mock('Link');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getRoutes' => $this->routes,
            'getTranslationClass' => $this->translation,
        ]);

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->configClass = $this->config_class;
        $this->dependencies->shouldReceive('getPlugin')
            ->andReturn($this->plugin);

        $this->setPrivateProperty('dependencies', $this->dependencies);
        $this->setPrivateProperty('configuration', $this->configuration);
        $this->setPrivateProperty('constant', $this->constant);
        $this->setPrivateProperty('context', $this->context);
    }

    protected function setPrivateProperty($name, $value)
    {
        // Reflection on the mock instance itself won't find these properties:
        // they are `private` and declared on PrestashopAdapter17, and PHP's
        // reflection only resolves private properties via the ACTUAL
        // declaring class, not a subclass (which is what Mockery's generated
        // mock class is). Reflecting the real class directly works and the
        // resulting ReflectionProperty can still set the value on the mock
        // instance, since it genuinely is-a PrestashopAdapter17.
        $reflection = new \ReflectionClass(PrestashopAdapter17::class);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->adapter, $value);
    }
}
