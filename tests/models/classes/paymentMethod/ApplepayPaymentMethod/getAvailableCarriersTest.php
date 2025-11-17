<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
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
        $this->assertSame([], $this->class->getAvailableCarriers($id_lang));
    }

    public function testWhenNoCarriersAreSetted()
    {
        $id_lang = 42;
        $this->carrier_adapter->shouldReceive([
            'getAllActiveCarriers' => [],
        ]);
        $this->assertSame([], $this->class->getAvailableCarriers($id_lang));
    }

    public function testWhenSettedCarrierIsNotConfigured()
    {
        $id_lang = 42;
        $this->carrier_adapter->shouldReceive([
            'getAllActiveCarriers' => [
                [
                    'id_carrier' => 42,
                    'name' => 'carrier',
                ],
            ],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[]');
        $expected = [
            [
                'id_carrier' => 42,
                'name' => 'carrier',
                'checked' => false,
            ],
        ];

        $this->assertSame($expected, $this->class->getAvailableCarriers($id_lang));
    }

    public function testWhenSettedCarrierIsConfigured()
    {
        $id_lang = 42;
        $this->carrier_adapter->shouldReceive([
            'getAllActiveCarriers' => [
                [
                    'id_carrier' => 42,
                    'name' => 'carrier',
                ],
            ],
        ]);

        $this->configuration->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[42]');

        $expected = [
            [
                'id_carrier' => 42,
                'name' => 'carrier',
                'checked' => true,
            ],
        ];
        $this->assertSame($expected, $this->class->getAvailableCarriers($id_lang));
    }
}
