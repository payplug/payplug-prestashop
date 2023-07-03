<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group dev
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionTest extends BaseOneClickPaymentMethod
{
    private $card;

    protected function setUp()
    {
        parent::setUp();

        $this->card = \Mockery::mock('Card');
        $this->plugin->shouldReceive([
            'getCard' => $this->card,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function atestWhenGivenPaymentOptionsIsNotValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function atestWhenNoCustomerCardsFound()
    {
        $payment_options = [];
        $this->card->shouldReceive([
            'getByCustomer' => [],
        ]);
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenCustomerCardsFound()
    {
        $payment_options = [];
        $this->card->shouldReceive([
            'getByCustomer' => [
                [
                    'id_payplug_card' => '1',
                    'brand' => 'CB',
                    'last4' => '4242',
                    'expiry_date' => '12 / 2050',
                ],
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');

        $this->classe->shouldReceive([
            'getCardBrand' => 'CB',
        ]);

        $tools = \Mockery::mock('Tools');
        $tools
            ->shouldReceive([
                'tool' => '',
            ]);
        $this->plugin->shouldReceive([
            'getTools' => $tools,
        ]);

        $expected = [
            'one_click_1' => [
                'name' => 'one_click',
                'inputs' => [
                    'pc' => [
                        'name' => 'pc',
                        'type' => 'hidden',
                        'value' => 1,
                    ],
                    'pay' => [
                        'name' => 'pay',
                        'type' => 'hidden',
                        'value' => '1',
                    ],
                    'id_cart' => [
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => 1,
                    ],
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'one_click',
                    ],
                ],
                'extra_classes' => 'one_click',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/svg/checkout/standard/.svg',
                'callToActionText' => 'paymentmethods.one_click.call_to_action',
                'action' => 'link',
                'moduleName' => 'payplug',
            ],
        ];

        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }
}
