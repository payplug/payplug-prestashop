<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class TreatAccountResponseTest extends BaseApi
{
    private $json_answer;

    public function setUp()
    {
        parent::setUp();

        $this->json_answer = [
            'secret_keys' => [
                'test' => 'sk_test_azerty12345',
                'live' => 'sk_live_azerty12345',
            ],
            'is_live' => true,
            'permissions' => [
                'use_live_mode' => true,
                'can_save_cards' => true,
                'can_use_oney' => true,
                'can_create_installment_plan' => true,
                'can_create_deferred_payment' => true,
                'can_use_integrated_payments' => true,
            ],
            'configuration' => [
                'currencies' => [
                    'EUR',
                ],
            ],
        ];

        $configuration_class = \Mockery::mock('ConfigurationClass');
        $configuration_class->shouldReceive([
            'set' => true,
        ]);
        $configuration_class
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                return $key;
            });

        $tools = \Mockery::mock('Tools');
        $tools->shouldReceive([
            'substr' => 'string',
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $configuration_class,
            'getTools' => $tools,
        ]);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $json_answer
     */
    public function testWhenGivenJsonAnswerIsntValidArray($json_answer)
    {
        $this->assertSame(
            [],
            $this->service->treatAccountResponse($json_answer)
        );
    }

    public function testWhenGivenJsonAnswerContainError()
    {
        $json_answer = [
            'object' => 'error',
        ];
        $this->assertSame(
            [],
            $this->service->treatAccountResponse($json_answer)
        );
    }

    public function testWhenResponseIsTreated()
    {
        $this->assertSame(
            [
                'is_live' => true,
                'use_live_mode' => true,
                'can_save_cards' => true,
                'apple_pay_allowed_domains' => [],
                'onboarding_oney_completed' => false,
                'can_use_oney' => true,
                'can_create_installment_plan' => true,
                'can_create_deferred_payment' => true,
                'can_use_integrated_payments' => true,
            ],
            $this->service->treatAccountResponse($this->json_answer)
        );
    }
}
