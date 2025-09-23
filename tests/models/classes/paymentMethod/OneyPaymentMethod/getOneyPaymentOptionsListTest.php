<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOneyPaymentOptionsListTest extends BaseOneyPaymentMethod
{
    public $amount;
    public $country;
    public $oney_simulations;

    public function setUp()
    {
        parent::setUp();
        $this->amount = 42;
        $this->country = 'FR';
        $this->helpers['amount']
            ->shouldReceive('convertAmount')
            ->andReturn(4200);
        $this->tools_adapter
            ->shouldReceive('tool')
            ->withArgs(['strtoupper', $this->country])
            ->andReturn($this->country);
        $this->oney_simulations = OneySimulationsMock::get();
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsNotValidIntegerFormat($amount)
    {
        $this->assertSame(
            [],
            $this->class->getOneyPaymentOptionsList($amount, $this->country)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $country
     */
    public function testWhenNoCountryAllowed($country)
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('');
        $this->assertSame(
            [],
            $this->class->getOneyPaymentOptionsList($this->amount, $country)
        );
    }

    public function testWhenOneySimulationsCantBeGetted()
    {
        $this->class->shouldReceive([
            'getOperations' => [],
            'getOneySimulations' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [],
            $this->class->getOneyPaymentOptionsList($this->amount, $this->country)
        );
    }

    public function testWhenListAreGetted()
    {
        $this->class->shouldReceive([
            'getOperations' => [],
            'getOneySimulations' => [
                'result' => true,
                'simulations' => $this->oney_simulations,
            ],
        ]);
        $this->class->shouldReceive('formatOneyResource')
            ->andReturnUsing(function ($method, $oney_simulation, $amount) {
                return $oney_simulation;
            });

        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(true);

        $this->assertSame(
            $this->oney_simulations,
            $this->class->getOneyPaymentOptionsList($this->amount, $this->country)
        );
    }
}
