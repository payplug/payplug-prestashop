<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class getPaymentOptionTest extends BaseStandardPaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getImgLang' => 'fr',
        ]);
        $this->dependencies->configClass = $configClass;
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->class->getPaymentOption($payment_options));
    }

    public function testWhenNoSavedCardOption()
    {
        $payment_options = $this->class->getPaymentOption([]);

        $this->assertSame(
            'paymentmethods.standard.call_to_action',
            $payment_options['standard']['callToActionText']
        );
        $this->assertSame('payplug default', $payment_options['standard']['extra_classes']);
    }

    public function testWhenSavedCardOptionWithEurCart()
    {
        $payment_options = $this->class->getPaymentOption([
            'one_click_1' => ['name' => 'one_click'],
        ]);

        $this->assertSame(
            'paymentmethods.standard.has_saved_card',
            $payment_options['standard']['callToActionText']
        );
    }

    public function testWhenSavedCardOptionWithNonEurCart()
    {
        $this->context->currency->iso_code = 'USD';
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR;USD');

        $payment_options = $this->class->getPaymentOption([
            'one_click_1' => ['name' => 'one_click'],
        ]);

        $this->assertSame(
            'paymentmethods.standard.call_to_action',
            $payment_options['standard']['callToActionText']
        );
    }

    public function testWhenCartCurrencyIsServedByTheHostedFields()
    {
        $this->context->currency->iso_code = 'USD';
        $this->setHostedFieldsConfiguration(true);

        $payment_options = $this->class->getPaymentOption([]);

        $this->assertSame(
            'paymentmethods.standard.call_to_action',
            $payment_options['standard']['callToActionText']
        );
        $this->assertSame('payplug default', $payment_options['standard']['extra_classes']);
    }

    public function testWhenCartCurrencyIsNeitherSupportedNorServedByTheHostedFields()
    {
        $payment_options = ['one_click_1' => ['name' => 'one_click']];
        $this->context->currency->iso_code = 'USD';
        $this->setHostedFieldsConfiguration(false);

        $this->assertSame($payment_options, $this->class->getPaymentOption($payment_options));
    }

    private function setHostedFieldsConfiguration($identifier_configured)
    {
        $this->dependencies->configClass->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')
            ->andReturn(true);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('integrated');
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')
            ->with('USD')
            ->andReturn($identifier_configured);
        $this->dependencies->shouldReceive('loadAdapterPresta')
            ->andReturn($prestashop_adapter);
    }
}
