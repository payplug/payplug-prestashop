<?php

namespace PayPlug\tests\models\classes\ApiRest;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 */
class getOneyPopupCartTest extends BaseApiRest
{
    public function setUp()
    {
        parent::setUp();
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
            $this->classe->getOneyPopupCart($active)
        );
    }

    /**
     * @description  test with switch set to true
     */
    public function testWhenEnabled()
    {
        $configuration = true;
        $response = $this->classe->getOneyPopupCart($configuration);

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
        $response = $this->classe->getOneyPopupCart($configuration);

        $assert_configurations = [];
        foreach ($response as $key => $schedule_configuration) {
            if ($key == 'checked') {
                $assert_configurations[] = (bool) $schedule_configuration;
            }
        }
        $this->assertTrue(in_array(false, $assert_configurations));
    }
}
