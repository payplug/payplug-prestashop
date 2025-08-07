<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class SetApiKeysbyJsonResponseTest extends BaseApi
{
    public $json_answer;

    public function setUp()
    {
        parent::setUp();

        $this->json_answer = [
            'object' => 'auth_obj',
            'secret_keys' => [
                'test' => 'sk_test_azerty12345',
                'live' => 'sk_live_azerty12345',
            ],
        ];

        $configuration_class = \Mockery::mock('ConfigurationClass');
        $configuration_class->shouldReceive([
            'set' => true,
            'getValue' => true,
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $configuration_class,
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
            null,
            $this->service->setApiKeysbyJsonResponse($json_answer)
        );
    }

    public function testWhenGivenJsonAnswerContainError()
    {
        $json_answer = [
            'object' => 'error',
        ];
        $this->assertSame(
            null,
            $this->service->setApiKeysbyJsonResponse($json_answer)
        );
    }

    public function testWhenAPICantBeInitialized()
    {
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $configuration_class->shouldReceive([
            'set' => true,
            'getValue' => true,
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $configuration_class,
        ]);
        $this->service->shouldReceive([
            'initialize' => null,
        ]);
        $this->assertSame(
            null,
            $this->service->setApiKeysbyJsonResponse($this->json_answer)
        );
    }

    public function testWhenApiKeysAreSetted()
    {
        $this->service->shouldReceive([
            'initialize' => $this->api,
        ]);
        $this->assertSame(
            $this->api,
            $this->service->setApiKeysbyJsonResponse($this->json_answer)
        );
    }
}
