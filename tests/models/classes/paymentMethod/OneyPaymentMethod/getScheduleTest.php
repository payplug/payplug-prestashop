<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group oney_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getScheduleTest extends BaseOneyPaymentMethod
{
    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $active
     */
    public function testWhenGivenDataHasInvalidFormat($active)
    {
        $this->assertSame(
            [],
            $this->classe->getSchedule($active)
        );
    }

    public function testWhenEnabled()
    {
        $configuration = true;
        $response = $this->classe->getSchedule($configuration);

        $assert_configurations = [];
        foreach ($response as $key => $schedule_configuration) {
            if ('checked' == $key) {
                $assert_configurations[] = (bool) $schedule_configuration;
            }
        }
        $this->assertTrue(in_array(true, $assert_configurations));
    }

    public function testWhenDisabled()
    {
        $configuration = false;
        $response = $this->classe->getSchedule($configuration);

        $assert_configurations = [];
        foreach ($response as $key => $schedule_configuration) {
            if ('checked' == $key) {
                $assert_configurations[] = (bool) $schedule_configuration;
            }
        }
        $this->assertTrue(in_array(false, $assert_configurations));
    }
}
