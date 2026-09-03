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
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{}');

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['identifier' => 'ident_42']))
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
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn(false);

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['identifier' => 'ident_42']))
            ->once()
            ->andReturn(true);

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }

    public function testWhenSubmerchantIdIsSaved()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_submerchant_id = 'sub_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{}');

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['submerchant_id' => 'sub_42']))
            ->once()
            ->andReturn(true);

        $this->mockSuccessfulHostedFieldsSave();

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }

    public function testWhenIdentifierIsMergedWithExistingHostedFields()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier = 'ident_42';

        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{"submerchant_id":"existing_sub"}');

        $this->configuration_class->shouldReceive('set')
            ->with('hosted_fields', json_encode(['submerchant_id' => 'existing_sub', 'identifier' => 'ident_42']))
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
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;
        $datas->payplug_identifier = 'ident_42';

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
                    'message' => 'An error has occurred while register payplug_identifier',
                ],
            ],
            $this->action->saveAction($datas)
        );
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
