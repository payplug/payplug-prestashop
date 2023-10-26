<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\CountryMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyPriceAndPaymentOptionsTest extends BaseOneyRepository
{
    private $cartMock;

    public function setUp()
    {
        parent::setUp();

        $this->context
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->country->shouldReceive('getCountry')
            ->andReturn(CountryMock::get())
        ;

        $this->repo
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive([
                'displayOneyRequiredFields' => 'required_field',
                'displayOneyPopin' => 'popin',
                'displayOneyPaymentOptions' => 'payment_option',
            ])
        ;

        $this->cartMock = CartMock::get();
    }

    public function validDataProvider()
    {
        yield [CartMock::get(), 15000, false];
        yield [null, 15000, false];
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param mixed $cart
     * @param mixed $amount
     * @param mixed $country
     */
    public function testWithValidData($cart, $amount, $country)
    {
        $this->repo
            ->shouldReceive([
                'isOneyElligible' => ['result' => true, 'error' => false],
                'isValidOneyAmount' => ['result' => true, 'error' => false],
                'getOneyPaymentOptionsList' => ['payment_option_list'],
            ])
        ;

        $this->assertSame(
            $this->repo->getOneyPriceAndPaymentOptions($cart, $amount, $country),
            [
                'result' => true,
                'error' => false,
                'popin' => 'popin',
                'payment' => 'payment_option',
            ]
        );
    }

    public function testWithIneligibleOney()
    {
        $this->repo
            ->shouldReceive([
                'isOneyElligible' => ['result' => false, 'error' => 'oney_ineligible'],
                'getOneyPaymentOptionsList' => ['payment_option_list'],
            ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney_ineligible',
                'popin' => 'popin',
                'payment' => 'payment_option',
            ],
            $this->repo->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }

    public function testWithInvalidAmount()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyAmount' => ['result' => false, 'error' => 'invalid_amount'],
                'getOneyPaymentOptionsList' => ['payment_option_list'],
            ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'error' => 'invalid_amount',
                'popin' => 'popin',
                'payment' => 'payment_option',
            ],
            $this->repo->getOneyPriceAndPaymentOptions(null, 15000)
        );
    }

    public function testWithoutPaymentOption()
    {
        $this->repo
            ->shouldReceive([
                'isOneyElligible' => ['result' => true, 'error' => false],
                'getOneyPaymentOptionsList' => [],
            ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.getOneyPriceAndPaymentOptions.unavailable',
                'popin' => 'popin',
                'payment' => 'payment_option',
            ],
            $this->repo->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }
}
