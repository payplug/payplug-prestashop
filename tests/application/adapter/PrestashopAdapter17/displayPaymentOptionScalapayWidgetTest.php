<?php

namespace PayPlug\tests\application\adapter\PrestashopAdapter17;

/**
 * @group unit
 * @group adapter
 * @group prestashop_adapter_17
 */
class displayPaymentOptionScalapayWidgetTest extends BasePrestashopAdapter17
{
    private $logs = [];
    private $payment_options;
    private $widget_action;

    public function setUp(): void
    {
        parent::setUp();
        $this->logs = [];

        $this->config_class->shouldReceive('isValidFeature')->with('feature_standard')->andReturn(false);
        $this->widget_action = \Mockery::mock('ScalapayWidgetAction');
        $this->module->shouldReceive('getService')
            ->with('payplug.action.scalapay_widget')
            ->andReturn($this->widget_action)
            ->byDefault();
        $this->logger->shouldReceive('addLog')->andReturnUsing(function ($message, $level = 'info') {
            $this->logs[] = ['level' => $level, 'message' => $message];

            return true;
        });

        $this->payment_options = [
            'bancontact' => $this->paymentOption('bancontact'),
            'scalapay' => $this->paymentOption('scalapay'),
        ];
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testTheWidgetIsShownUnderTheScalapayOption()
    {
        $this->widget_action->shouldReceive('renderCheckout')->once()->andReturn('<scalapay-widget type="checkout">');

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertCount(2, $result);
        $this->assertSame('<scalapay-widget type="checkout">', $result[1]->getAdditionalInformation());
        $this->assertNull($result[0]->getAdditionalInformation());
    }

    public function testTheScalapayOptionIsKeptWithoutWidget()
    {
        $this->widget_action->shouldReceive('renderCheckout')->once()->andReturn('');

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertCount(2, $result);
        $this->assertSame('scalapay', $result[1]->getInputs()['method']['value']);
        $this->assertNull($result[1]->getAdditionalInformation());
    }

    public function testAnEmptyWidgetDoesNotReplaceExistingInformation()
    {
        $this->payment_options['scalapay']['additionalInformation'] = '<p>existing</p>';
        $this->widget_action->shouldReceive('renderCheckout')->andReturn('');

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertSame('<p>existing</p>', $result[1]->getAdditionalInformation());
    }

    public function testARenderingErrorKeepsTheOptionAndIsLogged()
    {
        $this->widget_action->shouldReceive('renderCheckout')->andThrow(new \RuntimeException('template missing'));

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertCount(2, $result);
        $this->assertNull($result[1]->getAdditionalInformation());
        $this->assertCount(1, $this->logs);
        $this->assertSame('error', $this->logs[0]['level']);
        $this->assertStringContainsString('template missing', $this->logs[0]['message']);
    }

    public function testAMissingServiceKeepsTheOptionAndIsLogged()
    {
        $this->module->shouldReceive('getService')->with('payplug.action.scalapay_widget')->andReturn(null);

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertCount(2, $result);
        $this->assertCount(1, $this->logs);
    }

    public function testTheWidgetIsNotRequestedWithoutScalapayOption()
    {
        unset($this->payment_options['scalapay']);
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertCount(1, $result);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function paymentOption($name)
    {
        return [
            'name' => $name,
            'logo' => $name . '.svg',
            'callToActionText' => 'Pay with ' . $name,
            'moduleName' => 'payplug',
            'inputs' => [
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => $name,
                ],
            ],
            'action' => 'dispatcher',
        ];
    }
}
