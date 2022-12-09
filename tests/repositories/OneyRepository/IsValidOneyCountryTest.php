<?php

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class IsValidOneyCountryTest extends BaseOneyRepository
{
    public function testWithDifferentIsoCode()
    {
        $shipping_iso = 'FR';
        $billing_iso = 'IT';
        $error = 'Delivery and billing addresses must be in the same country to pay with Oney.';

        $this->assertSame(
            [
                'result' => false,
                'type' => 'different',
                'error' => $error,
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }

    public function testWithoutAllowCountries()
    {
        $shipping_iso = $billing_iso = 'FR';
        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            ->andReturn('');

        $this->assertSame(
            [
                'result' => false,
                'type' => 'no_country',
                'error' => 'No countries are configured to use oney.',
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }

    public function testWithValidIsoCode()
    {
        $shipping_iso = $billing_iso = 'FR';

        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            ->andReturn('FR');

        $this->assertSame(
            [
                'result' => true,
                'error' => false,
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }
}
