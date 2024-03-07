<?php

namespace PayPlug\tests\actions\CartAction;

/**
 * @group unit
 * @group action
 * @group renderer_action
 *
 * @runTestsInSeparateProcesses
 */
class renderApplePayCartCheckoutTest extends BaseCartAction
{
    /**
     * @description  test when browser is not safari
     */
    public function testWhenApplePayCartWhenBrowserIsNotSafari()
    {
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive(
            [
                'getName' => 'browser',
            ]
        );
        $this->plugin->shouldReceive(
            [
                'getBrowser' => $browser,
            ]
        );
        $this->browser_validator->shouldReceive(
            [
                'isApplePayCompatible' => [
                    'result' => false,
                    'message' => 'This browser is not applepay compatible.',
                ],
            ]
        );

        $this->assertFalse($this->action->renderApplePayCartCheckout());
    }

    /**
     * @description  test when returned carriers list is not compatible
     */
    public function testWithNoCompatibleCarriersproducts()
    {
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive(
            [
                'getName' => 'browser',
            ]
        );
        $this->plugin->shouldReceive(
            [
                'getBrowser' => $browser,
            ]
        );
        $this->browser_validator->shouldReceive(
            [
                'isApplePayCompatible' => [
                    'result' => true,
                    'message' => '',
                ],
            ]
        );

        $this->context->cart
            ->shouldReceive(
                [
                    'getDeliveryOptionList' => [
                        7 => [
                            '1,' => [
                                'carrier_list' => [
                                    7 => [
                                        'price_with_tax' => 0,
                                        'price_without_tax' => 0,
                                        'package_list' => [0],
                                        'product_list' => [
                                            0 => [/* items */],
                                        ],
                                    ],
                                ],
                                'is_best_price' => true,
                                'is_best_grade' => true,
                                'unique_carrier' => true,
                                'total_price_with_tax' => 0,
                                'total_price_without_tax' => 0,
                                'is_free' => true,
                                'position' => 0,
                            ],
                        ],
                    ],
                ]
            );

        $this->configuration
            ->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn(' {"0":1,"1":2,"2":3}');

        $this->assertFalse($this->action->renderApplePayCartCheckout());
    }

    /**
     * @description test when returned carriers list is empty.
     */
    public function testWithEmptyCarriersList()
    {
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive(
            [
                'getName' => 'browser',
            ]
        );
        $this->plugin->shouldReceive(
            [
                'getBrowser' => $browser,
            ]
        );
        $this->browser_validator->shouldReceive(
            [
                'isApplePayCompatible' => [
                    'result' => true,
                    'message' => '',
                ],
            ]
        );

        $this->context->cart
            ->shouldReceive(
                [
                    'getDeliveryOptionList' => [],

                ]
            );

        $this->configuration
            ->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn(' {"0":1,"1":2,"2":3}');

        $this->assertFalse($this->action->renderApplePayCartCheckout());
    }

    /**
     * @description test when the cart contains one carrier with a value of 0
     */
    public function checkIfCartContentIsNotSuitableToAnyCarrier()
    {
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive(
            [
                'getName' => 'browser',
            ]
        );
        $this->plugin->shouldReceive(
            [
                'getBrowser' => $browser,
            ]
        );
        $this->browser_validator->shouldReceive(
            [
                'isApplePayCompatible' => [
                    'result' => true,
                    'message' => '',
                ],
            ]
        );

        $this->context->cart
            ->shouldReceive(
                [
                    'getDeliveryOptionList' => [
                        7 => [
                            '0' => [
                                'carrier_list' => [
                                    0 => [
                                        'price_with_tax' => 0,
                                        'price_without_tax' => 0,
                                        'package_list' => [0],
                                        'product_list' => [
                                            0 => [/* items */],
                                        ],
                                    ],
                                ],
                                'is_best_price' => true,
                                'is_best_grade' => true,
                                'unique_carrier' => true,
                                'total_price_with_tax' => 0,
                                'total_price_without_tax' => 0,
                                'is_free' => true,
                                'position' => 0,
                            ],
                        ],
                    ],

                ]
            );

        $this->configuration
            ->shouldReceive('getValue')
            ->with('applepay_carriers')
            ->andReturn(' {"0":1,"1":2,"2":3}');

        $this->assertFalse($this->action->renderApplePayCartCheckout());
    }
}
