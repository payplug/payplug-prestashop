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
class getLinesItemsTest extends BaseApplepayPaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $carriers
     */
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($carriers)
    {
        $this->assertSame([], $this->class->getLinesItems($carriers));
    }

    public function testWhenNoCarriersIsGiven()
    {
        $this->cart_adapter->shouldReceive([
            'getOrderTotalWithoutShipping' => 42,
            'getOrderTotalDiscount' => 0,
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($a, $b, $c) {
                if ('ps_round' == $a) {
                    return (int) $b;
                }

                return '';
            });
        $expected = [
            [
                'label' => 'paymentmethods.applepay.modal.subtotal',
                'type' => 'final',
                'amount' => (int) 42,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.tva',
                'type' => 'final',
                'amount' => (int) 0,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.delivery_cost',
                'type' => 'final',
                'amount' => (int) 0,
            ],
        ];
        $this->assertSame($expected, $this->class->getLinesItems());
    }

    public function testWhenCartHasDiscount()
    {
        $subtotal = 1;
        $discount = 3;
        $this->cart_adapter->shouldReceive([
            'getOrderTotalWithoutShipping' => $subtotal,
            'getOrderTotalDiscount' => $discount,
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($a, $b, $c) {
                if ('ps_round' == $a) {
                    return (int) $b;
                }

                return '';
            });
        $expected = [
            [
                'label' => 'paymentmethods.applepay.modal.subtotal',
                'type' => 'final',
                'amount' => $subtotal,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.tva',
                'type' => 'final',
                'amount' => 0,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.discount',
                'type' => 'final',
                'amount' => $discount * -1,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.delivery_cost',
                'type' => 'final',
                'amount' => 0,
            ],
        ];
        $this->assertSame($expected, $this->class->getLinesItems());
    }

    public function testWhenCarrierIsGiven()
    {
        $this->cart_adapter->shouldReceive([
            'getOrderTotalWithoutShipping' => 42,
            'getOrderTotalDiscount' => 0,
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($a, $b, $c) {
                if ('ps_round' == $a) {
                    return (int) $b;
                }

                return '';
            });
        $carriers = [
            [
                'identifier' => 1,
                'amount' => 42,
            ],
        ];
        $expected = [
            [
                'label' => 'paymentmethods.applepay.modal.subtotal',
                'type' => 'final',
                'amount' => (int) 42,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.tva',
                'type' => 'final',
                'amount' => (int) 0,
            ],
            [
                'label' => 'paymentmethods.applepay.modal.delivery_cost',
                'type' => 'final',
                'amount' => (int) 42,
            ],
        ];
        $this->assertSame($expected, $this->class->getLinesItems($carriers));
    }
}
