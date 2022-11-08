<?php

namespace PayPlug\tests\utilities\validators\OneyValidator;

use PayPlug\src\utilities\validators\oneyValidator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class isOneySettingsCtaAllowed extends TestCase
{
    protected $oneyValidator;

    protected function setUp()
    {
        $this->oneyValidator = new OneyValidator();
    }

    public function invalidOneyCountryFeatureDataProvider()
    {
        yield [42, 'FR', 'Invalid oney allowed countries format'];
        yield [['key' => 'value'], 'FR', 'Invalid oney allowed countries format'];
        yield [false, 'FR', 'Invalid oney allowed countries format'];
        yield ['', 'FR', 'Invalid oney allowed countries format'];

        yield ['FR', 42, 'Invalid country format'];
        yield ['FR', ['key' => 'value'], 'Invalid country format'];
        yield ['FR', false, 'Invalid country format'];
        yield ['FR', '', 'Invalid country format'];
    }

    /**
     * @dataProvider invalidOneyCountryFeatureDataProvider
     *
     * @param mixed $oneyAllowedCountries
     * @param mixed $country
     * @param mixed $errorMsg
     */
    public function testWithInvalidOneyCountryFeatureDataProvider($oneyAllowedCountries, $country, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->oneyValidator->isOneyAllowedCountry($oneyAllowedCountries, $country)
        );
    }
}
