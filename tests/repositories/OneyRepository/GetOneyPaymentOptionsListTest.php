<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyPaymentOptionsListTest extends BaseOneyRepository
{
    protected $list;

    public function setUp()
    {
        parent::setUp();

        $this->carrier->shouldReceive([
            'get' => CarrierMock::get(),
            'getDefaultDelay' => 0,
            'getDefaultDeliveryType' => 'storepickup',
        ]);

        $this->context = MockHelper::createContextMock('Payplug\src\application\adapter\ContextAdapter');

        $this->repo
            ->shouldAllowMockingProtectedMethods()
        ;

        $this->list = OneySimulationsMock::getFormated();

        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('"MQ","FR","BL","YT","RE","GF","PF","NC","GP","MF"');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn('1');
    }

    public function validListDataProvider()
    {
        yield [15000, null];
        yield [15000, false];
        yield [15000, ''];
        yield [15000, 'FR'];
    }

    /**
     * @dataProvider validListDataProvider
     *
     * @param $amount
     * @param $country
     */
    public function testGetList($amount, $country)
    {
        $this->repo
            ->shouldReceive([
                'getOneySimulations' => [
                    'result' => true,
                    'simulations' => OneySimulationsMock::get(),
                ],
            ])
        ;

        $this->assertSame(
            $this->repo->getOneyPaymentOptionsList($amount, $country),
            $this->list
        );
    }

    public function invalidListDataProvider()
    {
        yield [0, 'FR'];
        yield [false, 'FR'];
        yield [null, 'FR'];
        yield ['wrong params', 'FR'];
    }

    /**
     * @dataProvider invalidListDataProvider
     *
     * @param mixed $amount
     * @param mixed $country
     */
    public function testGetListWithWrongAmount($amount, $country)
    {
        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList($amount, $country)
        );
    }

    public function testGetListWithoutSimulation()
    {
        $this->repo
            ->shouldReceive([
                'getOneySimulations' => [
                    'result' => false,
                    'error' => 'There is an error',
                    'simulations' => [],
                ],
            ])
        ;

        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList(15000, 'FR')
        );
    }
}
