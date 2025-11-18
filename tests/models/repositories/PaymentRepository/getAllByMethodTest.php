<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 */
class getAllByMethodTest extends BasePaymentRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method_name
     */
    public function testWhenGivenMethodIsInvalidIntegerFormat($method_name)
    {
        $this->assertSame([], $this->repository->getAllByMethod($method_name));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $method_name = 'method';
        $this
            ->repository->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame([], $this->repository->getAllByMethod($method_name));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $method_name = 'method';
        $payment = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty12345',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
            'schedules' => '',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this
            ->repository->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => $payment,
            ]);

        $this->assertSame($payment, $this->repository->getAllByMethod($method_name));
    }
}
