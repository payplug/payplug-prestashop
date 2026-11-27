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
    public $logs;
    public $sandbox_mode;
    public $stored_token;

    public function setUp(): void
    {
        parent::setUp();

        $this->captured_configuration = [];
        $this->sandbox_mode = '0';
        $this->stored_token = '';

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
                if ('sandbox_mode' === $key) {
                    return $this->sandbox_mode;
                }

                return 'scalapay_merchant_token' === $key ? $this->stored_token : $key;
            });

        $tools = \Mockery::mock('Tools');
        $tools->shouldReceive([
            'substr' => 'string',
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration_class,
            'getTools' => $tools,
        ]);
        $this->logs = [];
        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')
            ->andReturnUsing(function ($message, $level = 'info') {
                $this->logs[] = [$message, $level];

                return true;
            });
        $this->plugin->shouldReceive('getLogger')->andReturn($logger)->byDefault();
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

    public function testWhenResponseContainsPaymentMethodsLimitsAreMappedAndSyncIsStamped()
    {
        $this->json_answer['payment_methods'] = [
            'bancontact' => [
                'enabled' => true,
                'allowed_countries' => ['ALL'],
                'min_amounts' => ['EUR' => 30],
                'max_amounts' => ['EUR' => 2000000],
            ],
            'satispay' => [
                'enabled' => true,
                'allowed_countries' => ['IT'],
                'min_amounts' => ['EUR' => 30],
                'max_amounts' => ['EUR' => 2000000],
            ],
        ];

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame(
            '{"bancontact":{"min":"EUR:30","max":"EUR:2000000"},"satispay":{"min":"EUR:30","max":"EUR:2000000"}}',
            $this->captured_configuration['amounts']
        );
        self::assertSame('{"satispay":["IT"]}', $this->captured_configuration['countries']);
    }

    public function testNewlyInvalidApmSettingsAreLoggedOnceTheLimitsAreStored()
    {
        $this->json_answer['payment_methods'] = [
            'satispay' => [
                'enabled' => true,
                'min_amounts' => ['EUR' => 30],
                'max_amounts' => ['EUR' => 4000],
            ],
        ];
        $previous = ['bizum' => ['min' => 6000, 'max' => 5500, 'currency' => 'EUR']];
        $stored_amounts = [];
        $apm_eligibility = \Mockery::mock('ApmEligibility');
        $apm_eligibility->shouldReceive('getInvalidSettings')
            ->once()
            ->andReturnUsing(function () use (&$stored_amounts, $previous) {
                $stored_amounts[] = isset($this->captured_configuration['amounts']) ? $this->captured_configuration['amounts'] : null;

                return $previous;
            });
        $apm_eligibility->shouldReceive('logNewlyInvalidSettings')
            ->once()
            ->with($previous)
            ->andReturnUsing(function () use (&$stored_amounts) {
                $stored_amounts[] = isset($this->captured_configuration['amounts']) ? $this->captured_configuration['amounts'] : null;

                return ['satispay'];
            });
        $this->service->shouldReceive('getApmEligibility')->andReturn($apm_eligibility);

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame([null, '{"satispay":{"min":"EUR:30","max":"EUR:4000"}}'], $stored_amounts);
    }

    public function checkFailureProvider()
    {
        yield 'previous settings read' => ['getInvalidSettings', new \TypeError('Argument must be of type array')];

        yield 'new settings check' => ['logNewlyInvalidSettings', new \TypeError('Argument must be of type array')];

        yield 'exception' => ['logNewlyInvalidSettings', new \Exception('Argument must be of type array')];
    }

    /**
     * @dataProvider checkFailureProvider
     *
     * @param string $failing_method
     * @param \Throwable $error
     */
    public function testAccountIsStillTreatedWhenTheApmSettingsCheckFails($failing_method, $error)
    {
        $logs = [];
        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')
            ->andReturnUsing(function ($message, $level = 'info') use (&$logs) {
                $logs[] = [$message, $level];

                return true;
            });
        $this->plugin->shouldReceive('getLogger')->andReturn($logger);
        $apm_eligibility = \Mockery::mock('ApmEligibility');
        $apm_eligibility->shouldReceive([
            'getInvalidSettings' => [],
            'logNewlyInvalidSettings' => [],
        ])->byDefault();
        $apm_eligibility->shouldReceive($failing_method)->andThrow($error);
        $this->service->shouldReceive('getApmEligibility')->andReturn($apm_eligibility);

        $permissions = $this->service->treatAccountResponse($this->json_answer);

        self::assertTrue($permissions['can_use_oney']);
        self::assertArrayHasKey('amounts', $this->captured_configuration);
        self::assertSame(
            [['Api::treatAccountResponse - APM settings check failed: Argument must be of type array', 'error']],
            $logs
        );
    }

    public function testScalapayMerchantTokenIsStoredWhenTheLiveAccountExposesIt()
    {
        $this->json_answer['payment_methods'] = $this->scalapayPaymentMethods('tok_abc');

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame('tok_abc', $this->captured_configuration['scalapay_merchant_token']);
    }

    public function testScalapayMerchantTokenIsTrimmed()
    {
        $this->json_answer['payment_methods'] = $this->scalapayPaymentMethods("  tok_abc \n");

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame('tok_abc', $this->captured_configuration['scalapay_merchant_token']);
    }

    public function missingTokenProvider()
    {
        yield 'no scalapay entry' => [['bancontact' => ['enabled' => true]]];

        yield 'no token field' => [['scalapay' => ['enabled' => true]]];

        yield 'empty token' => [['scalapay' => ['enabled' => true, 'merchant_token' => '']]];

        yield 'blank token' => [['scalapay' => ['enabled' => true, 'merchant_token' => '   ']]];

        yield 'array token' => [['scalapay' => ['enabled' => true, 'merchant_token' => ['tok_abc']]]];

        yield 'integer token' => [['scalapay' => ['enabled' => true, 'merchant_token' => 42]]];

        yield 'scalapay entry is not an array' => [['scalapay' => true]];
    }

    /**
     * @dataProvider missingTokenProvider
     *
     * @param array $payment_methods
     */
    public function testScalapayMerchantTokenIsClearedWhenTheLiveAccountExposesNone($payment_methods)
    {
        $this->stored_token = 'tok_old';
        $this->json_answer['payment_methods'] = $payment_methods;

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame('', $this->captured_configuration['scalapay_merchant_token']);
    }

    public function testScalapayMerchantTokenIsKeptWhenTheAccountIsNotLive()
    {
        $this->stored_token = 'tok_live';
        $this->json_answer['is_live'] = false;
        $this->json_answer['payment_methods'] = $this->scalapayPaymentMethods('tok_sandbox');

        $this->service->treatAccountResponse($this->json_answer);

        self::assertArrayNotHasKey('scalapay_merchant_token', $this->captured_configuration);
    }

    public function testScalapayMerchantTokenIsKeptInSandboxModeEvenForALiveAnswer()
    {
        $this->sandbox_mode = '1';
        $this->stored_token = 'tok_live';
        $this->json_answer['payment_methods'] = $this->scalapayPaymentMethods('tok_other');

        $this->service->treatAccountResponse($this->json_answer);

        self::assertArrayNotHasKey('scalapay_merchant_token', $this->captured_configuration);
        self::assertSame([], $this->logs);
    }

    public function testScalapayMerchantTokenIsKeptWhenTheAnswerHasNoPaymentMethods()
    {
        $this->stored_token = 'tok_live';

        $this->service->treatAccountResponse($this->json_answer);

        self::assertArrayNotHasKey('scalapay_merchant_token', $this->captured_configuration);
    }

    public function testScalapayMerchantTokenIsKeptOnAnErrorAnswer()
    {
        $this->stored_token = 'tok_live';

        $this->service->treatAccountResponse([
            'object' => 'error',
            'is_live' => true,
            'payment_methods' => $this->scalapayPaymentMethods(''),
        ]);

        self::assertSame([], $this->captured_configuration);
    }

    public function testScalapayMerchantTokenIsKeptOnAnEmptyAnswer()
    {
        $this->stored_token = 'tok_live';

        $this->service->treatAccountResponse([]);

        self::assertSame([], $this->captured_configuration);
    }

    public function tokenChangeProvider()
    {
        yield 'received' => ['', 'tok_abc', 'Api::treatAccountResponse - Scalapay merchant token received'];

        yield 'removed' => ['tok_abc', '', 'Api::treatAccountResponse - Scalapay merchant token removed'];

        yield 'replaced' => ['tok_old', 'tok_abc', 'Api::treatAccountResponse - Scalapay merchant token updated'];
    }

    /**
     * @dataProvider tokenChangeProvider
     *
     * @param string $stored
     * @param string $received
     * @param string $message
     */
    public function testAScalapayMerchantTokenChangeIsLoggedOnceWithoutTheToken($stored, $received, $message)
    {
        $this->stored_token = $stored;
        $this->json_answer['payment_methods'] = $this->scalapayPaymentMethods($received);

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame([[$message, 'info']], $this->logs);
    }

    public function testAnUnchangedScalapayMerchantTokenIsNotLogged()
    {
        $this->stored_token = 'tok_abc';
        $this->json_answer['payment_methods'] = $this->scalapayPaymentMethods('tok_abc');

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame([], $this->logs);
    }

    public function testAMissingStoredTokenAndNoReceivedTokenIsNotLogged()
    {
        $this->stored_token = false;
        $this->json_answer['payment_methods'] = ['scalapay' => ['enabled' => true]];

        $this->service->treatAccountResponse($this->json_answer);

        self::assertSame([], $this->logs);
    }

    /**
     * @param mixed $token
     *
     * @return array
     */
    private function scalapayPaymentMethods($token)
    {
        return [
            'scalapay' => [
                'enabled' => true,
                'merchant_token' => $token,
            ],
        ];
    }
}
