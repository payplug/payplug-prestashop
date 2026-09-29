<?php

namespace PayPlug\tests\application\adapter\PrestashopAdapter17;

/**
 * @group unit
 * @group adapter
 * @group prestashop_adapter_17
 */
class setHostedFieldsPaymentOptionTest extends BasePrestashopAdapter17
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
                    'id_cart' => [
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => 4242,
                    ],
                ],
            ],
        ];

        $this->context->currency->iso_code = 'USD';
        $this->context->link->shouldReceive('getModuleLink')
            ->with('payplug', 'unified', ['action' => 'create'], true)
            ->andReturn('https://shop.example/module/payplug/unified?action=create');

        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{"usd":"ident_42"}');

        $this->routes->shouldReceive('getSourceUrl')
            ->andReturn([
                'applepay' => 'applepay.js',
                'embedded' => 'embedded.js',
                'integrated' => 'integrated.js',
                'oney' => 'oney.js',
                'hosted_fields' => 'https://payment.example/hosted-fields.min.js',
            ]);
    }

    public function testReturnsExpectedPaymentOptionShape()
    {
        $result = $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertSame('hosted_fields.tpl', $result['standard']['tpl']);
        $this->assertSame(
            'javascript:payplugModule.hosted_fields.form.validate();',
            $result['standard']['action']
        );
        $this->assertSame('hosted_fields', $result['standard']['inputs']['method']['value']);
        $this->assertSame('hosted_fields', $result['standard']['name']);
        $this->assertSame('logo.png', $result['standard']['logo']);
        $this->assertSame('payplug', $result['standard']['moduleName']);
        $this->assertSame('payplug hosted_fields', $result['standard']['extra_classes']);
    }

    public function testAssignsSmartyVariablesFromHostedFieldsRoutesAndIdentifier()
    {
        $assigned_vars = null;
        $this->context->smarty->shouldReceive('assign')
            ->once()
            ->andReturnUsing(function ($vars) use (&$assigned_vars) {
                $assigned_vars = $vars;
            });

        $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertSame(
            'https://payment.example/hosted-fields.min.js',
            $assigned_vars['hosted_fields_js_url']
        );
        $this->assertSame('ident_42', $assigned_vars['hosted_fields_identifier']);
        $this->assertSame(
            json_encode(['cb', 'visa', 'mastercard']),
            $assigned_vars['hosted_fields_accepted_brands']
        );
        $this->assertSame(
            'https://shop.example/module/payplug/unified?action=create',
            $assigned_vars['hosted_fields_uhf_url']
        );
    }

    public function testAssignsTheSavedAliasesUsableForTheCartCurrencyAndIdentifier()
    {
        $aliases = [[
            'id_payplug_alias' => 1,
            'currency' => 'usd',
            'brand' => 'visa',
            'last4' => '0001',
            'expiry_date' => '12 / 29',
            'usable' => true,
        ]];
        $this->enableOneClick();
        $this->alias_action->shouldReceive('renderCheckoutList')->once()->with('USD', 'ident_42')->andReturn($aliases);

        $assigned_vars = null;
        $this->context->smarty->shouldReceive('assign')
            ->once()
            ->andReturnUsing(function ($vars) use (&$assigned_vars) {
                $assigned_vars = $vars;
            });

        $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertSame($aliases, $assigned_vars['saved_aliases']);
    }

    public function testAssignsNoSavedAliasesWithoutCallingTheAliasServiceWhenOneClickIsDisabled()
    {
        $this->alias_action->shouldReceive('renderCheckoutList')->never();

        $assigned_vars = null;
        $this->context->smarty->shouldReceive('assign')
            ->once()
            ->andReturnUsing(function ($vars) use (&$assigned_vars) {
                $assigned_vars = $vars;
            });

        $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertSame([], $assigned_vars['saved_aliases']);
        $this->assertFalse($assigned_vars['is_one_click_activated']);
    }

    public function testAssignsAnEmptySavedAliasListWhenTheCustomerHasNone()
    {
        $this->enableOneClick();
        $assigned_vars = null;
        $this->context->smarty->shouldReceive('assign')
            ->once()
            ->andReturnUsing(function ($vars) use (&$assigned_vars) {
                $assigned_vars = $vars;
            });

        $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertSame([], $assigned_vars['saved_aliases']);
    }

    public function testFetchesTheHostedFieldsAdditionalInformationTemplate()
    {
        $this->config_class->shouldReceive('fetchTemplate')
            ->once()
            ->with('checkout/payment/hosted_fields.tpl')
            ->andReturn('<rendered-template>');

        $result = $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertSame('<rendered-template>', $result['standard']['additionalInformation']);
    }

    public function testReturnsEmptyArrayWhenPaymentOptionsIsEmpty()
    {
        $result = $this->adapter->setHostedFieldsPaymentOption([]);

        $this->assertSame([], $result);
    }

    public function testPreservesIdCartInputFromStandardPaymentOption()
    {
        $result = $this->adapter->setHostedFieldsPaymentOption($this->payment_options);

        $this->assertArrayHasKey('id_cart', $result['standard']['inputs']);
        $this->assertSame(
            [
                'name' => 'id_cart',
                'type' => 'hidden',
                'value' => 4242,
            ],
            $result['standard']['inputs']['id_cart']
        );
    }

    private function enableOneClick()
    {
        $this->config_class->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn(json_encode(['one_click' => true, 'deferred' => false]));
    }
}
