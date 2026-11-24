<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class savePaymentMethodsTest extends BaseConfigurationAction
{
    private $modal_translation;

    public function setUp(): void
    {
        parent::setUp();

        $this->modal_translation = [
            'confirmation' => [
                'text' => 'modal.confirmation.text',
                'submit' => 'modal.confirmation.submit',
            ],
        ];
    }

    public function testAnErrorIsReturnedWithoutInitializingTheApiWhenThePaymentMethodsCannotBeStored()
    {
        $this->configuration_class->shouldReceive('set')
            ->with('payment_methods', \Mockery::any())
            ->andReturn(false);
        $this->api_service->shouldNotReceive('initialize');

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred while register enable_scalapay',
                ],
            ],
            $this->action->savePaymentMethods($this->getDatas(), $this->modal_translation)
        );
    }

    public function testThePaymentMethodsAreStoredAndTheApiIsInitializedForTheLiveMode()
    {
        $stored = null;
        $this->configuration_class->shouldReceive('set')
            ->with('payment_methods', \Mockery::any())
            ->andReturnUsing(function ($key, $value) use (&$stored) {
                $stored = json_decode($value, true);

                return true;
            });
        $this->configuration_class->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn('0');
        $this->api_service->shouldReceive('initialize')->once()->with(true)->andReturn(true);

        $this->assertSame(
            [
                'success' => true,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.confirmation.text',
                    'close' => 'modal.confirmation.submit',
                ],
                'send_telementry' => true,
            ],
            $this->action->savePaymentMethods($this->getDatas(), $this->modal_translation)
        );
        $this->assertTrue($stored['standard']);
        $this->assertTrue($stored['scalapay']);
        $this->assertFalse($stored['oney']);
    }

    /**
     * @return \stdClass
     */
    private function getDatas()
    {
        $datas = new \stdClass();
        $datas->enable_standard = true;
        $datas->enable_scalapay = true;

        return $datas;
    }
}
