<?php

namespace PayPlug\tests\utilities\services\PhoneNumber;

use PayPlug\src\utilities\services\PhoneNumber;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group service
 * @group phone_number_service
 */
class formatPhoneNumberTest extends TestCase
{
    private $dependencies;
    private $plugin;
    private $logger;
    private $phoneNumber;

    public function setUp(): void
    {
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive(['addLog' => true]);

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive(['getLogger' => $this->logger]);

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->shouldReceive(['getPlugin' => $this->plugin]);

        // Mock DependenciesClass to avoid needing Prestashop core classes
        \Mockery::mock('alias:PayPlug\classes\DependenciesClass', function ($mock) {
            $mock->shouldReceive('__construct')->andReturnNull();
        });

        $this->phoneNumber = new PhoneNumber();
        $this->phoneNumber->dependencies = $this->dependencies;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testReturnsE164FormattedNumberForValidFrenchMobileNumber()
    {
        $this->assertSame(
            '+33612345678',
            $this->phoneNumber->formatPhoneNumber('0612345678', 'FR')
        );
    }

    public function testReturnsEmptyStringWhenPhoneNumberIsEmpty()
    {
        $this->assertSame('', $this->phoneNumber->formatPhoneNumber('', 'FR'));
    }

    public function testReturnsEmptyStringWhenIsoCodeIsEmpty()
    {
        $this->assertSame('', $this->phoneNumber->formatPhoneNumber('0612345678', ''));
    }

    public function testReturnsEmptyStringWhenPhoneNumberIsNotValidForGivenCountry()
    {
        $this->assertSame('', $this->phoneNumber->formatPhoneNumber('0612345678', 'US'));
    }

    /**
     * Documents an intentional behavior tightening from delegating to UPC's
     * PhoneHelper. The old implementation only checked isValidNumber(), with
     * no region comparison, so a valid Belgian mobile number formatted
     * against 'FR' used to be accepted and returned as-is. UPC's PhoneHelper
     * requires an exact region match (getRegionCodeForNumber), so the same
     * call now correctly returns '' (the number is valid, but not for FR).
     */
    public function testReturnsEmptyStringWhenPhoneNumberIsValidForADifferentCountryThanGiven()
    {
        $this->assertSame('', $this->phoneNumber->formatPhoneNumber('+32475123456', 'FR'));
    }
}
