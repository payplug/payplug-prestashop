<?php

namespace PayPlug\tests\repositories\OneyRepository;

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
    public function testWithEmptyIsoCode()
    {
        $this->assertSame(
            false,
            $this->repo->getOneyCountry(null)
        );
    }

    public function testWithWrongIsoCode()
    {
        $this->assertSame(
            false,
            $this->repo->getOneyCountry(42)
        );
    }

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

    public function testGetCustomIsoCode()
    {
        $iso_code = 'BE';
        $this->assertSame(
            $iso_code,
            $this->repo->getOneyCountry($iso_code)
        );
    }
}
