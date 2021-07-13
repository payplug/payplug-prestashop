<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\CountryMock;
use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group repository
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
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->country->shouldReceive('getCountry')
            ->andReturn(CountryMock::get());

        $this->repo
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive([
                'displayOneyRequiredFields' => 'required_field',
                'displayOneyPopin' => 'popin',
                'displayOneyPaymentOptions' => 'payment_option'
            ]);

        $this->cartMock = CartMock::get();
    }

    public function validDataProvider()
    {
        yield [CartMock::get(), 15000, false];
        yield [null, 15000, false];
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testWithValidData($cart, $amount, $country)
    {
        $this->repo
            ->shouldReceive([
                'isOneyElligible' => ['result' => true, 'error' => false],
                'isValidOneyAmount' => ['result' => true, 'error' => false],
                'getOneyPaymentOptionsList' => ['payment_option_list'],
            ]);

        $this->assertSame(
            $this->repo->getOneyPriceAndPaymentOptions($cart, $amount, $country),
            [
                'result' => true,
                'error' => false,
                'popin' => 'popin',
                'payment' => 'payment_option'
            ]
        );
    }

    public function testWithIneligibleOney()
    {
        $this->repo
            ->shouldReceive([
                'isOneyElligible' => ['result' => false, 'error' => 'oney_ineligible'],
                'getOneyPaymentOptionsList' => ['payment_option_list'],
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney_ineligible',
                'popin' => 'popin',
                'payment' => 'payment_option'
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
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'invalid_amount',
                'popin' => 'popin',
                'payment' => 'payment_option'
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
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.getOneyPriceAndPaymentOptions.unavailable',
                'popin' => 'popin',
                'payment' => 'payment_option'
            ],
            $this->repo->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }
}
