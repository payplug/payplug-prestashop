<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\CountryMock;
use PayPlug\tests\mock\PaymentTabMock;

/**
 * @group unit
 * @group old_repository
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

        $this->context->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->country->shouldReceive('getCountry')
            ->andReturn(CountryMock::get())
        ;

        $this->dependencies->shouldReceive('isValidMobilePhoneNumber')
            ->andReturnUsing(function ($phone_number) {
                return !is_null($phone_number) && '' !== $phone_number;
            })
        ;

        $paymentTab = PaymentTabMock::getStandard();
        $this->tab = $paymentTab['shipping'];
    }

    /**
     * @description test checkOneyRequiredFields
     *  when $paymentData is empty
     */
    public function testMethodWithEmptyParams()
    {
        $paymentData = null;
        $response = $this->repo->checkOneyRequiredFields($paymentData);
        $this->assertSame(
            ['Please fill in the required fields'],
            $response
        );
    }

    /**
     * @description test checkOneyRequiredFields
     * when $paymentData is invalid
     */
    public function testMethodWithInvalidParams()
    {
        $paymentData = 'wrong params';
        $response = $this->repo->checkOneyRequiredFields($paymentData);
        $this->assertSame(
            ['Please fill in the required fields'],
            $response
        );
    }

    /**
     * @description  test checkOneyRequiredFields
     * when $paymentData is valid
     */
    public function testWithValidPaymentData()
    {
        $response = $this->repo->checkOneyRequiredFields($this->tab);
        $this->assertTrue(
            empty($response)
        );
    }

    /**
     * @return \Generator
     */
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
     * @description  test checkOneyRequiredFields
     * with valid data
     *
     * @dataProvider validPaymentDataProvider
     *
     * @param mixed $parameter
     */
    public function testWithValidDataProvider($parameter)
    {
        $field = ['shipping-' . $parameter => $this->tab[$parameter]];
        $this->validators['payment']->shouldReceive([
            'isPhoneNumber' => [
                'result' => true,
            ],
            'isValidMobilePhoneNumber' => [
                'result' => true,
                'message' => '',
            ],
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

    /**
     * @return \Generator
     */
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
     * @description test checkOneyRequiredFields
     * with invalid datas
     *
     * @dataProvider invalidPaymentDataProvider
     *
     * @param mixed $parameter
     * @param mixed $expected
     */
    public function testWithInvalidDataProvider($parameter, $expected)
    {
        $field = ['shipping-' . $parameter => null];
        $this->validators['payment']->shouldReceive([
            'isPhoneNumber' => [
                'result' => true,
            ],
            'isValidMobilePhoneNumber' => [
                'result' => false,
                'message' => '$iso_code is wrong',
            ],
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

    /**
     * @description  test checkOneyRequiredFields
     * when city name is too long
     */
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
