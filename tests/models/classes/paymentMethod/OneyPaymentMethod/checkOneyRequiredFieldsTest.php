<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\PaymentTabMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
final class checkOneyRequiredFieldsTest extends BaseOneyPaymentMethod
{
    public function setUp()
    {
        parent::setUp();

        $this->dependencies->shouldReceive('isValidMobilePhoneNumber')
            ->andReturnUsing(function ($phone_number) {
                return !is_null($phone_number) && '' !== $phone_number;
            })
        ;

        $paymentTab = PaymentTabMock::getStandard();
        $this->tab = $paymentTab['shipping'];
    }

    /**
     * @description  test checkOneyRequiredFields
     * with empty param
     */
    public function testMethodWithEmptyParams()
    {
        $paymentData = null;
        $response = $this->class->checkOneyRequiredFields($paymentData);
        $this->assertSame(
            ['Please fill in the required fields'],
            $response
        );
    }

    /**
     * @description test checkOneyRequiredFields
     * with invalid param
     */
    public function testMethodWithInvalidParams()
    {
        $paymentData = 'wrong params';
        $response = $this->class->checkOneyRequiredFields($paymentData);
        $this->assertSame(
            ['Please fill in the required fields'],
            $response
        );
    }

    /**
     * @description  test test checkOneyRequiredFields
     * with valid aram
     */
    public function testWithValidPaymentData()
    {
        $response = $this->class->checkOneyRequiredFields($this->tab);
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
     * @dataProvider validPaymentDataProvider
     *
     * @param mixed $parameter
     */
    public function testWithValidDataProvider($parameter)
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->validators['payment']->shouldReceive([
            'isValidMobilePhoneNumber' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturn(20);
        $get_country = (object) [];
        $get_country->iso_code = 'fr';
        $this->country_adapter->shouldReceive([
            'getCountry' => $get_country,
        ]);

        $field = ['shipping-' . $parameter => $this->tab[$parameter]];
        $response = $this->class->checkOneyRequiredFields($field);

        $this->assertSame(
            [],
            $response
        );

        $field = ['billing-' . $parameter => $this->tab[$parameter]];
        $response = $this->class->checkOneyRequiredFields($field);

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
     * @description  test checkOneyRequiredFields
     * with invalid data provider
     * @dataProvider invalidPaymentDataProvider
     *
     * @param mixed $parameter
     * @param mixed $expected
     */
    public function testWithInvalidDataProvider($parameter, $expected)
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->validators['payment']->shouldReceive([
            'isValidMobilePhoneNumber' => [
                'result' => false,
                'message' => '$iso_code is wrong',
            ],
        ]);
        $this->country_adapter->shouldReceive([
            'getCountry' => [],
        ]);

        $field = ['shipping-' . $parameter => null];
        $response = $this->class->checkOneyRequiredFields($field);

        $this->assertSame(
            [sprintf($expected, 'shipping')],
            $response
        );

        $field = ['billing-' . $parameter => ''];
        $response = $this->class->checkOneyRequiredFields($field);

        $this->assertSame(
            [sprintf($expected, 'billing')],
            $response
        );
    }

    /**
     * @description  test checkOneyRequiredFields
     * with too long city
     */
    public function testWithTooLongCity()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->tools_adapter->shouldReceive('tool')
            ->andReturn(37);

        $cityTooLong = 'city too long too long city tooo long';

        $this->assertSame(
            ['Your city name is too long (max 32 characters). Please change it to another one or select another payment method.'],
            $this->class->checkOneyRequiredFields(['shipping-city' => $cityTooLong])
        );
        $this->assertSame(
            ['Your city name is too long (max 32 characters). Please change it to another one or select another payment method.'],
            $this->class->checkOneyRequiredFields(['billing-city' => $cityTooLong])
        );
    }
}
