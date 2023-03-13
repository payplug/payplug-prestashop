<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentMethodsSectionTest extends BaseApiRest
{
    //private $context;

    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $context,
        ]);
        $this->classe->shouldReceive([
            'getDeferredState' => [],
        ]);

        $link = \Mockery::mock('Link');
        $link
            ->shouldReceive([
                'getAdminLink' => 'AdminPayPlugInstallment',
            ])
        ;
        $this->plugin->getContext()->get()->link = $link;
    }

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [null];
        yield [false];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenConfigurationIsInvalidArrayFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->classe->getPaymentMethodsSection($current_configuration)
        );
    }

    public function testWhenNoMethodAreEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => false,
            ])
        ;
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->classe->getPaymentMethodsSection($current_configuration));
    }

    public function testWhenStandardIsNotEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_standard' != $key;
            })
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertFalse(in_array('standard', $payment_methods));
    }

    public function testWhenStandardIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ])
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('standard', $payment_methods));
    }

    public function testWhenAmexIsNotEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_amex' != $key;
            })
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertFalse(in_array('american_express', $payment_methods));
    }

    public function testWhenAmexIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ])
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('american_express', $payment_methods));
    }

    public function testWhenApplePayIsNotEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_applepay' != $key;
            })
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertFalse(in_array('applepay', $payment_methods));
    }

    public function testWhenApplePayIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ])
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('applepay', $payment_methods));
    }

    public function testWhenBancontactIsNotEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_bancontact' != $key;
            })
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertFalse(in_array('bancontact', $payment_methods));
    }

    public function testWhenBancontactIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ])
        ;
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('bancontact', $payment_methods));
    }
}
