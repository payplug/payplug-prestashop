<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class GetOneySimulationsTest extends BaseApi
{
    public $data;

    public function setUp()
    {
        parent::setUp();
        $this->data = [
            'key' => 'value',
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $data
     */
    public function testWhenGivenDataIsntValidArray($data)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $data given',
            ],
            $this->service->getOneySimulations($data)
        );
    }

    public function testWhenAPiCantBeInitialize()
    {
        $this->service->shouldReceive([
            'initialize' => false,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'Cannot connect to the API',
            ],
            $this->service->getOneySimulations($this->data)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->oney_simulation
            ->shouldReceive('getSimulations')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->getOneySimulations($this->data)
        );
    }

    public function testWhenOneyOperationAreGetted()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $resource = OneySimulationsMock::get();
        $this->oney_simulation->shouldReceive([
            'getSimulations' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->getOneySimulations($this->data)
        );
    }
}
