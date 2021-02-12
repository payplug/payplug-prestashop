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

use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\AddresstMock;
use PayPlug\tests\mock\PaymentTabMock;
use PayPlug\tests\mock\CountryMock;
use PayPlug\src\repositories\OneyRepository;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group dev
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckOneyRequiredFieldsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // Default setup
    protected $config;
    protected $myLogPhp;


    // Method setup
    protected $cache;
    protected $logger;

    protected $address;
    protected $context;
    protected $country;
    protected $tools;
    protected $translate;
    protected $validate;

    protected $payplug;

    // Method Params
    protected $repo;
    protected $tab;

    public function setUp()
    {
        // Default setup for Oney Repository using
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        // Method setup
        $this->address = MockHelper::createAddressMock('Payplug\src\specific\AddressSpecific');
        $this->context = MockHelper::createContextMock('Payplug\src\specific\ContextSpecific');
        $this->country = MockHelper::createMockFactory('Payplug\src\specific\CountrySpecific');
        $this->tools = MockHelper::createToolsMock('Payplug\src\specific\ToolsSpecific');
        $this->translate = MockHelper::createTranslateMock('Payplug\src\specific\TranslationSpecific');
        $this->validate = MockHelper::createValidateMock('Payplug\src\specific\ValidateSpecific');

        $this->payplug = Mockery::mock('payplug');
        $this->repo = new OneyRepository($this->payplug);

        // Method Params
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

    public function testWithValidMobilePhoneNumber()
    {
        $this->country->shouldReceive('getCountry')
            ->andReturn(CountryMock::get());

        $this->payplug->shouldReceive('isValidMobilePhoneNumber')
            ->andReturn(true);

        $phone_number = ['shipping-mobile_phone_number' => $this->tab['mobile_phone_number']];
        $response = $this->repo->checkOneyRequiredFields($phone_number);

        $this->assertSame(
            [],
            $response
        );
    }

    public function testWithInalidMobilePhoneNumber()
    {
        $this->country->shouldReceive('getCountry')
            ->andReturn(CountryMock::get());

        $this->payplug->shouldReceive('isValidMobilePhoneNumber')
            ->andReturn(false);

        $phone_number = ['shipping-mobile_phone_number' => $this->tab['mobile_phone_number']];
        $response = $this->repo->checkOneyRequiredFields($phone_number);

        $this->assertSame(
            ['Please enter your mobile phone number.'],
            $response
        );
    }

    public function testWithValidFirstName()
    {
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['shipping-first_name' => $this->tab['first_name']])
        );
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['billing-first_name' => $this->tab['first_name']])
        );
    }

    public function testWithInvalidFirstName()
    {
        $this->validate
            ->andReturnUsing(function ($action, $data) {
                if ($action == 'isName') {
                    return false;
                }
                return true;
            });

        $this->assertSame(
            ['Please enter your shipping firstname.'],
            $this->repo->checkOneyRequiredFields(['shipping-first_name' => $this->tab['first_name']])
        );
        $this->assertSame(
            ['Please enter your billing firstname.'],
            $this->repo->checkOneyRequiredFields(['billing-first_name' => $this->tab['first_name']])
        );
    }

    public function testWithValidLastName()
    {
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['shipping-last_name' => $this->tab['last_name']])
        );
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['billing-last_name' => $this->tab['last_name']])
        );
    }

    public function testWithInvalidLastName()
    {
        $this->validate
            ->andReturnUsing(function ($action, $data) {
                if ($action == 'isName') {
                    return false;
                }
                return true;
            });

        $this->assertSame(
            ['Please enter your shipping lastname.'],
            $this->repo->checkOneyRequiredFields(['shipping-last_name' => $this->tab['last_name']])
        );
        $this->assertSame(
            ['Please enter your billing lastname.'],
            $this->repo->checkOneyRequiredFields(['billing-last_name' => $this->tab['last_name']])
        );
    }

    public function testWithValidAddress()
    {
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['shipping-address1' => $this->tab['address1']])
        );
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['billing-address1' => $this->tab['address1']])
        );
    }

    public function testWithInvalidAddress()
    {
        $this->validate
            ->andReturnUsing(function ($action, $data) {
                if ($action == 'isAddress') {
                    return false;
                }
                return true;
            });

        $this->assertSame(
            ['Please enter your shipping address.'],
            $this->repo->checkOneyRequiredFields(['shipping-address1' => $this->tab['address1']])
        );
        $this->assertSame(
            ['Please enter your billing address.'],
            $this->repo->checkOneyRequiredFields(['billing-address1' => $this->tab['address1']])
        );
    }

    public function testWithValidPostcode()
    {
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['shipping-postcode' => $this->tab['postcode']])
        );
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['billing-postcode' => $this->tab['postcode']])
        );
    }

    public function testWithInvalidPostcode()
    {
        $this->validate
            ->andReturnUsing(function ($action, $data) {
                if ($action == 'isPostCode') {
                    return false;
                }
                return true;
            });

        $this->assertSame(
            ['Please enter your shipping postcode.'],
            $this->repo->checkOneyRequiredFields(['shipping-postcode' => $this->tab['postcode']])
        );
        $this->assertSame(
            ['Please enter your billing postcode.'],
            $this->repo->checkOneyRequiredFields(['billing-postcode' => $this->tab['postcode']])
        );
    }

    public function testWithValidCity()
    {
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['shipping-city' => $this->tab['city']])
        );
        $this->assertSame(
            [],
            $this->repo->checkOneyRequiredFields(['billing-city' => $this->tab['city']])
        );
    }

    public function testWithInvalidCity()
    {
        $this->validate
            ->andReturnUsing(function ($action, $data) {
                if ($action == 'isCityName') {
                    return false;
                }
                return true;
            });

        $this->assertSame(
            ['Please enter your shipping city.'],
            $this->repo->checkOneyRequiredFields(['shipping-city' => $this->tab['city']])
        );
        $this->assertSame(
            ['Please enter your billing city.'],
            $this->repo->checkOneyRequiredFields(['billing-city' => $this->tab['city']])
        );
    }

    public function testWithTooLongCity()
    {
        $this->tools
            ->andReturnUsing(function ($action, $value, $params2) {
                switch ($action) {
                    case 'jsonDecode':
                        return json_decode($value, $params2);
                    case 'strpos':
                        return strpos($value, $params2);
                    case 'strlen':
                        return 33;
                    default:
                        break;
                }

                return false;
            });

        $this->assertSame(
            ['Your city name is too long (max 32 characters). Please change it to another one or select another payment method.'],
            $this->repo->checkOneyRequiredFields(['shipping-city' => $this->tab['city']])
        );
        $this->assertSame(
            ['Your city name is too long (max 32 characters). Please change it to another one or select another payment method.'],
            $this->repo->checkOneyRequiredFields(['billing-city' => $this->tab['city']])
        );
    }
}
