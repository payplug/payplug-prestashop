<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class getByCartTest extends BasePaymentRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWhenGivenIdCountryIsInvalidIntegerFormat($id_cart)
    {
        $this->assertSame([], $this->repository->getByCart($id_cart));
    }

    /**
     * @group debug
     */
    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_cart = 42;
        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame([], $this->repository->getByCart($id_cart));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_cart = 42;
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

        $this->assertSame($payment, $this->repository->getByCart($id_cart));
    }
}
