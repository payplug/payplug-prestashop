<?php

namespace PayPlug\tests\models\classes\ApiRest;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 *
 * @runTestsInSeparateProcesses
 */
class getThresholdsOptionsTest extends BaseApiRest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function invalidDataProvider()
    {
        yield [42];
        yield [true];
        yield [null];
        yield [''];
    }

    public function validDataProvider()
    {
        yield [
            [
                'oney_min_amounts' => 'EUR:10000',
                'oney_max_amounts' => 'EUR:300000',
                'oney_custom_min_amounts' => 'EUR:10000',
                'oney_custom_max_amounts' => 'EUR:300000',
            ],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenDataHasInvalidFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->classe->getThresholdsOptions($current_configuration)
        );
    }

    /**
     * @description
     *
     * @dataProvider validDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenDataHasValidFormat($current_configuration)
    {
        $all_amounts = [];
        foreach ($current_configuration as $key => $configuration) {
            $amount = explode(':', $configuration);
            $all_amounts[$key] = (int) $amount[1];

            $this->amount_helper
                ->shouldReceive(
                    'formatOneyAmount'
                )
                ->once()
                ->with($amount[1])
                ->andReturn(['result' => [
                    1 => $amount[1],
                ]]);
        }

        $response = $this->classe->getThresholdsOptions($current_configuration);

        // test oney_min_amounts
        $this->assertSame(
            $all_amounts['oney_custom_min_amounts'],
            (int) $response['descriptions']['min_amount']['value'][1]
        );
        $this->assertSame(
            $all_amounts['oney_custom_min_amounts'],
            (int) $response['descriptions']['min_amount']['placeholder'][1]
        );
        $this->assertSame(
            $all_amounts['oney_min_amounts'],
            (int) $response['descriptions']['min_amount']['default'][1]
        );

        // test oney_min_amounts
        $this->assertSame(
            $all_amounts['oney_custom_max_amounts'],
            (int) $response['descriptions']['max_amount']['value'][1]
        );
        $this->assertSame(
            $all_amounts['oney_custom_max_amounts'],
            (int) $response['descriptions']['max_amount']['placeholder'][1]
        );
        $this->assertSame(
            $all_amounts['oney_max_amounts'],
            (int) $response['descriptions']['max_amount']['default'][1]
        );
    }
}
