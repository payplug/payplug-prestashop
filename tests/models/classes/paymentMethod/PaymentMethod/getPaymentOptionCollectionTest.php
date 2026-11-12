<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getPaymentOptionCollectionTest extends BasePaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();
        $this->configuration->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    default:
                        return $this->configuration->getDefault($key);
                }
            });
    }

    public function testWhenNoAvailablePaymentMethodFound()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [],
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    public function testWhenNoAvailableOptionFound()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentOptionsAvailability' => [],
        ]);
        $configClass->shouldReceive([
            'isValidFeature' => true,
            'getImgLang' => 'fr',
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    public function testWhenPaymentMethodIsntAllowed()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payment_method
     */
    public function testWhenPaymentMethodObjectIsntValid($payment_method)
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $payment_method,
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentOptionCollection());
    }

    public function testWhenOptionIsReturn()
    {
        $configClass = \Mockery::mock('Config');
        $this->class->shouldReceive([
            'getAvailablePaymentMethod' => [
                'standard',
            ],
            'getPaymentMethod' => $this->class,
            'getPaymentOption' => [
                'standard' => new \stdClass(),
            ],
        ]);
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertTrue(array_key_exists('standard', $this->class->getPaymentOptionCollection()));
    }

    public function testAnUnsupportedCartCurrencyNotServedByTheHostedFieldsIsLoggedOncePerRender()
    {
        $this->context->currency->iso_code = 'USD';
        $this->mockTwoEnabledPaymentMethods();
        $this->mockHostedFieldsAdapter(false);

        $this->class->getPaymentOptionCollection();

        $this->class->shouldHaveReceived('getPaymentOption')->twice();
        $this->logger->shouldHaveReceived('addLog')
            ->with(
                'PaymentMethod::getPaymentOptionCollection - cart currency USD is not supported and not served by the hosted fields: no payment option offered',
                'info'
            )
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testAnUnsupportedCartCurrencyIsLoggedWhenThePrestashopAdapterCannotBeLoaded()
    {
        $this->context->currency->iso_code = 'USD';
        $this->mockTwoEnabledPaymentMethods();
        $this->dependencies->shouldReceive('loadAdapterPresta')->andReturn(false);

        $this->class->getPaymentOptionCollection();

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/cart currency USD is not supported and not served by the hosted fields/'), 'info')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testAnUnsupportedCartCurrencyServedByTheHostedFieldsIsNotLogged()
    {
        $this->context->currency->iso_code = 'USD';
        $this->mockTwoEnabledPaymentMethods();
        $prestashop_adapter = $this->mockHostedFieldsAdapter(true);

        $this->class->getPaymentOptionCollection();

        $prestashop_adapter->shouldHaveReceived('canServeHostedFields')->with('USD')->once();
        $this->logger->shouldNotHaveReceived('addLog');
        $this->addToAssertionCount(1);
    }

    public function testASupportedCartCurrencyIsNotLogged()
    {
        $this->context->currency->iso_code = 'EUR';
        $this->mockTwoEnabledPaymentMethods();

        $this->class->getPaymentOptionCollection();

        $this->logger->shouldNotHaveReceived('addLog');
        $this->dependencies->shouldNotHaveReceived('loadAdapterPresta');
        $this->addToAssertionCount(1);
    }

    private function mockHostedFieldsAdapter($can_serve)
    {
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $prestashop_adapter->shouldReceive('canServeHostedFields')->andReturn($can_serve);
        $this->dependencies->shouldReceive('loadAdapterPresta')->andReturn($prestashop_adapter);

        return $prestashop_adapter;
    }

    private function mockTwoEnabledPaymentMethods()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->class->shouldReceive([
            'getPaymentOptionsAvailability' => [
                'standard' => true,
                'bancontact' => true,
            ],
            'getPaymentMethod' => $this->class,
        ]);
        $this->class->shouldReceive('getPaymentOption')
            ->andReturnUsing(function ($payment_options) {
                return $payment_options;
            });
    }
}
