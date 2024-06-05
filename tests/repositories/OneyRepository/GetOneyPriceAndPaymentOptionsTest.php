<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @dontrunTestsInSeparateProcesses
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

        $this->cart->shouldReceive([
            'nbProducts' => 1001,
        ]);

        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;

        $this->country->shouldReceive([
            'getByIso' => 4,
        ]);

        $this->currency->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);

        $this->validators['payment']->shouldReceive([
            'isValidProductQuantity' => [
                'result' => true,
                'message' => '',
            ],
        ]);

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

        $this->validators['payment']->shouldReceive([
                'isOneyElligible' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->assertSame(
            $this->repo->getOneyPriceAndPaymentOptions($cart, $amount, $country),
            [
                'result' => true,
                'error' => false,
                'popin' => 'popin',
            ]
        );
    }

    public function testWithIneligibleOney()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyAmount' => ['result' => true, 'error' => false],
                'getOneyPaymentOptionsList' => ['payment_option_list'],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isOneyElligible' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.getOneyPriceAndPaymentOptions.unavailable',
                'popin' => 'popin',
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
            ],
            $this->repo->getOneyPriceAndPaymentOptions(null, 15000)
        );
    }

    public function testWithoutPaymentOption()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyAmount' => ['result' => true, 'error' => false],
                'getOneyPaymentOptionsList' => [],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isOneyElligible' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.getOneyPriceAndPaymentOptions.unavailable',
                'popin' => 'popin',
            ],
            $this->repo->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }
}
