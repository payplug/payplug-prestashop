<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\src\models\entities\OneyEntity;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\RepositoryBase;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class IsValidOneyCountryTest extends RepositoryBase
{
    protected $oney;

    public function setUp()
    {
        parent::setUp();
    }

    public function testWithDifferentIsoCode()
    {
        $shipping_iso = 'FR';
        $billing_iso = 'IT';
        $error = 'Delivery and billing addresses must be in the same country to pay with Oney.';
        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            ->andReturn('FR');

        $this->oney = $this->oney ? $this->oney : new OneyEntity();
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->repo = \Mockery::mock(OneyRepository::class, [
            $this->address,
            $this->assign,
            $this->cache,
            $this->carrier,
            $this->cart,
            $this->config,
            $this->context,
            $this->country,
            $this->currency,
            $this->media,
            $this->dependencies,
            $this->logger,
            $this->myLogPhp,
            $this->oney,
            $this->tools,
            $this->validate,
        ])->makePartial();

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

        $this->oney = $this->oney ? $this->oney : new OneyEntity();
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->repo = \Mockery::mock(OneyRepository::class, [
            $this->address,
            $this->assign,
            $this->cache,
            $this->carrier,
            $this->cart,
            $this->config,
            $this->context,
            $this->country,
            $this->currency,
            $this->media,
            $this->dependencies,
            $this->logger,
            $this->myLogPhp,
            $this->oney,
            $this->tools,
            $this->validate,
        ])->makePartial();

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

        $this->oney = $this->oney ? $this->oney : new OneyEntity();
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->repo = \Mockery::mock(OneyRepository::class, [
            $this->address,
            $this->assign,
            $this->cache,
            $this->carrier,
            $this->cart,
            $this->config,
            $this->context,
            $this->country,
            $this->currency,
            $this->media,
            $this->dependencies,
            $this->logger,
            $this->myLogPhp,
            $this->oney,
            $this->tools,
            $this->validate,
        ])->makePartial();

        $this->assertSame(
            [
                'result' => true,
                'error' => false,
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }
}
