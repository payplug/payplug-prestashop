<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 */
class getResourceDetailTest extends BaseInstallmentPaymentMethod
{
    public function testAmountsAreConvertedInTheInstallmentPlanCurrency()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(['currency' => 'JPY']),
                'schedule' => [
                    ['resource' => null, 'amount' => 10440, 'date' => '2021-04-04'],
                ],
            ],
            'getPaymentStatus' => [
                'id_status' => 6,
                'code' => 'on_going',
            ],
            'updateInstallmentSchedules' => true,
        ]);
        $this->payment_repository->shouldReceive('getBy')
            ->andReturn(['schedules' => 'stored']);
        $this->plugin->shouldReceive('getPaymentRepository')
            ->andReturn($this->payment_repository);
        $this->tools_adapter->shouldReceive('tool')->andReturnUsing(function ($method, $arg) {
            return $arg;
        });

        $detail = $this->class->getResourceDetail('inst_1gDmrsoMdIAJfV2MxzkBPX');

        $this->assertSame('JPY', $detail['currency']);
        $this->amountHelper->shouldHaveReceived('convertAmount')
            ->with(10440, true, 'JPY')
            ->twice();
        $this->amountHelper->shouldNotHaveReceived('convertAmount', [\Mockery::any(), true, '']);
    }

    public function testTotalAmountIsTheSumOfAllSchedules()
    {
        $this->stubInstallmentPlan('EUR', [
            [
                'resource' => PaymentMock::getStandard(['id' => 'pay_paid_schedule']),
                'amount' => 10440,
                'date' => '2021-03-05',
            ],
            ['resource' => null, 'amount' => 10440, 'date' => '2021-04-04'],
            ['resource' => null, 'amount' => 10440, 'date' => '2021-05-04'],
        ]);

        $detail = $this->class->getResourceDetail('inst_1gDmrsoMdIAJfV2MxzkBPX');

        $this->assertSame(313.2, $detail['amount']);
        $this->assertSame(104.4, $detail['payment_list'][1]['amount']);
        $this->assertSame(104.4, $detail['payment_list'][2]['amount']);
    }

    public function testAmountsAreFormattedInTheInstallmentPlanCurrencyNotTheContextOne()
    {
        $this->context->currency->iso_code = 'EUR';
        $this->stubInstallmentPlan('JPY', [
            ['resource' => null, 'amount' => 10440, 'date' => '2021-04-04'],
        ]);

        $this->class->getResourceDetail('inst_1gDmrsoMdIAJfV2MxzkBPX');

        $this->price_adapter->shouldHaveReceived('formatPrice')
            ->with(\Mockery::any(), 'JPY')
            ->times(3);
        $this->price_adapter->shouldNotHaveReceived('formatPrice', [\Mockery::any(), 'EUR']);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider missingCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testAmountsAreFormattedInTheContextCurrencyWhenThePlanHasNone($currency)
    {
        $this->context->currency->iso_code = 'CHF';
        $this->stubInstallmentPlan($currency, [
            ['resource' => null, 'amount' => 10440, 'date' => '2021-04-04'],
        ]);

        $this->class->getResourceDetail('inst_1gDmrsoMdIAJfV2MxzkBPX');

        $this->price_adapter->shouldHaveReceived('formatPrice')
            ->with(\Mockery::any(), 'CHF')
            ->times(3);
        $this->price_adapter->shouldNotHaveReceived('formatPrice', [\Mockery::any(), '']);
        $this->amountHelper->shouldNotHaveReceived('convertAmount', [\Mockery::any(), true, 'CHF']);
        $this->addToAssertionCount(1);
    }

    public function missingCurrencyProvider()
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    /**
     * @param string|null $currency
     * @param array $schedules
     */
    private function stubInstallmentPlan($currency, $schedules)
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(['currency' => $currency]),
                'schedule' => $schedules,
            ],
            'getPaymentStatus' => [
                'id_status' => 6,
                'code' => 'on_going',
            ],
            'updateInstallmentSchedules' => true,
        ]);
        $this->payment_method->shouldReceive([
            'getResourceDetail' => [
                'id' => 'pay_paid_schedule',
                'amount' => 104.4,
                'refund' => [
                    'refunded' => 0,
                    'available' => 104.4,
                ],
            ],
        ]);
        $this->payment_repository->shouldReceive('getBy')
            ->andReturn(['schedules' => 'stored']);
        $this->plugin->shouldReceive('getPaymentRepository')
            ->andReturn($this->payment_repository);
        $this->tools_adapter->shouldReceive('tool')->andReturnUsing(function ($method, $arg) {
            return $arg;
        });
    }
}
