<?php

namespace PayPlug\tests\application\adapter\PrestashopAdapter17;

/**
 * @group unit
 * @group adapter
 * @group prestashop_adapter_17
 */
class displayPaymentOptionTest extends BasePrestashopAdapter17
{
    private $payment_options;

    public function setUp(): void
    {
        parent::setUp();

        $this->payment_options = [
            'standard' => [
                'name' => 'standard',
                'logo' => 'logo.png',
                'callToActionText' => 'Pay',
                'moduleName' => 'payplug',
                'inputs' => [
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'standard',
                    ],
                ],
            ],
        ];
    }

    /**
     * Scenario 1: EUR cart, embedded_mode=integrated, both features valid,
     * 'standard' present -> the existing setIntegratedPaymentOption() path
     * is taken (unchanged regression behavior for the EUR case).
     */
    public function testIntegratedPathIsTakenForEurCartWithIntegratedMode()
    {
        $this->context->currency->iso_code = 'EUR';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_integrated')->andReturn(true);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')->andReturn('integrated');

        $this->adapter->shouldReceive('setIntegratedPaymentOption')
            ->once()
            ->with($this->payment_options)
            ->andReturnUsing(function ($payment_options) {
                $payment_options['standard']['tpl'] = 'integrated_payment.tpl';

                return $payment_options;
            });
        $this->adapter->shouldReceive('setHostedFieldsPaymentOption')->never();

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertCount(1, $result);
    }

    /**
     * Scenario 2 (regression guard): a non-EUR cart with embedded_mode=integrated
     * must NOT take the integrated path anymore - this is the exact bug this
     * ticket fixes (today, before this ticket, a non-EUR cart would incorrectly
     * get 'integrated').
     */
    public function testIntegratedPathIsNotTakenWhenCurrencyIsNotEur()
    {
        $this->context->currency->iso_code = 'USD';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_integrated')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')->andReturn(false);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')->andReturn('integrated');

        $this->adapter->shouldReceive('setIntegratedPaymentOption')->never();
        $this->adapter->shouldReceive('setHostedFieldsPaymentOption')->never();

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertSame('logo.png', $result[0]->getLogo());
        $this->assertSame('standard', $result[0]->getInputs()['method']['value']);
    }

    /**
     * Scenario 3: USD cart, feature_hosted_fields and feature_standard valid,
     * Configuration::hosted_fields has an identifier for 'usd' -> the new
     * setHostedFieldsPaymentOption() path is taken.
     */
    public function testHostedFieldsPathIsTakenWhenIdentifierIsConfiguredForCurrency()
    {
        $this->context->currency->iso_code = 'USD';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_integrated')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')->andReturn(true);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')->andReturn('redirect');
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{"usd":"ident_42"}');

        $this->adapter->shouldReceive('setIntegratedPaymentOption')->never();
        $this->adapter->shouldReceive('setHostedFieldsPaymentOption')
            ->once()
            ->with($this->payment_options)
            ->andReturnUsing(function ($payment_options) {
                $payment_options['standard']['tpl'] = 'hosted_fields.tpl';
                $payment_options['standard']['action'] = 'javascript:payplugModule.hosted_fields.form.validate();';
                $payment_options['standard']['inputs']['method']['value'] = 'hosted_fields';

                return $payment_options;
            });

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertSame(
            'javascript:payplugModule.hosted_fields.form.validate();',
            $result[0]->getAction()
        );
        $this->assertSame('hosted_fields', $result[0]->getInputs()['method']['value']);
    }

    /**
     * Scenario 4: USD cart, feature_hosted_fields valid, but
     * Configuration::hosted_fields has no identifier for 'usd' -> neither
     * branch taken, 'standard' passes through completely unchanged.
     */
    public function testNeitherPathIsTakenWhenNoIdentifierConfiguredForCurrency()
    {
        $this->context->currency->iso_code = 'USD';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_integrated')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')->andReturn(true);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')->andReturn('redirect');
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{}');

        $this->adapter->shouldReceive('setIntegratedPaymentOption')->never();
        $this->adapter->shouldReceive('setHostedFieldsPaymentOption')->never();

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertSame('logo.png', $result[0]->getLogo());
        $this->assertSame('Pay', $result[0]->getCallToActionText());
        $this->assertSame('payplug', $result[0]->getModuleName());
        $this->assertSame('standard', $result[0]->getInputs()['method']['value']);
        $this->assertNull($result[0]->getAction());
    }

    /**
     * Scenario 5: feature_hosted_fields is not valid at all, regardless of
     * currency -> the hosted_fields branch is never taken.
     */
    public function testHostedFieldsPathIsNeverTakenWhenFeatureNotValid()
    {
        $this->context->currency->iso_code = 'USD';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_integrated')->andReturn(true);
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')->andReturn(false);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')->andReturn('redirect');

        $this->adapter->shouldReceive('setIntegratedPaymentOption')->never();
        $this->adapter->shouldReceive('setHostedFieldsPaymentOption')->never();

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertSame('logo.png', $result[0]->getLogo());
    }

    /**
     * Scenario 6: feature_standard is not valid -> neither branch taken,
     * matching the existing guard's feature_standard check.
     */
    public function testNeitherPathIsTakenWhenFeatureStandardNotValid()
    {
        $this->context->currency->iso_code = 'EUR';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(false);

        $this->adapter->shouldReceive('setIntegratedPaymentOption')->never();
        $this->adapter->shouldReceive('setHostedFieldsPaymentOption')->never();

        $result = $this->adapter->displayPaymentOption($this->payment_options);

        $this->assertSame('logo.png', $result[0]->getLogo());
    }
}
