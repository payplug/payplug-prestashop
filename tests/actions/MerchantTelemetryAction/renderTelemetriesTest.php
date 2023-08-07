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
        $hash = '55adccc282d322c8d9ff632a3f31517cce07da223ae59c50707eb12864d66f58';
        $this->configuration
            ->shouldReceive('getValue')
            ->with('telemetry_hash')
            ->andReturn($hash);

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
                'date_upd' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->assertSame(
            $expected,
            $this->action->renderTelemetries($source)
        );
    }
}
