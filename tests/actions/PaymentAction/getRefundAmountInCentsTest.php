<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 */
class getRefundAmountInCentsTest extends BasePaymentAction
{
    private $amount_helper;

    public function setUp(): void
    {
        parent::setUp();

        $this->amount_helper = \Mockery::mock('AmountHelper');
        $this->amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $from_cents, $currency) {
                return (int) round($amount * ('JPY' === $currency ? 1 : 100));
            });
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($this->amount_helper);
    }

    public function testAmountIsConvertedInThePaymentCurrency()
    {
        $this->stubPayment(['currency' => 'JPY']);

        $this->assertSame(1500, $this->action->getRefundAmountInCents('1500', 'pay_azerty1234'));
        $this->amount_helper->shouldHaveReceived('convertAmount')->with('1500', false, 'JPY')->once();
        $this->payment_method->shouldHaveReceived('retrieve')->with('pay_azerty1234')->once();
    }

    public function testPaymentCurrencyWinsOverTheBackOfficeCurrency()
    {
        $this->context->get()->currency->iso_code = 'EUR';
        $this->stubPayment(['currency' => 'JPY']);

        $this->assertSame(1500, $this->action->getRefundAmountInCents('1500', 'pay_azerty1234'));
        $this->amount_helper->shouldNotHaveReceived('convertAmount', [\Mockery::any(), false, 'EUR']);
    }

    public function testTwoDecimalCurrencyAmountIsConvertedToCents()
    {
        $this->stubPayment(['currency' => 'EUR']);

        $this->assertSame(1050, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testInvalidResourceIdGivesZero($resource_id)
    {
        $this->assertSame(0, $this->action->getRefundAmountInCents('10.50', $resource_id));
        $this->amount_helper->shouldNotHaveReceived('convertAmount');
    }

    public function testUnknownStoredPaymentGivesZero()
    {
        $this->payment_repository->shouldReceive('getBy')
            ->with('resource_id', 'pay_azerty1234')
            ->andReturn([]);

        $this->assertSame(0, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->amount_helper->shouldNotHaveReceived('convertAmount');
        $this->logger->shouldHaveReceived('addLog')
            ->with('PaymentAction::getRefundAmountInCents - No stored payment found for given resource ID.', 'error')
            ->once();
    }

    public function testUnretrievablePaymentGivesZero()
    {
        $this->stubStoredPayment();
        $this->payment_method->shouldReceive('retrieve')
            ->andReturn(['result' => false]);

        $this->assertSame(0, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->amount_helper->shouldNotHaveReceived('convertAmount');
        $this->logger->shouldHaveReceived('addLog')
            ->with('PaymentAction::getResourceCurrency - The resource currency cannot be retrieved.', 'error')
            ->once();
    }

    /**
     * @dataProvider missingCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testPaymentWithoutCurrencyGivesZero($currency)
    {
        $this->stubPayment(['currency' => $currency]);

        $this->assertSame(0, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->amount_helper->shouldNotHaveReceived('convertAmount');
        $this->logger->shouldHaveReceived('addLog')
            ->with('PaymentAction::getResourceCurrency - The resource currency cannot be retrieved.', 'error')
            ->once();
    }

    public function testSamePaymentIsRetrievedOnceForSeveralConversions()
    {
        $this->stubPayment(['currency' => 'JPY']);

        $this->assertSame(1500, $this->action->getRefundAmountInCents('1500', 'pay_azerty1234'));
        $this->assertSame(500, $this->action->getRefundAmountInCents('500', 'pay_azerty1234'));
        $this->payment_method->shouldHaveReceived('retrieve')->with('pay_azerty1234')->once();
        $this->amount_helper->shouldHaveReceived('convertAmount')->with('500', false, 'JPY')->once();
    }

    public function testDifferentPaymentsAreRetrievedSeparately()
    {
        $this->stubPayment(['currency' => 'JPY']);
        $this->stubStoredPayment('pay_qwerty5678');
        $this->payment_method->shouldReceive('retrieve')
            ->with('pay_qwerty5678')
            ->andReturn([
                'result' => true,
                'resource' => PaymentMock::getStandard(['id' => 'pay_qwerty5678', 'currency' => 'EUR']),
            ]);

        $this->assertSame(1500, $this->action->getRefundAmountInCents('1500', 'pay_azerty1234'));
        $this->assertSame(1050, $this->action->getRefundAmountInCents('10.50', 'pay_qwerty5678'));
        $this->payment_method->shouldHaveReceived('retrieve')->with('pay_azerty1234')->once();
        $this->payment_method->shouldHaveReceived('retrieve')->with('pay_qwerty5678')->once();
    }

    public function testFailedRetrieveIsRetriedOnTheNextCall()
    {
        $this->stubStoredPayment();
        $this->payment_method->shouldReceive('retrieve')
            ->with('pay_azerty1234')
            ->andReturn(
                ['result' => false],
                [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(['currency' => 'EUR']),
                ]
            );

        $this->assertSame(0, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->assertSame(1050, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->payment_method->shouldHaveReceived('retrieve')->with('pay_azerty1234')->twice();
    }

    public function testPaymentWithoutCurrencyIsRetriedOnTheNextCall()
    {
        $this->stubStoredPayment();
        $this->payment_method->shouldReceive('retrieve')
            ->with('pay_azerty1234')
            ->andReturn(
                [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(['currency' => '']),
                ],
                [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(['currency' => 'EUR']),
                ]
            );

        $this->assertSame(0, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->assertSame(1050, $this->action->getRefundAmountInCents('10.50', 'pay_azerty1234'));
        $this->payment_method->shouldHaveReceived('retrieve')->with('pay_azerty1234')->twice();
    }

    public function missingCurrencyProvider()
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    /**
     * @param string $resource_id
     */
    private function stubStoredPayment($resource_id = 'pay_azerty1234')
    {
        $this->payment_repository->shouldReceive('getBy')
            ->with('resource_id', $resource_id)
            ->andReturn([
                'id_payplug_payment' => 42,
                'resource_id' => $resource_id,
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ]);
    }

    /**
     * @param array $parameters
     */
    private function stubPayment($parameters)
    {
        $this->stubStoredPayment();
        $this->payment_method->shouldReceive('retrieve')
            ->with('pay_azerty1234')
            ->andReturn([
                'result' => true,
                'resource' => PaymentMock::getStandard($parameters),
            ]);
    }
}
