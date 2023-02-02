<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaylaterSectionTest extends BaseApiRest
{
    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $context,
        ]);
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
                'oney' => true,
                'oney_min_amounts' => 'EUR:1000',
                'oney_max_amounts' => 'EUR:300000',
                'oney_custom_min_amounts' => 'EUR:10000',
                'oney_custom_max_amounts' => 'EUR:300000',
                'oney_product_animation' => true,
                'oney_cart_animation' => true,
                'oney_schedule' => true,
                'oney_fees' => true,
            ],
        ];
    }

    /**
     * @description  test with invalid parameters
     * @dataProvider invalidDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenDataHasInvalidFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->classe->getPaylaterSection($current_configuration)
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
            if (strpos($key, '_amounts') !== false) {
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
        }

        $response = $this->classe->getPaylaterSection($current_configuration);

        // test Oney switch
        $this->assertSame(
            true,
            $response['options']['checked']
        );

        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'oneyAllowedCountries':
                        return 'GP,MQ,FR,GF,RE,YT';

                    default:
                        return false;
                }
            });

        $thresholds_options = $response['options']['advanced_options'][0];
        // test oney_min_amounts
        $this->assertSame(
            $all_amounts['oney_custom_min_amounts'],
            (int) $thresholds_options['descriptions']['min_amount']['value'][1]
        );
        $this->assertSame(
            $all_amounts['oney_custom_min_amounts'],
            (int) $thresholds_options['descriptions']['min_amount']['placeholder'][1]
        );
        $this->assertSame(
            $all_amounts['oney_min_amounts'],
            (int) $thresholds_options['descriptions']['min_amount']['min'][1]
        );
        $this->assertSame(
            $all_amounts['oney_max_amounts'],
            (int) $thresholds_options['descriptions']['min_amount']['max'][1]
        );

        // test oney_max_amounts
        $this->assertSame(
            $all_amounts['oney_custom_max_amounts'],
            (int) $thresholds_options['descriptions']['max_amount']['value'][1]
        );
        $this->assertSame(
            $all_amounts['oney_custom_max_amounts'],
            (int) $thresholds_options['descriptions']['max_amount']['placeholder'][1]
        );
        $this->assertSame(
            $all_amounts['oney_min_amounts'],
            (int) $thresholds_options['descriptions']['max_amount']['min'][1]
        );
        $this->assertSame(
            $all_amounts['oney_max_amounts'],
            (int) $thresholds_options['descriptions']['max_amount']['max'][1]
        );

        // test fees options
        $this->assertSame(
            true,
            $response['options']['options'][0]['checked']
        );
        $this->assertSame(
            false,
            $response['options']['options'][1]['checked']
        );
    }
}
