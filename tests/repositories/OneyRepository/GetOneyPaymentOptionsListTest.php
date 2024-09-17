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

        $this->configuration->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('"MQ","FR","BL","YT","RE","GF","PF","NC","GP","MF"');
        $this->configuration->shouldReceive('getValue')
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
        $this->repo->shouldReceive([
            'getOneySimulations' => [
                'result' => true,
                'simulations' => OneySimulationsMock::get(),
            ],
        ]);

        $x3_oney = [
            'installments' => [
                [
                    'date' => '2021-02-19T01:00:00.000Z',
                    'amount' => '80.42',
                    'value' => '80,42 €',
                ],
                [
                    'date' => '2021-03-19T01:00:00.000Z',
                    'amount' => '80.41',
                    'value' => '80,41 €',
                ],
            ],
            'total_cost' => [
                'amount' => '3.50',
                'value' => '3,50 €',
            ],
            'nominal_annual_percentage_rate' => '17.76',
            'effective_annual_percentage_rate' => '19.27',
            'down_payment_amount' => [
                'amount' => '83.92',
                'value' => '83,92 €',
            ],
            'split' => 3,
            'title' => 'Payment in 3x',
            'total_amount' => [
                'amount' => '15,003.50',
                'value' => '15,003,50 €',
            ],
        ];

        $x4_oney = [
            'installments' => [
                [
                    'date' => '2021-02-19T01:00:00.000Z',
                    'amount' => '60.31',
                    'value' => '60,31 €',
                ],
                [
                    'date' => '2021-03-19T01:00:00.000Z',
                    'amount' => '60.31',
                    'value' => '60,31 €',
                ],
                [
                    'date' => '2021-04-19T00:00:00.000Z',
                    'amount' => '60.32',
                    'value' => '60,32 €',
                ],
            ],
            'total_cost' => [
                'amount' => '5.31',
                'value' => '5,31 €',
            ],
            'nominal_annual_percentage_rate' => '18.05',
            'effective_annual_percentage_rate' => '19.62',
            'down_payment_amount' => [
                'amount' => '65.62',
                'value' => '65,62 €',
            ],
            'split' => 4,
            'title' => 'Payment in 4x',
            'total_amount' => [
                'amount' => '15,005.31',
                'value' => '15,005,31 €',
            ],
        ];

        $this->repo->shouldReceive('formatOneyResource')
            ->once()
            ->andReturn($x3_oney);

        $this->repo->shouldReceive('formatOneyResource')
            ->once()
            ->andReturn($x4_oney);

        $this->amount_helper->shouldReceive([
            'convertAmount' => 15000,
        ]);

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
        $this->repo->shouldReceive([
            'getOneySimulations' => [
                'result' => false,
                'error' => 'There is an error',
                'simulations' => [],
            ],
        ])
        ;

        $this->amount_helper->shouldReceive([
            'convertAmount' => 15000,
        ]);

        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList(15000, 'FR')
        );
    }
}
