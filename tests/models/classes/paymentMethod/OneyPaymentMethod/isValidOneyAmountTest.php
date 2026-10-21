<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class isValidOneyAmountTest extends BaseOneyPaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $currency = new \stdClass();
        $currency->iso_code = 'EUR';
        $this->configuration->shouldReceive('getValue')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('1');
        $this->currency_adapter->shouldReceive('get')
            ->with(1)
            ->andReturn($currency);
        $this->validate_adapter->shouldReceive('validate')->andReturn(true);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $value) {
                return strtoupper($value);
            });
        $this->class->shouldReceive('getOneyPriceLimit')
            ->andReturn(['min' => 10000, 'max' => 300000]);
        $this->amountHelper->shouldReceive('formatOneyAmount')
            ->andReturnUsing(function ($amount) {
                return ['result' => $amount / 100, 'message' => ''];
            });
    }

    public function testCartAmountIsConvertedInTheCartCurrencyAndLimitsInTheirOwn()
    {
        $this->context->currency->iso_code = 'JPY';
        $this->validators['payment']->shouldReceive('isAmount')
            ->andReturn(['result' => true, 'message' => '']);

        $this->assertSame(['result' => true, 'message' => ''], $this->class->isValidOneyAmount(150.0));

        $this->amountHelper->shouldHaveReceived('convertAmount')->with(150.0, false, 'JPY')->once();
        $this->amountHelper->shouldHaveReceived('formatOneyAmount')->with(10000, 'EUR')->once();
        $this->amountHelper->shouldHaveReceived('formatOneyAmount')->with(300000, 'EUR')->once();
        $this->amountHelper->shouldHaveReceived('convertAmount')->with(100, false, 'EUR')->once();
        $this->amountHelper->shouldHaveReceived('convertAmount')->with(3000, false, 'EUR')->once();
    }

    public function testAmountOutOfTheLimitsIsRejected()
    {
        $this->validators['payment']->shouldReceive('isAmount')
            ->andReturn(['result' => false, 'message' => '']);

        $this->assertSame(
            ['result' => false, 'message' => 'oney.isValidOneyAmount.unvalid'],
            $this->class->isValidOneyAmount(50.0)
        );
    }

    public function testCartWithoutCurrencyIsConvertedWithoutCurrency()
    {
        $this->context->currency = null;
        $this->validators['payment']->shouldReceive('isAmount')
            ->andReturn(['result' => false, 'message' => '']);

        $result = $this->class->isValidOneyAmount(150.0);

        $this->assertFalse($result['result']);
        $this->amountHelper->shouldHaveReceived('convertAmount')->with(150.0, false, '')->once();
    }
}
