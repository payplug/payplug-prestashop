<?php

namespace PayPlug\tests\actions\MerchantTelemetryAction;

/**
 * @group unit
 * @group action
 * @group merchant_telemetry_action
 *
 * @runTestsInSeparateProcesses
 */
class renderTelemetriesTest extends BaseMerchantTelemetryAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $source
     */
    public function testWhenGivenSourceIsNotValidString($source)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameter given, $source must be a non empty string.',
            ],
            $this->action->renderTelemetries($source)
        );
    }

    public function testWhenMerchantTelemetryCorrespondToHash()
    {
        $source = 'source';

        // Expected hash for current mocked telemetries
        $expected_hash = '4b8611ab5ba4538dcf0b31881c33b52481b9b922088cf5283233a8ecc758a5bf';
        $this->configuration
            ->shouldReceive('getValue')
            ->with('telemetry_hash')
            ->andReturn($expected_hash);

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Current configuration correspond to previous hash.',
            ],
            $this->action->renderTelemetries($source)
        );
    }

    public function testWhenMerchantTelemetryIsRender()
    {
        $source = 'source';

        // Expected hash for current mocked telemetries
        $hash = 'wrong_hash';
        $this->configuration
            ->shouldReceive('getValue')
            ->with('telemetry_hash')
            ->andReturn($hash);
        $this->configuration
            ->shouldReceive([
                'set' => true,
            ]);

        $expected = [
            'result' => true,
            'telemetries' => [
                'version' => '1.0.0',
                'php_version' => '',
                'name' => 'payplug',
                'configurations' => [],
                'domains' => [
                    [
                        'url' => 'website.domain.1.com',
                        'default' => true,
                    ],
                    [
                        'url' => 'website.domain.2.com',
                        'default' => false,
                    ],
                    [
                        'url' => 'website.domain.3.com',
                        'default' => false,
                    ],
                ],
                'modules' => [
                    [
                        'name' => 'module_1',
                        'version' => '1.0.0',
                    ],
                    [
                        'name' => 'module_2',
                        'version' => '2.0.0',
                    ],
                    [
                        'name' => 'module_3',
                        'version' => '3.0.0',
                    ],
                ],
                'source' => 'source',
            ],
        ];

        $this->assertSame(
            $expected,
            $this->action->renderTelemetries($source)
        );
    }
}
