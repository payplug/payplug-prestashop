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
class getOneyScheduleTest extends BaseApiRest
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
        yield [['key' => 'value']];
        yield [null];
        yield [''];
    }

    /**
     * @description  test with invalid parameters
     * @dataProvider invalidDataProvider
     *
     * @param mixed $active
     */
    public function testWhenGivenDataHasInvalidFormat($active)
    {
        $this->assertSame(
            [],
            $this->classe->getOneySchedule($active)
        );
    }

    /**
     * @description  test with switch set to true
     */
    public function testWhenEnabled()
    {
        $configuration = true;
        $response = $this->classe->getOneySchedule($configuration);

        $assert_configurations = [];
        foreach ($response as $key => $schedule_configuration) {
            if ($key == 'checked') {
                $assert_configurations[] = (bool) $schedule_configuration;
            }
        }
        $this->assertTrue(in_array(true, $assert_configurations));
    }

    /**
     * @description  test with switch set to false
     */
    public function testWhenDisabled()
    {
        $configuration = false;
        $response = $this->classe->getOneySchedule($configuration);

        $assert_configurations = [];
        foreach ($response as $key => $schedule_configuration) {
            if ($key == 'checked') {
                $assert_configurations[] = (bool) $schedule_configuration;
            }
        }
        $this->assertTrue(in_array(false, $assert_configurations));
    }
}
