<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getAvailableCarriersTest extends BaseApplepayPaymentMethod
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_lang
     */
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($id_lang)
    {
        $this->assertSame([], $this->classe->getAvailableCarriers($id_lang));
    }

    public function testWhenNoCarriersAreSetted()
    {
        $id_lang = 42;
        $this->carrier_adapter
            ->shouldReceive([
                'getCarriers' => [],
            ]);
        $this->assertSame([], $this->classe->getAvailableCarriers($id_lang));
    }

    public function testWhenSettedCarrierIsNotConfigured()
    {
        $id_lang = 42;
        $this->carrier_adapter
            ->shouldReceive([
                'getCarriers' => [
                    [
                        'id_carrier' => 42,
                        'name' => 'carrier',
                    ],
                ],
            ]);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[]');
        $expected = [
            [
                'id_carrier' => 42,
                'name' => 'carrier',
                'checked' => false,
            ],
        ];

        $this->assertSame($expected, $this->classe->getAvailableCarriers($id_lang));
    }

    public function testWhenSettedCarrierIsConfigured()
    {
        $id_lang = 42;
        $this->carrier_adapter
            ->shouldReceive([
                'getCarriers' => [
                    [
                        'id_carrier' => 42,
                        'name' => 'carrier',
                    ],
                ],
            ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[42]');

        $expected = [
            [
                'id_carrier' => 42,
                'name' => 'carrier',
                'checked' => true,
            ],
        ];
        $this->assertSame($expected, $this->classe->getAvailableCarriers($id_lang));
    }
}
