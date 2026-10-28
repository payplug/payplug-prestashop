<?php

namespace PayPlug\tests\unified\UnifiedApiPaymentServiceFactory;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;
use PayPlug\src\unified\UnifiedApiPaymentServiceFactory;
use PayplugUnifiedCore\Services\DomainAssociationService;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group unified
 */
class createDomainAssociationServiceTest extends TestCase
{
    private $factory;

    public function setUp(): void
    {
        $this->factory = \Mockery::mock(UnifiedApiPaymentServiceFactory::class)->makePartial();
        $this->factory->dependencies = \Mockery::mock('Dependencies');
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testBuildsTheDomainAssociationService()
    {
        $this->assertInstanceOf(DomainAssociationService::class, $this->factory->createDomainAssociationService());
    }

    public function testBuildsTheDomainAssociationStorage()
    {
        $this->assertInstanceOf(UnifiedDomainAssociationStorage::class, $this->factory->createDomainAssociationStorage());
    }
}
