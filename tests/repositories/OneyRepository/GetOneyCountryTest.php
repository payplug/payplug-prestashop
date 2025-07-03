<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\FormatDataProvider;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyCountryTest extends BaseOneyRepository
{
    use FormatDataProvider;

    /**
     * @description  test getOneyCountry
     * when invalid $iso_code is given
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $iso_country
     */
    public function testWhenGivenIsoCountryIsInvalidStringFormat($iso_country)
    {
        $this->assertSame(
            'FR',
            $this->repo->getOneyCountry($iso_country)
        );
    }

    /**
     * @description test getOneyCountry
     * when empty iso code is given
     */
    public function testWithEmptyIsoCode()
    {
        $this->assertSame(
            'FR',
            $this->repo->getOneyCountry(null)
        );
    }

    /**
     * @description test getOneyCountry
     * when wrong is code is given
     */
    public function testWithWrongIsoCode()
    {
        $this->assertSame(
            'FR',
            $this->repo->getOneyCountry(42)
        );
    }

    /**
     * @description test getOneyCountry
     * with default iso code
     */
    public function testGetDefaultIsoCode()
    {
        $overseas_iso = ['GP', 'MQ', 'GF', 'RE', 'YT'];

        foreach ($overseas_iso as $iso) {
            $this->assertSame(
                'FR',
                $this->repo->getOneyCountry($iso)
            );
        }
    }

    /**
     * @description test getOneyCountry
     * with custom iso_code
     */
    public function testGetCustomIsoCode()
    {
        $iso_code = 'BE';
        $this->assertSame(
            $iso_code,
            $this->repo->getOneyCountry($iso_code)
        );
    }
}
