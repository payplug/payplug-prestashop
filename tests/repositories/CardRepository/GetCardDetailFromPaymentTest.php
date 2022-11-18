<?php

namespace PayPlug\tests\repositories\CardRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetCardDetailFromPaymentTest extends BaseCardRepository
{
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->payment = PaymentMock::getOneClick();
    }

    public function invalidDataProvider()
    {
        // invalid int $customerId
        yield [42];
        yield [null];
        yield [false];
        yield ['I am a string!'];
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $customerId
     * @param $payplugCardId
     * @param $companyId
     * @param mixed $payment
     */
    public function testWithInvalidParams($payment)
    {
        $this->assertSame(
            [],
            $this->repo->getCardDetailFromPayment($payment)
        );
    }

    public function testWithValidResource()
    {
        $this->assertSame(
            [
                'last4' => '0001',
                'country' => 'FR',
                'exp_year' => 2030,
                'exp_month' => 9,
                'brand' => 'CB',
            ],
            $this->repo->getCardDetailFromPayment($this->payment)
        );
    }
}
