<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class saveActionTest extends BaseConfigurationAction
{
    public function setUp(): void
    {
        parent::setUp();

        $this->module->shouldReceive([
            'enable' => true,
        ]);
    }

    public function invalidObjectFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $datas
     */
    public function testWhenGivenDataIsInvalidFormat($datas)
    {
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenGivenActionIsEmpty()
    {
        $datas = new \stdClass();
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenGivenActionIsInvalid()
    {
        $datas = new \stdClass();
        $datas->action = 'test';
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenNoApplepayDisplayIsSelected()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->enable_applepay_cart = false;
        $datas->enable_applepay_checkout = false;
        $datas->enable_applepay_product = false;
        $datas->applepay_carriers = [];

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.applepay.display.text',
                    'close' => 'modal.applepay.display.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenApplepayCarriersIsEmpty()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->enable_applepay_cart = true;
        $datas->applepay_carriers = [];

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.applepay.carrier.text',
                    'close' => 'modal.applepay.carrier.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenConfigurationCannotBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->applepay_carriers = ['1', '2', '4'];
        $datas->enable_applepay_cart = true;
        $datas->enable_standard = 1;

        $this->configuration->shouldReceive([
            'updateValue' => false,
        ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred while register applepay_carriers',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenOneyAmountIsOutsideTheAccountRangeItIsNotPersisted()
    {
        $stored = &$this->stubOneySave();

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_oney = true;
        $datas->oney_min_amounts = 1;

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertArrayNotHasKey('oney_custom_min_amounts', $stored);
    }

    public function testWhenOneyAmountIsInsideTheAccountRangeItIsPersisted()
    {
        $stored = &$this->stubOneySave();

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_oney = true;
        $datas->oney_min_amounts = 200;

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertSame('EUR:20000', $stored['oney_custom_min_amounts']);
    }

    public function testOneyAmountIsConvertedInTheOneyLimitsCurrency()
    {
        $stored = &$this->stubOneySave('JPY');

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_oney = true;
        $datas->oney_min_amounts = 20000;

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertSame('JPY:20000', $stored['oney_custom_min_amounts']);
    }

    public function testWhenConfigurationCanBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->applepay_carriers = ['1', '2', '4'];
        $datas->enable_applepay_cart = true;
        $datas->payplug_standard = 1;

        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);

        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }

    public function testWhenIdentifierIsSaved()
    {
        $this->currencies = [['iso_code' => 'USD']];

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier_usd = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{}');

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['usd' => 'ident_42']))
            ->once()
            ->andReturn(true);

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }

    public function testWhenIdentifierIsSavedWithoutExistingHostedFields()
    {
        $this->currencies = [['iso_code' => 'USD']];

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier_usd = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn(false);

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['usd' => 'ident_42']))
            ->once()
            ->andReturn(true);

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }

    public function testWhenHostedFieldsCannotBeUpdated()
    {
        $this->currencies = [['iso_code' => 'USD']];

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier_usd = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{}');

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', \Mockery::any())
            ->andReturn(false);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.hosted_fields.identifier.text',
                    'close' => 'modal.error.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenIdentifierIsSavedItIsTrimmed()
    {
        $this->currencies = [['iso_code' => 'USD']];

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier_usd = "  ident_42 \n";

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{}');

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['usd' => 'ident_42']))
            ->once()
            ->andReturn(true);

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertTrue($this->action->saveAction($datas)['success']);
    }

    public function testWhenIdentifierIsSavedItKeepsOtherCurrencies()
    {
        $this->currencies = [['iso_code' => 'USD']];

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier_usd = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn(json_encode(['gbp' => 'ident_gbp', 'usd' => 'ident_old']));

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['gbp' => 'ident_gbp', 'usd' => 'ident_42']))
            ->once()
            ->andReturn(true);

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertTrue($this->action->saveAction($datas)['success']);
    }

    public function testWhenEurIdentifierIsGivenItIsNotSaved()
    {
        $this->currencies = [['iso_code' => 'EUR'], ['iso_code' => 'USD']];

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier_eur = 'ident_eur';
        $datas->payplug_identifier_usd = 'ident_42';

        $hosted_fields = '{}';
        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturnUsing(function () use (&$hosted_fields) {
                return $hosted_fields;
            });

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', \Mockery::any())
            ->andReturnUsing(function ($key, $value) use (&$hosted_fields) {
                $hosted_fields = $value;

                return true;
            });

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertSame(json_encode(['usd' => 'ident_42']), $hosted_fields);
    }

    /**
     * @description Wire up what the Oney branch of saveAction() needs: the account's
     *              authorized range and the euro/cent conversion.
     *
     * @param string $currency currency of the Oney limits
     *
     * @return array the configuration keys saveAction() persisted, by key
     */
    private function &stubOneySave($currency = 'EUR')
    {
        $oney = \Mockery::mock('OneyPaymentMethod');
        $oney->shouldReceive('getOneyPriceLimit')
            ->with(false)
            ->andReturn([
                'min' => 10000,
                'max' => 300000,
            ]);
        $oney->shouldReceive('setCustomOneyLimit')
            ->andReturnUsing(function ($amount) use ($currency) {
                return $currency . ':' . (int) $amount;
            });
        $oney->shouldReceive('getOneyLimitsCurrency')
            ->andReturn($currency);

        $payment_method_class = \Mockery::mock('PaymentMethodClass');
        $payment_method_class->shouldReceive('getPaymentMethod')
            ->with('oney')
            ->andReturn($oney);
        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $payment_method_class,
        ]);

        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $from_cents, $currency) {
                return (int) round($amount * ('JPY' === $currency ? 1 : 100));
            });
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);

        $this->dependencies->shouldReceive([
            'getValidators' => [
                'payment' => \Mockery::mock(\PayPlug\src\utilities\validators\paymentValidator::class)->makePartial(),
            ],
        ]);

        $stored = [];
        $this->configuration_class->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) use (&$stored) {
                $stored[$key] = $value;

                return true;
            });

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);

        return $stored;
    }

    private function mockSuccessfulHostedFieldsSave()
    {
        $this->configuration_class->shouldReceive('set')
            ->with('payment_methods', \Mockery::any())
            ->andReturn(true);

        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);

        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);
    }
}
