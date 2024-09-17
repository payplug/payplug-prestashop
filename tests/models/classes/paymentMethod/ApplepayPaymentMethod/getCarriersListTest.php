<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getCarriersListTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoCarriersConfigured()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[]');

        $this->assertSame([], $this->class->getCarriersList());
    }

    public function testWhenNoCartDeliveryOptions()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[1,2,3]');

        $this->cart_adapter->shouldReceive([
            'getDeliveryOptionList' => [],
        ]);

        $this->assertSame([], $this->class->getCarriersList());
    }

    public function testWhenConfiguredCarriersDoesntMatchWithDeliveryOption()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[1,2,3]');

        $delivery_options = [
            [
                [
                    'carrier_list' => [
                        [
                            4 => [
                                'name' => 'carrier 4',
                            ],
                            5 => [
                                'name' => 'carrier 5',
                            ],
                            6 => [
                                'name' => 'carrier 6',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->cart_adapter->shouldReceive([
            'getDeliveryOptionList' => $delivery_options,
        ]);

        $this->assertSame([], $this->class->getCarriersList());
    }

    public function testWhenConfiguredCarriersMatchWithDeliveryOption()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn('[1,2,3,4]');
        $delivery_options = [
            [
                [
                    'carrier_list' => [
                        [
                            3 => [
                                'name' => 'carrier 3',
                            ],
                            4 => [
                                'name' => 'carrier 4',
                            ],
                            5 => [
                                'name' => 'carrier 5',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->cart_adapter->shouldReceive([
            'getDeliveryOptionList' => $delivery_options,
        ]);

        $this->assertSame([], $this->class->getCarriersList());
    }
}
