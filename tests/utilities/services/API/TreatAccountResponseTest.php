<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class TreatAccountResponseTest extends BaseApi
{
    public $json_answer;
    public $configuration_class;
    public $captured_configuration;

    public function setUp(): void
    {
        parent::setUp();

        $this->captured_configuration = [];

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

        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->configuration_class
            ->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                $this->captured_configuration[$key] = $value;

                return true;
            });
        $this->configuration_class
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                return $key;
            });

        $tools = \Mockery::mock('Tools');
        $tools->shouldReceive([
            'substr' => 'string',
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
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

    public function testWhenResponseContainsOneyDataMapsCountriesMetadataAndAmountsToConfiguration()
    {
        $countries_metadata = [
            'FR' => [
                'merchant_guid' => 'd5104abda4e74c45a78c08901107bb08',
                'psp_guid' => '0699c7373e544d038d930072e10cd575',
                'oney_business_codes' => [
                    'x3_with_fees' => 'W3135',
                    'x4_with_fees' => 'W4144',
                    'x3_without_fees' => 'DLN04',
                    'x4_without_fees' => 'DLN05',
                ],
            ],
        ];
        $this->json_answer['oney'] = [
            'enabled' => true,
            'allowed_countries' => ['FR', 'IT', 'ES', 'NL'],
            'min_amounts' => ['EUR' => 10000],
            'max_amounts' => ['EUR' => 300000],
            'show_legal_notices' => true,
            'countries_metadata' => $countries_metadata,
        ];

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame(
            json_encode($countries_metadata),
            $this->captured_configuration['oney_countries_metadata']
        );
        self::assertSame(1, $this->captured_configuration['oney_show_legal_notices']);
        self::assertSame(json_encode(['EUR' => 10000]), $this->captured_configuration['oney_min_amounts']);
        self::assertSame(json_encode(['EUR' => 300000]), $this->captured_configuration['oney_max_amounts']);
        self::assertSame('FR,IT,ES,NL', $this->captured_configuration['oney_allowed_countries']);
    }

    public function testWhenResponseContainsOneyDataWithMissingFieldsOverwritesConfigurationWithEmptyValues()
    {
        $this->json_answer['oney'] = [
            'enabled' => false,
            'show_legal_notices' => false,
        ];

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame(json_encode([]), $this->captured_configuration['oney_min_amounts']);
        self::assertSame(json_encode([]), $this->captured_configuration['oney_max_amounts']);
        self::assertSame('', $this->captured_configuration['oney_allowed_countries']);
    }

    public function testWhenResponseContainsOneyDataWithEmptyAllowedCountriesOverwritesConfiguration()
    {
        $this->json_answer['oney'] = [
            'enabled' => false,
            'allowed_countries' => [],
            'min_amounts' => ['EUR' => 10000],
            'max_amounts' => ['EUR' => 300000],
            'show_legal_notices' => false,
        ];

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame('', $this->captured_configuration['oney_allowed_countries']);
    }

    public function testWhenResponseContainsNoOneyDataItLeavesOneyConfigurationUntouched()
    {
        $this->service->treatAccountResponse($this->json_answer);

        self::assertArrayNotHasKey('oney_countries_metadata', $this->captured_configuration);
        self::assertArrayNotHasKey('oney_show_legal_notices', $this->captured_configuration);
        self::assertArrayNotHasKey('oney_min_amounts', $this->captured_configuration);
        self::assertArrayNotHasKey('oney_max_amounts', $this->captured_configuration);
    }

    public function testWhenResponseContainsLegacyNestedOneyDataItIsStillMapped()
    {
        $countries_metadata = [
            'FR' => [
                'merchant_guid' => 'guid123',
                'oney_business_codes' => [
                    'x3_with_fees' => 'W3135',
                ],
            ],
        ];
        $this->json_answer['configuration']['oney'] = [
            'enabled' => true,
            'allowed_countries' => ['FR'],
            'min_amounts' => ['EUR' => 10000],
            'max_amounts' => ['EUR' => 300000],
            'show_legal_notices' => false,
            'countries_metadata' => $countries_metadata,
        ];

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame(json_encode($countries_metadata), $this->captured_configuration['oney_countries_metadata']);
        self::assertSame('FR', $this->captured_configuration['oney_allowed_countries']);
    }
}
