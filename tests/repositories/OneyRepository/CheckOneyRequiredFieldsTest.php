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

use PayPlug\tests\mock\AddresstMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\PaymentTabMock;
use PayPlug\tests\mock\CountryMock;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckOneyRequiredFieldsTest extends BaseOneyRepository
{
    public function setUp()
    {
        parent::setUp();

        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->country->shouldReceive('getCountry')
            ->andReturn(CountryMock::get());

        $this->dependencies->shouldReceive('isValidMobilePhoneNumber')
            ->andReturnUsing(function ($phone_number) {
                return (!is_null($phone_number) && $phone_number !== '');
            });

        $paymentTab = PaymentTabMock::getStandard();
        $this->tab = $paymentTab['shipping'];
    }

    public function testMethodWithEmptyParams()
    {
        $paymentData = null;
        $response = $this->repo->checkOneyRequiredFields($paymentData);
        $this->assertSame(
            ['Please fill in the required fields'],
            $response
        );
    }

    public function testMethodWithInvalidParams()
    {
        $paymentData = 'wrong params';
        $response = $this->repo->checkOneyRequiredFields($paymentData);
        $this->assertSame(
            ['Please fill in the required fields'],
            $response
        );
    }

    public function testWithValidPaymentData()
    {
        $response = $this->repo->checkOneyRequiredFields($this->tab);
        $this->assertTrue(
            empty($response)
        );
    }

    public function validPaymentDataProvider()
    {
        yield ['mobile_phone_number'];
        yield ['first_name'];
        yield ['last_name'];
        yield ['address1'];
        yield ['postcode'];
        yield ['city'];
    }

    /**
     * @dataProvider validPaymentDataProvider
     */
    public function testWithValidDataProvider($parameter)
    {
        $field = ['shipping-' . $parameter => $this->tab[$parameter]];
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => true
            ]);
        $response = $this->repo->checkOneyRequiredFields($field);

        $this->assertSame(
            [],
            $response
        );

        $field = ['billing-' . $parameter => $this->tab[$parameter]];
        $response = $this->repo->checkOneyRequiredFields($field);

        $this->assertSame(
            [],
            $response
        );
    }

    public function invalidPaymentDataProvider()
    {
        yield ['mobile_phone_number', 'Please enter your mobile phone number.'];
        yield ['first_name', 'Please enter your %s firstname.'];
        yield ['last_name', 'Please enter your %s lastname.'];
        yield ['address1', 'Please enter your %s address.'];
        yield ['postcode', 'Please enter your %s postcode.'];
        yield ['city', 'Please enter your %s city.'];
    }

    /**
     * @dataProvider invalidPaymentDataProvider
     */
    public function testWithInvalidDataProvider($parameter, $expected)
    {
        $field = ['shipping-' . $parameter => null];
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => false
            ]);
        $response = $this->repo->checkOneyRequiredFields($field);

        $this->assertSame(
            [sprintf($expected, 'shipping')],
            $response
        );

        $field = ['billing-' . $parameter => ''];
        $response = $this->repo->checkOneyRequiredFields($field);

        $this->assertSame(
            [sprintf($expected, 'billing')],
            $response
        );
    }

    public function testWithTooLongCity()
    {
        $cityTooLong = 'city too long too long city tooo long';

        $this->assertSame(
            ['Your city name is too long (max 32 characters). Please change it to another one or select another payment method.'],
            $this->repo->checkOneyRequiredFields(['shipping-city' => $cityTooLong])
        );
        $this->assertSame(
            ['Your city name is too long (max 32 characters). Please change it to another one or select another payment method.'],
            $this->repo->checkOneyRequiredFields(['billing-city' => $cityTooLong])
        );
    }
}
