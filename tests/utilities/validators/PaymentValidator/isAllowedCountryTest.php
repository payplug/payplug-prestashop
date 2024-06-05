<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isAllowedCountryTest extends TestCase
{
    private $paymentValidator;

    protected function setUp()
    {
        $this->paymentValidator = new paymentValidator();
    }

    /**
     * @description invalid data provider
     *
     * @return Generator
     */
    public function invalidDataProvider()
    {
        yield [42, 'FR', 'Invalid allowed countries format'];
        yield [['key' => 'value'], 'FR', 'Invalid allowed countries format'];
        yield [false, 'FR', 'Invalid allowed countries format'];
        yield ['', 'FR', 'Invalid allowed countries format'];

        yield ['FR', 42, 'Invalid country format'];
        yield ['FR', ['key' => 'value'], 'Invalid country format'];
        yield ['FR', false, 'Invalid country format'];
        yield ['FR', '', 'Invalid country format'];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $allowedCountries
     * @param mixed $country
     * @param mixed $errorMsg
     */
    public function testIsAllowedCountryWithInvalidData($allowedCountries, $country, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->paymentValidator->isAllowedCountry($allowedCountries, $country)
        );
    }
}
