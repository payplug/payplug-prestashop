<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

use PayPlug\src\models\repositories\PaymentRepository;
use PayPlug\tests\models\repositories\BaseRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class getByIdPaymentTest extends BaseRepository
{
    protected function setUp()
    {
        $this->repository = \Mockery::mock(PaymentRepository::class)->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $pay_id
     */
    public function testWhenGivenIdCountryIsInvalidIntegerFormat($pay_id)
    {
        $this->assertSame([], $this->repository->getByIdPayment($pay_id));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $pay_id = 'pay_azerty12345';
        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame([], $this->repository->getByIdPayment($pay_id));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $pay_id = 'pay_azerty12345';
        $payment = [
            'id_payplug_payment' => 42,
            'id_payment' => 'pay_azerty12345',
            'payment_method' => 'standard',
            'payment_url' => 'https://secure-qa.payplug.com/pay/azerty12345',
            'payment_return_url' => 'https://www.my-ecommerce.com/fr/module/payplug/validation?ps=1&cartid=42',
            'id_cart' => 42,
            'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
            'authorized_at' => 0,
            'is_paid' => 0,
            'is_pending' => 0,
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => $payment,
            ]);

        $this->assertSame($payment, $this->repository->getByIdPayment($pay_id));
    }
}
