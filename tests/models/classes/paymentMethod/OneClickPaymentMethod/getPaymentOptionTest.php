<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionTest extends BaseOneClickPaymentMethod
{
    private $card;

    protected function setUp()
    {
        parent::setUp();

        $this->card = \Mockery::mock('CardAction');
        $this->plugin->shouldReceive([
            'getCardAction' => $this->card,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenNoCustomerCardsFound()
    {
        $payment_options = [];
        $this->card->shouldReceive([
            'renderList' => [],
        ]);
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenCustomerCardsFound()
    {
        $payment_options = [];
        $this->card->shouldReceive([
            'renderList' => [
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
        $this->helpers['amount']
            ->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
        $this->classe->shouldReceive([
            'getCardBrand' => 'CB',
        ]);
        $this->tools_adapter
            ->shouldReceive([
                'tool' => '',
                'strtolower' => 'brand',
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
